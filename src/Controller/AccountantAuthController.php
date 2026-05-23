<?php

namespace App\Controller;

use App\Entity\Accountant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AccountantAuthController extends AbstractController
{
    #[Route('/expert/login', name: 'expert_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser() instanceof Accountant) {
            return $this->redirectToRoute('expert_dashboard');
        }

        return $this->render('accountant_auth/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error'         => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/expert/logout', name: 'expert_logout')]
    public function logout(): never
    {
        throw new \LogicException('Intercepted by the firewall.');
    }

    #[Route('/expert/register', name: 'expert_register')]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        if ($this->getUser() instanceof Accountant) {
            return $this->redirectToRoute('expert_dashboard');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email     = trim($request->request->get('email', ''));
            $password  = $request->request->get('password', '');
            $firstName = trim($request->request->get('first_name', ''));
            $lastName  = trim($request->request->get('last_name', ''));
            $firm      = trim($request->request->get('firm', ''));
            $regNumber = trim($request->request->get('registration_number', ''));

            if (!$email || !$password || !$firstName || !$lastName) {
                $error = 'Tous les champs obligatoires doivent être remplis.';
            } elseif ($em->getRepository(Accountant::class)->findOneBy(['email' => $email])) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                $accountant = new Accountant();
                $accountant->setEmail($email)
                    ->setFirstName($firstName)
                    ->setLastName($lastName)
                    ->setFirm($firm ?: null)
                    ->setRegistrationNumber($regNumber ?: null)
                    ->setPassword($hasher->hashPassword($accountant, $password));

                $em->persist($accountant);
                $em->flush();

                $this->addFlash('success', 'Compte créé. Connectez-vous.');
                return $this->redirectToRoute('expert_login');
            }
        }

        return $this->render('accountant_auth/register.html.twig', ['error' => $error]);
    }
}
