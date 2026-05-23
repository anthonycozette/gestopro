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
