<?php

namespace App\Service;

use Google\ApiCore\ApiException;
use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\Document;
use Google\Cloud\DocumentAI\V1\Document\Entity;
use Google\Cloud\DocumentAI\V1\Document\Entity\NormalizedValue;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;

/**
 * Envoie un reçu à Google Document AI (Expense Parser) et extrait les données comptables.
 * Retourne un tableau normalisé prêt à pré-remplir une Expense.
 */
class ReceiptScannerService
{
    public function __construct(
        private readonly string $projectId,
        private readonly string $processorId,
        private readonly string $location,
        private readonly string $credentialsPath,
    ) {}

    /**
     * @param string $imageBase64  Contenu du fichier encodé en base64
     * @param string $mediaType    ex: image/jpeg, image/png, image/webp, application/pdf
     * @return array{
     *   vendor: string|null,
     *   date: string|null,
     *   amount_ttc: float|null,
     *   amount_ht: float|null,
     *   tva: float|null,
     *   tva_rate: float|null,
     *   category: string|null,
     *   notes: string|null,
     *   confidence: float,
     *   raw: string
     * }
     */
    public function scan(string $imageBase64, string $mediaType = 'image/jpeg'): array
    {
        $clientOptions = [
            'apiEndpoint' => "{$this->location}-documentai.googleapis.com",
            'transport'   => 'rest',
        ];

        if ($this->credentialsPath && file_exists($this->credentialsPath)) {
            $clientOptions['credentials'] = $this->credentialsPath;
        }

        $client = new DocumentProcessorServiceClient($clientOptions);

        try {
            $processorName = sprintf(
                'projects/%s/locations/%s/processors/%s',
                $this->projectId,
                $this->location,
                $this->processorId
            );

            $rawDocument = (new RawDocument())
                ->setContent(base64_decode($imageBase64))
                ->setMimeType($this->normalizeMimeType($mediaType));

            $request = (new ProcessRequest())
                ->setName($processorName)
                ->setRawDocument($rawDocument);

            $response = $client->processDocument($request);

            return $this->parseDocument($response->getDocument());

        } catch (ApiException $e) {
            throw new \RuntimeException('Google Document AI : ' . $e->getMessage(), 0, $e);
        } finally {
            $client->close();
        }
    }

