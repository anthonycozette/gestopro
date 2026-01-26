<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class URSSAFController extends AbstractController
{
    #[Route('/api/u/r/s/s/a/f', name: 'app_api_u_r_s_s_a_f')]
    public function index(): Response
    {
        return $this->render('api/urssaf/index.html.twig', [
            'controller_name' => 'Api/URSSAFController',
        ]);
    }
}
