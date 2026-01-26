<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExpenseController extends AbstractController
{
    #[Route('/api/expense', name: 'app_api_expense')]
    public function index(): Response
    {
        return $this->render('api/expense/index.html.twig', [
            'controller_name' => 'Api/ExpenseController',
        ]);
    }
}
