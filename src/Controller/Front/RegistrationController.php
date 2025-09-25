<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashed = $passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData());
            $user->setPassword($hashed);
            $em->persist($user);
            $em->flush();

            // Email de bienvenue
            try {
                $email = (new TemplatedEmail())
                    ->from('no-reply@monblog.local')
                    ->to($user->getEmail())
                    ->subject('Bienvenue sur Mon Blog')
                    ->htmlTemplate('email/welcome.html.twig')
                    ->context([
                        'email' => $user->getEmail(),
                    ]);
                $mailer->send($email);
            } catch (\Throwable $e) {
                // On ne bloque pas l'inscription si l'email échoue
                $this->addFlash('warning', "Compte créé mais email non envoyé: " . $e->getMessage());
            }

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}