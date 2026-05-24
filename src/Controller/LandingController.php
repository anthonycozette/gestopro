<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandingController extends AbstractController
{
    #[Route('/', name: 'app_landing', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $name    = trim($request->request->get('name', ''));
            $email   = trim($request->request->get('email', ''));
            $subject = trim($request->request->get('subject', 'Contact GestoPro'));
            $message = trim($request->request->get('message', ''));

            if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
                $this->addFlash('contact_success', 'Message envoyé ! Nous vous répondrons sous 24 h.');
            } else {
                $this->addFlash('contact_error', 'Merci de remplir tous les champs obligatoires.');
            }

            return $this->redirectToRoute('app_landing', ['_fragment' => 'contact']);
        }

        return $this->render('landing/index.html.twig');
    }
}
