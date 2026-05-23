<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ReceiptScannerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/expenses/scan', name: 'api_expense_scan', methods: ['POST'])]
class ExpenseScanController extends AbstractController
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
    private const MAX_SIZE_MB  = 10;

    public function __invoke(Request $request, ReceiptScannerService $scanner): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Plan free : accès interdit
        if ($user->getPlan() === 'free') {
            return $this->json(
                ['error' => 'Le scan OCR est réservé aux plans Pro et Expert.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        // ── Récupération de l'image ──────────────────────────────────────────
        $base64    = null;
        $mediaType = 'image/jpeg';

        $file = $request->files->get('receipt');
        if ($file) {
            // Upload multipart/form-data
            if (!in_array($file->getMimeType(), self::ALLOWED_MIME, true)) {
                return $this->json(
                    ['error' => 'Format non supporté. Utilisez JPEG, PNG, WebP ou PDF.'],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
            if ($file->getSize() > self::MAX_SIZE_MB * 1024 * 1024) {
                return $this->json(
                    ['error' => sprintf('Fichier trop volumineux (max %d Mo).', self::MAX_SIZE_MB)],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
            $mediaType = $file->getMimeType();
            $base64    = base64_encode(file_get_contents($file->getPathname()));
        } else {
            // JSON body avec { "image": "base64...", "media_type": "image/jpeg" }
            $body = json_decode($request->getContent(), true);
            if (!empty($body['image'])) {
                $base64    = $body['image'];
                $mediaType = $body['media_type'] ?? 'image/jpeg';

                if (!in_array($mediaType, self::ALLOWED_MIME, true)) {
                    return $this->json(
                        ['error' => 'media_type non supporté.'],
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                    );
                }
            }
        }

        if (!$base64) {
            return $this->json(
                ['error' => 'Aucune image fournie. Envoyez un fichier (champ "receipt") ou un JSON avec "image" en base64.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // ── Appel Claude Vision ───────────────────────────────────────────────
        try {
            $result = $scanner->scan($base64, $mediaType);
        } catch (\Throwable $e) {
            return $this->json(
                ['error' => 'Erreur lors de l\'analyse : ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return $this->json([
            'vendor'     => $result['vendor'],
            'date'       => $result['date'],
            'amount_ttc' => $result['amount_ttc'],
            'amount_ht'  => $result['amount_ht'],
            'tva'        => $result['tva'],
            'tva_rate'   => $result['tva_rate'],
            'category'   => $result['category'],
            'notes'      => $result['notes'],
            'confidence' => $result['confidence'],
        ]);
    }
}
