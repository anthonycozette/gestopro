<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SubscriptionController extends AbstractController
{
    #[Route('/pricing', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('subscription/pricing.html.twig');
    }

    #[Route('/subscription', name: 'app_subscription_manage')]
    public function manage(): Response
    {
        return $this->render('subscription/manage.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/subscription/checkout/{plan}', name: 'app_subscription_checkout', methods: ['POST'])]
    public function checkout(string $plan, Request $request, StripeService $stripe): Response
    {
        if (!$this->isCsrfTokenValid('subscription_checkout', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_subscription_manage');
        }

        $prices = [
            User::PLAN_PRO    => $this->getParameter('stripe_price_pro'),
            User::PLAN_EXPERT => $this->getParameter('stripe_price_expert'),
        ];

        if (!isset($prices[$plan])) {
            throw $this->createNotFoundException('Plan inconnu.');
        }

        $priceId = $prices[$plan];
        if (empty($priceId)) {
            $this->addFlash('error', 'Stripe n\'est pas configuré sur ce serveur (clé de prix manquante).');
            return $this->redirectToRoute('app_subscription_manage');
        }

        /** @var User $user */
        $user = $this->getUser();
        $session = $stripe->createCheckoutSession($user, $priceId, $plan);

        return $this->redirect($session->url);
    }

    #[Route('/subscription/success', name: 'app_subscription_success')]
    public function success(): Response
    {
        return $this->render('subscription/success.html.twig');
    }

    #[Route('/subscription/portal', name: 'app_subscription_portal', methods: ['POST'])]
    public function portal(Request $request, StripeService $stripe): Response
    {
        if (!$this->isCsrfTokenValid('subscription_portal', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_subscription_manage');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getStripeCustomerId()) {
            $this->addFlash('error', 'Aucun abonnement actif trouvé.');
            return $this->redirectToRoute('app_subscription_manage');
        }

        $portalSession = $stripe->createPortalSession($user);

        return $this->redirect($portalSession->url);
    }
}
