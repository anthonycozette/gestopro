<?php

namespace App\Service;

use Anthropic\Anthropic;

/**
 * Envoie une image de reçu à Claude Vision et extrait les données comptables.
 * Retourne un tableau normalisé prêt à pré-remplir une Expense.
 */
class ReceiptScannerService
{
    private Anthropic $client;

    public function __construct(string $apiKey)
    {
        $this->client = Anthropic::factory()->withApiKey($apiKey)->make();
    }

    /**
     * @param string $imageBase64  Contenu de l'image encodé en base64
     * @param string $mediaType    ex: image/jpeg, image/png, image/webp, image/gif
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
        $prompt = <<<'PROMPT'
Tu es un assistant comptable expert. Analyse ce reçu ou cette facture et extrais les informations suivantes en JSON strict.

Réponds UNIQUEMENT avec un objet JSON valide, sans texte avant ni après, sans markdown ni balises de code.

Format attendu :
{
  "vendor": "nom du fournisseur ou commerçant",
  "date": "YYYY-MM-DD ou null si illisible",
  "amount_ttc": montant_total_en_float_ou_null,
  "amount_ht": montant_ht_en_float_ou_null,
  "tva": montant_tva_en_float_ou_null,
  "tva_rate": taux_de_tva_en_float_ou_null,
  "category": "une parmi : transport, logiciel, materiel, repas, hebergement, formation, marketing, telecoms, autre ou null",
  "notes": "numéro de facture ou information utile, null sinon",
  "confidence": score_de_confiance_entre_0_et_100
}

Règles :
- Si un montant est illisible, mets null.
- Si la TVA n'est pas affichée, calcule-la depuis le TTC et le taux si connu.
- Le score de confiance reflète ta certitude globale sur l'extraction (0 = impossible à lire, 100 = tout est parfaitement lisible).
- Convertis toujours les montants en float (ex: 12,50 → 12.5).
PROMPT;

        $response = $this->client->messages()->create([
            'model'      => 'claude-opus-4-5',
            'max_tokens' => 512,
            'messages'   => [[
                'role'    => 'user',
                'content' => [
                    [
                        'type'   => 'image',
                        'source' => [
                            'type'       => 'base64',
                            'media_type' => $mediaType,
                            'data'       => $imageBase64,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $prompt,
                    ],
                ],
            ]],
        ]);

        $raw  = $response->content[0]->text ?? '';
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            // Claude n'a pas renvoyé du JSON valide — fallback minimal
            return [
                'vendor'     => null,
                'date'       => null,
                'amount_ttc' => null,
                'amount_ht'  => null,
                'tva'        => null,
                'tva_rate'   => null,
                'category'   => null,
                'notes'      => null,
                'confidence' => 0.0,
                'raw'        => $raw,
            ];
        }

        return [
            'vendor'     => isset($data['vendor'])     ? (string) $data['vendor']           : null,
            'date'       => isset($data['date'])       ? (string) $data['date']             : null,
            'amount_ttc' => isset($data['amount_ttc']) ? (float)  $data['amount_ttc']       : null,
            'amount_ht'  => isset($data['amount_ht'])  ? (float)  $data['amount_ht']        : null,
            'tva'        => isset($data['tva'])        ? (float)  $data['tva']              : null,
            'tva_rate'   => isset($data['tva_rate'])   ? (float)  $data['tva_rate']         : null,
            'category'   => isset($data['category'])   ? (string) $data['category']         : null,
            'notes'      => isset($data['notes'])      ? (string) $data['notes']            : null,
            'confidence' => isset($data['confidence']) ? min(100, max(0, (float) $data['confidence'])) : 0.0,
            'raw'        => $raw,
        ];
    }
}
