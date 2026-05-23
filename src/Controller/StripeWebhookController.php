<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        StripeService $stripe,
        EntityManagerInterface $em,
        UserRepository $userRepo,
    ): Response {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        try {
            $event = $stripe->constructWebhookEvent($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            return new Response('Signature invalide.', 400);
        } catch (\UnexpectedValueException $e) {
            return new Response('Payload invalide.', 400);
        }

        match ($event->type) {
            'checkout.session.completed'    => $this->handleCheckoutCompleted($event->data->object, $userRepo, $em),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object, $userRepo, $em),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object, $userRepo, $em),
            default                         => null,
        };

        return new Response('', 200);
    }

    private function handleCheckoutCompleted(object $session, UserRepository $userRepo, EntityManagerInterface $em): void
    {
        $userId = $session->metadata->user_id ?? null;
        $plan   = $session->metadata->plan ?? null;

        if (!$userId || !$plan) {
            return;
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return;
        }

        $user->setPlan($plan);
        $user->setStripeSubscriptionId($session->subscription);
        $em->flush();
    }

    private function handleSubscriptionUpdated(object $subscription, UserRepository $userRepo, EntityManagerInterface $em): void
    {
        $plan = $subscription->metadata->plan ?? null;
        if (!$plan) {
            return;
        }

        $user = $userRepo->findOneBy(['stripeSubscriptionId' => $subscription->id]);
        if (!$user) {
            return;
        }

        if ($subscription->cancel_at_period_end) {
            $user->setSubscriptionEndsAt(new \DateTimeImmutable('@' . $subscription->current_period_end));
        } else {
            $user->setSubscriptionEndsAt(null);
            $user->setPlan($plan);
        }

        $em->flush();
    }

    private function handleSubscriptionDeleted(object $subscription, UserRepository $userRepo, EntityManagerInterface $em): void
    {
        $user = $userRepo->findOneBy(['stripeSubscriptionId' => $subscription->id]);
        if (!$user) {
            // fallback: find by Stripe customer ID
            $user = $userRepo->findOneBy(['stripeCustomerId' => $subscription->customer]);
        }

        if (!$user) {
            return;
        }

        $user->setPlan(User::PLAN_FREE);
        $user->setStripeSubscriptionId(null);
        $user->setSubscriptionEndsAt(null);
        $em->flush();
    }
}
