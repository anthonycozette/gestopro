<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret,
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $router,
    ) {
        Stripe::setApiKey($this->secretKey);
    }

    public function getOrCreateCustomer(User $user): string
    {
        if ($user->getStripeCustomerId()) {
            return $user->getStripeCustomerId();
        }

        $customer = Customer::create([
            'email'    => $user->getEmail(),
            'name'     => $user->getFullName(),
            'metadata' => ['user_id' => $user->getId()],
        ]);

        $user->setStripeCustomerId($customer->id);
        $this->em->flush();

        return $customer->id;
    }

    public function createCheckoutSession(User $user, string $priceId, string $plan): Session
    {
        $customerId = $this->getOrCreateCustomer($user);

        return Session::create([
            'customer'   => $customerId,
            'mode'       => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'success_url' => $this->router->generate(
                'app_subscription_success',
                ['session_id' => '{CHECKOUT_SESSION_ID}'],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            'cancel_url' => $this->router->generate(
                'app_subscription_manage',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            'metadata' => ['user_id' => $user->getId(), 'plan' => $plan],
            'subscription_data' => [
                'metadata' => ['user_id' => $user->getId(), 'plan' => $plan],
            ],
        ]);
    }

    public function createPortalSession(User $user): PortalSession
    {
        $customerId = $this->getOrCreateCustomer($user);

        return PortalSession::create([
            'customer'   => $customerId,
            'return_url' => $this->router->generate(
                'app_subscription_manage',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ]);
    }

    public function syncSubscriptionFromStripe(User $user): ?string
    {
        $customerId = $this->getOrCreateCustomer($user);

        $subscriptions = \Stripe\Subscription::all([
            'customer' => $customerId,
            'status'   => 'active',
            'limit'    => 1,
            'expand'   => ['data.items'],
        ]);

        if (empty($subscriptions->data)) {
            return null;
        }

        $subscription = $subscriptions->data[0];
        $priceId      = $subscription->items->data[0]->price->id;

        $pricePro    = $_ENV['STRIPE_PRICE_PRO']    ?? '';
        $priceExpert = $_ENV['STRIPE_PRICE_EXPERT'] ?? '';

        $plan = match ($priceId) {
            $pricePro    => User::PLAN_PRO,
            $priceExpert => User::PLAN_EXPERT,
            default      => null,
        };

        if (!$plan) {
            return null;
        }

        $user->setPlan($plan);
        $user->setStripeSubscriptionId($subscription->id);
        $this->em->flush();

        return $plan;
    }

    public function handleSuccessfulCheckout(User $user, string $sessionId): ?string
    {
        $session = Session::retrieve([
            'id'     => $sessionId,
            'expand' => ['subscription'],
        ]);

        if ($session->payment_status !== 'paid' && $session->status !== 'complete') {
            return null;
        }

        $plan = $session->metadata->plan ?? null;
        if (!$plan) {
            return null;
        }

        $user->setPlan($plan);

        if ($session->subscription) {
            $subscriptionId = is_string($session->subscription)
                ? $session->subscription
                : $session->subscription->id;
            $user->setStripeSubscriptionId($subscriptionId);
        }

        if (!$user->getStripeCustomerId() && $session->customer) {
            $customerId = is_string($session->customer)
                ? $session->customer
                : $session->customer->id;
            $user->setStripeCustomerId($customerId);
        }

        $this->em->flush();

        return $plan;
    }

    public function updateSubscription(User $user, string $newPriceId): void
    {
        $subscriptionId = $user->getStripeSubscriptionId();
        if (!$subscriptionId) {
            throw new \RuntimeException('Aucun abonnement actif à mettre à jour.');
        }

        $subscription = \Stripe\Subscription::retrieve([
            'id'     => $subscriptionId,
            'expand' => ['items'],
        ]);

        $itemId = $subscription->items->data[0]->id;

        \Stripe\Subscription::update($subscriptionId, [
            'items'              => [['id' => $itemId, 'price' => $newPriceId]],
            'proration_behavior' => 'create_prorations',
        ]);
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): Event
    {
        return Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
    }
}