    private function normalizeMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png'       => 'image/png',
            'image/webp'      => 'image/webp',
            'image/gif'       => 'image/gif',
            'application/pdf' => 'application/pdf',
            default           => 'image/jpeg',
        };
    }

    private function parseDocument(Document $document): array
    {
        $entities    = $document->getEntities();
        $extracted   = [];
        $confidences = [];

        /** @var Entity $entity */
        foreach ($entities as $entity) {
            $type       = $entity->getType();
            $confidence = $entity->getConfidence();
            $normVal    = $entity->getNormalizedValue();
            $rawText    = $entity->getMentionText();

            if ($confidence > 0) {
                $confidences[] = $confidence;
            }

            switch ($type) {
                case 'supplier_name':
                    $extracted['vendor'] ??= $normVal?->getText() ?: $rawText;
                    break;

                case 'receipt_date':
                case 'invoice_date':
                    $extracted['date'] ??= $this->parseDate($normVal, $rawText);
                    break;

                case 'total_amount':
                    $extracted['amount_ttc'] = $this->parseMoney($normVal, $rawText);
                    break;

                case 'net_amount':
                case 'subtotal':
                    $extracted['amount_ht'] ??= $this->parseMoney($normVal, $rawText);
                    break;

                case 'total_tax_amount':
                    $extracted['tva'] ??= $this->parseMoney($normVal, $rawText);
                    break;

                case 'vat_tax_rate':
                case 'tax_rate':
                    $extracted['tva_rate'] ??= $this->parseTaxRate($normVal, $rawText);
                    break;

                case 'receipt_id':
                case 'invoice_id':
                case 'invoice_number':
                    $extracted['invoice_number'] ??= $normVal?->getText() ?: $rawText;
                    break;

                case 'payment_type':
                    $extracted['payment_method'] ??= $this->mapPaymentMethod($normVal?->getText() ?: $rawText);
                    break;

                case 'purchase_type':
                    $extracted['category'] ??= $this->mapCategory($normVal?->getText() ?: $rawText);
                    break;
            }
        }

        // Calculer le taux de TVA depuis les montants si absent
        if (
            !isset($extracted['tva_rate'])
            && isset($extracted['tva'], $extracted['amount_ht'])
            && $extracted['amount_ht'] > 0
        ) {
            $rate = $extracted['tva'] / $extracted['amount_ht'] * 100;
            $extracted['tva_rate'] = $this->snapToFrenchTaxRate($rate);
        }

        $confidence = $confidences
            ? min(100.0, round(array_sum($confidences) / count($confidences) * 100, 1))
            : 0.0;

        return [
            'vendor'         => isset($extracted['vendor']) ? trim((string) $extracted['vendor']) : null,
            'date'           => $extracted['date']           ?? null,
            'amount_ttc'     => $extracted['amount_ttc']     ?? null,
            'amount_ht'      => $extracted['amount_ht']      ?? null,
            'tva'            => $extracted['tva']            ?? null,
            'tva_rate'       => $extracted['tva_rate']       ?? null,
            'category'       => $extracted['category']       ?? null,
            'invoice_number' => $extracted['invoice_number'] ?? null,
            'payment_method' => $extracted['payment_method'] ?? null,
            'notes'          => null,
            'confidence'     => $confidence,
            'raw'            => '',
        ];
    }

    private function parseDate(?NormalizedValue $normVal, string $rawText): ?string
    {
        if ($normVal) {
            $dateVal = $normVal->getDateValue();
            if ($dateVal && $dateVal->getYear() > 0) {
                return sprintf('%04d-%02d-%02d',
                    $dateVal->getYear(),
                    $dateVal->getMonth(),
                    $dateVal->getDay()
                );
            }
            $text = $normVal->getText();
            if ($text) {
                try { return (new \DateTimeImmutable($text))->format('Y-m-d'); } catch (\Throwable) {}
            }
        }
        try { return (new \DateTimeImmutable($rawText))->format('Y-m-d'); } catch (\Throwable) {}
        return null;
    }

    private function parseMoney(?NormalizedValue $normVal, string $rawText): ?float
    {
        if ($normVal) {
            $money = $normVal->getMoneyValue();
            if ($money) {
                return (float) $money->getUnits() + $money->getNanos() / 1_000_000_000;
            }
            $text = trim($normVal->getText());
            if ($text !== '') {
                return $this->parseFloat($text);
            }
        }
        return $this->parseFloat($rawText);
    }

    private function parseTaxRate(?NormalizedValue $normVal, string $rawText): ?float
    {
        $text = $normVal?->getText() ?: $rawText;
        $val  = $this->parseFloat($text);
        return $val !== null ? $this->snapToFrenchTaxRate($val) : null;
    }

    private function parseFloat(string $text): ?float
    {
        $cleaned = preg_replace('/[^\d,.]/', '', $text);
        $cleaned = str_replace(',', '.', $cleaned);
        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function snapToFrenchTaxRate(float $rate): float
    {
        foreach ([0.0, 5.5, 10.0, 20.0] as $standard) {
            if (abs($rate - $standard) < 1.5) {
                return $standard;
            }
        }
        return round($rate, 1);
    }

    private function mapPaymentMethod(string $paymentType): string
    {
        return match (strtoupper(trim($paymentType))) {
            'CREDIT_CARD', 'DEBIT_CARD', 'CARD' => 'carte',
            'CASH'                               => 'especes',
            'CHECK', 'CHEQUE'                    => 'cheque',
            'BANK_TRANSFER', 'WIRE_TRANSFER'     => 'virement',
            'DIRECT_DEBIT'                       => 'prelevement',
            default                              => '',
        };
    }

    private function mapCategory(string $purchaseType): string
    {
        return match (strtoupper(trim($purchaseType))) {
            'FOOD_AND_BEVERAGES', 'FOOD_BEVERAGE', 'MEALS', 'RESTAURANT', 'FOOD'
                => 'repas',
            'TRAVEL', 'TRANSPORTATION', 'TAXI', 'TRAIN', 'AIRLINE', 'VEHICLE_RENTAL'
                => 'transport',
            'GAS', 'GAS_AND_FUEL', 'FUEL', 'PETROL'
                => 'carburant',
            'ACCOMMODATION', 'LODGING', 'HOTEL'
                => 'hebergement',
            'OFFICE_SUPPLIES', 'EQUIPMENT', 'ELECTRONICS', 'COMPUTER'
                => 'materiel',
            'SOFTWARE', 'SUBSCRIPTION', 'SAAS', 'DIGITAL_SERVICES'
                => 'logiciel',
            'TELECOMMUNICATIONS', 'TELECOM', 'PHONE', 'INTERNET', 'MOBILE'
                => 'telecom',
            'TRAINING', 'EDUCATION', 'CONFERENCE'
                => 'formation',
            'ADVERTISING', 'MARKETING', 'PROMOTION'
                => 'marketing',
            'BANKING', 'BANK_FEES', 'FINANCIAL_SERVICES'
                => 'bancaire',
            default
                => 'autre',
        };
    }
}
