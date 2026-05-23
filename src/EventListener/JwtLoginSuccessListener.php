<?php

namespace App\EventListener;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

final class JwtLoginSuccessListener
{
    public function __construct(
        private readonly RefreshTokenGeneratorInterface $generator,
        private readonly RefreshTokenManagerInterface $manager,
        private readonly EntityManagerInterface $em,
        private readonly int $ttl = 2592000,
    ) {}

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $refreshToken = $this->generator->createForUserWithTtl($user, $this->ttl);
        $this->manager->save($refreshToken);

        $data = $event->getData();
        $data['refresh_token'] = $refreshToken->getRefreshToken();
        $event->setData($data);
    }
}
