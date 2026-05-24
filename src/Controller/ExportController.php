<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Repository\ExpenseRepository;
use App\Repository\InvoiceRepository;
use App\Repository\UrssafDeclarationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/export', name: 'app_export')]
class ExportController extends AbstractController
{
    #[Route('/invoices.csv', name: '_invoices_csv')]
    public function invoicesCsv(InvoiceRepository $repo): StreamedResponse
    {
        $invoices = $repo->findBy(
            ['user' => $this->getUser(), 'type' => Invoice::TYPE_INVOICE],
            ['issuedAt' => 'DESC']
        );

        return $this->csvResponse('factures.csv', function () use ($invoices) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($out, ['Numéro', 'Client', 'Date émission', 'Échéance', 'Statut', 'Devise', 'Total HT', 'TVA', 'Total TTC', 'Payée le'], ';');
            foreach ($invoices as $inv) {
                fputcsv($out, [
                    $inv->getNumber(),
                    $inv->getClient()->getName(),
                    $inv->getIssuedAt()?->format('d/m/Y'),
                    $inv->getDueAt()?->format('d/m/Y') ?? '',
                    $this->statusLabel($inv->getStatus()),
                    $inv->getCurrency(),
                    number_format((float) $inv->getTotalHt(), 2, ',', ''),
                    number_format((float) $inv->getTotalTva(), 2, ',', ''),
                    number_format((float) $inv->getTotalTtc(), 2, ',', ''),
                    $inv->getPaidAt()?->format('d/m/Y') ?? '',
                ], ';');
            }
            fclose($out);
        });
    }

    #[Route('/expenses.csv', name: '_expenses_csv')]
    public function expensesCsv(ExpenseRepository $repo): StreamedResponse
    {
        $expenses = $repo->findBy(['user' => $this->getUser()], ['date' => 'DESC']);

        return $this->csvResponse('depenses.csv', function () use ($expenses) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Fournisseur', 'Catégorie', 'Montant HT', 'TVA', 'Montant TTC', 'Taux TVA %', 'Mode paiement', 'Déductible', 'Notes'], ';');
            foreach ($expenses as $exp) {
                fputcsv($out, [
                    $exp->getDate()?->format('d/m/Y'),
                    $exp->getVendor(),
                    $exp->getCategory()?->getName() ?? '',
                    number_format((float) $exp->getAmountHt(), 2, ',', ''),
                    number_format((float) $exp->getTva(), 2, ',', ''),
                    number_format((float) $exp->getAmountTtc(), 2, ',', ''),
                    number_format((float) $exp->getTvaRate(), 1, ',', ''),
                    $exp->getPaymentMethod() ?? '',
                    $exp->isDeductible() ? 'Oui' : 'Non',
                    $exp->getNotes() ?? '',
                ], ';');
            }
            fclose($out);
        });
    }

    #[Route('/urssaf.csv', name: '_urssaf_csv')]
    public function urssafCsv(UrssafDeclarationRepository $repo): StreamedResponse
    {
        $declarations = $repo->findBy(['user' => $this->getUser()], ['periodStart' => 'DESC']);

        return $this->csvResponse('declarations-urssaf.csv', function () use ($declarations) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Période', 'Début', 'Fin', 'Périodicité', 'CA déclaré (€)', 'Taux cotisation %', 'Cotisation (€)', 'Statut', 'Date déclaration'], ';');
            foreach ($declarations as $d) {
                fputcsv($out, [
                    $d->getPeriodLabel(),
                    $d->getPeriodStart()?->format('d/m/Y'),
                    $d->getPeriodEnd()?->format('d/m/Y'),
                    $d->getPeriodicity() === 'monthly' ? 'Mensuelle' : 'Trimestrielle',
                    number_format((float) $d->getRevenue(), 2, ',', ''),
                    number_format((float) $d->getCotisationRate() * 100, 1, ',', ''),
                    number_format((float) $d->getCotisationAmount(), 2, ',', ''),
                    $d->isDeclared() ? 'Déclarée' : 'À déclarer',
                    $d->getDeclaredAt()?->format('d/m/Y') ?? '',
                ], ';');
            }
            fclose($out);
        });
    }

    #[Route('/fec.csv', name: '_fec_csv')]
    public function fecCsv(InvoiceRepository $invoiceRepo, ExpenseRepository $expenseRepo): StreamedResponse
    {
        $user     = $this->getUser();
        $invoices = $invoiceRepo->findBy(['user' => $user, 'type' => Invoice::TYPE_INVOICE]);
        $expenses = $expenseRepo->findBy(['user' => $user]);

        return $this->csvResponse('FEC-' . date('Ymd') . '.txt', function () use ($invoices, $expenses) {
            $out = fopen('php://output', 'w');
            // En-tête FEC (format DGFiP — 18 colonnes)
            fputcsv($out, [
                'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
                'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
                'PieceRef', 'PieceDate', 'EcritureLib',
                'Debit', 'Credit',
                'EcritureLet', 'DateLet',
                'ValidDate', 'Montantdevise', 'Idevise',
            ], "\t");

            $lineNum = 1;

            // Factures — journal VTE (ventes)
            foreach ($invoices as $inv) {
                $date = $inv->getIssuedAt()?->format('Ymd') ?? date('Ymd');
                $ref  = $inv->getNumber();
                $ht   = $this->fecAmount((float) $inv->getTotalHt());
                $tva  = $this->fecAmount((float) $inv->getTotalTva());
                $ttc  = $this->fecAmount((float) $inv->getTotalTtc());

                // Ligne client (débit 411)
                fputcsv($out, [
                    'VTE', 'Ventes', sprintf('VTE%06d', $lineNum), $date,
                    '411000', 'Clients', '', $inv->getClient()->getName(),
                    $ref, $date, 'Facture ' . $ref,
                    $ttc, '0,00',
                    '', '', $date, '', '',
                ], "\t");
                $lineNum++;

                // Ligne produit (crédit 706)
                fputcsv($out, [
                    'VTE', 'Ventes', sprintf('VTE%06d', $lineNum), $date,
                    '706000', 'Prestations de services', '', '',
                    $ref, $date, 'Facture ' . $ref,
                    '0,00', $ht,
                    '', '', $date, '', '',
                ], "\t");
                $lineNum++;

                // Ligne TVA collectée (crédit 445710) si applicable
                if ((float) $inv->getTotalTva() > 0) {
                    fputcsv($out, [
                        'VTE', 'Ventes', sprintf('VTE%06d', $lineNum), $date,
                        '445710', 'TVA collectée', '', '',
                        $ref, $date, 'TVA ' . $ref,
                        '0,00', $tva,
                        '', '', $date, '', '',
                    ], "\t");
                    $lineNum++;
                }
            }

            // Dépenses — journal ACH (achats)
            foreach ($expenses as $exp) {
                $date = $exp->getDate()?->format('Ymd') ?? date('Ymd');
                $ref  = 'DEP-' . $exp->getId();
                $ht   = $this->fecAmount((float) $exp->getAmountHt());
                $tva  = $this->fecAmount((float) $exp->getTva());
                $ttc  = $this->fecAmount((float) $exp->getAmountTtc());

                // Ligne charge (débit 6xx)
                fputcsv($out, [
                    'ACH', 'Achats', sprintf('ACH%06d', $lineNum), $date,
                    '606100', 'Fournitures', '', $exp->getVendor(),
                    $ref, $date, $exp->getVendor(),
                    $ht, '0,00',
                    '', '', $date, '', '',
                ], "\t");
                $lineNum++;

                if ((float) $exp->getTva() > 0) {
                    fputcsv($out, [
                        'ACH', 'Achats', sprintf('ACH%06d', $lineNum), $date,
                        '445660', 'TVA déductible', '', '',
                        $ref, $date, 'TVA ' . $exp->getVendor(),
                        $tva, '0,00',
                        '', '', $date, '', '',
                    ], "\t");
                    $lineNum++;
                }

                // Fournisseur (crédit 401)
                fputcsv($out, [
                    'ACH', 'Achats', sprintf('ACH%06d', $lineNum), $date,
                    '401000', 'Fournisseurs', '', $exp->getVendor(),
                    $ref, $date, $exp->getVendor(),
                    '0,00', $ttc,
                    '', '', $date, '', '',
                ], "\t");
                $lineNum++;
            }

            fclose($out);
        });
    }

    private function csvResponse(string $filename, callable $writer): StreamedResponse
    {
        $response = new StreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft'     => 'Brouillon',
            'sent'      => 'Envoyée',
            'paid'      => 'Payée',
            'overdue'   => 'En retard',
            'cancelled' => 'Annulée',
            default     => $status,
        };
    }

    private function fecAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '');
    }
}
