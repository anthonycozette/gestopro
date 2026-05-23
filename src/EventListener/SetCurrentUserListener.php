<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiPlatform\Symfony\EventListener\EventPriorities;

final class SetCurrentUserListener implements EventSubscriberInterface
{
    public function __construct(private readonly Security $security) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['setUser', EventPriorities::PRE_WRITE]];
    }

    public function setUser(ViewEvent $event): void
    {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if ($method !== Request::METHOD_POST) {
            return;
        }

        if (!is_object($entity) || !method_exists($entity, 'setUser') || !method_exists($entity, 'getUser')) {
            return;
        }

        if ($entity->getUser() !== null) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $entity->setUser($user);
        }
    }
}
