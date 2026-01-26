<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InvoiceController extends AbstractController
{
    #[Route('/api/invoice', name: 'app_api_invoice')]
    public function index(): Response
    {
        return $this->render('api/invoice/index.html.twig', [
            'controller_name' => 'Api/InvoiceController',
        ]);
    }
}
