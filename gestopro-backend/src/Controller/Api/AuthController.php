<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    #[Route('/api/auth', name: 'app_api_auth')]
    public function index(): Response
    {
        return $this->render('api/auth/index.html.twig', [
            'controller_name' => 'Api/AuthController',
        ]);
    }
}
