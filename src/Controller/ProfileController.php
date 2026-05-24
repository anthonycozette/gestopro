<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile', name: 'app_profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user  = $this->getUser();
        $error = null;

        if ($request->isMethod('POST')) {
            $firstName    = trim($request->request->get('firstName', ''));
            $lastName     = trim($request->request->get('lastName', ''));
            $email        = trim($request->request->get('email', ''));
            $phone        = trim($request->request->get('phone', '')) ?: null;
            $siret        = trim($request->request->get('siret', '')) ?: null;
            $address      = trim($request->request->get('address', '')) ?: null;
            $postalCode   = trim($request->request->get('postalCode', '')) ?: null;
            $city         = trim($request->request->get('city', '')) ?: null;
            $activityLabel = trim($request->request->get('activityLabel', '')) ?: null;

            if (!$firstName || !$lastName) {
                $error = 'Le prénom et le nom sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse email invalide.';
            } elseif ($email !== $user->getEmail()) {
                $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    $error = 'Cette adresse email est déjà utilisée par un autre compte.';
                }
            }

            if (!$error) {
                $user->setFirstName($firstName)
                     ->setLastName($lastName)
                     ->setEmail($email)
                     ->setPhone($phone)
                     ->setSiret($siret)
                     ->setAddress($address)
                     ->setPostalCode($postalCode)
                     ->setCity($city)
                     ->setActivityLabel($activityLabel);

                $em->flush();
                $this->addFlash('success', 'Informations mises à jour avec succès.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/index.html.twig', ['error' => $error]);
    }

    #[Route('/password', name: '_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        /** @var User $user */
        $user        = $this->getUser();
        $current     = $request->request->get('current_password', '');
        $new         = $request->request->get('new_password', '');
        $confirm     = $request->request->get('confirm_password', '');

        if (!$hasher->isPasswordValid($user, $current)) {
            $this->addFlash('password_error', 'Mot de passe actuel incorrect.');
            return $this->redirectToRoute('app_profile');
        }

        if (strlen($new) < 8) {
            $this->addFlash('password_error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            return $this->redirectToRoute('app_profile');
        }

        if ($new !== $confirm) {
            $this->addFlash('password_error', 'Les deux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setPassword($hasher->hashPassword($user, $new));
        $em->flush();

        $this->addFlash('password_success', 'Mot de passe modifié avec succès.');
        return $this->redirectToRoute('app_profile');
    }
}
