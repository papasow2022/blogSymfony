<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/api')]
class AuthApiController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        if (!$email || !$password) {
            return $this->json([
                'success' => false,
                'message' => 'Email and password are required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Note: L'authentification réelle se fait via le firewall Symfony
        // Cette API est principalement pour la documentation
        return $this->json([
            'success' => false,
            'message' => 'Please use the web interface for authentication or implement JWT authentication'
        ], JsonResponse::HTTP_METHOD_NOT_ALLOWED);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');

        // Validation basique
        if (!$email || !$password || !$confirmPassword) {
            return $this->json([
                'success' => false,
                'message' => 'Email, password and confirm_password are required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($password !== $confirmPassword) {
            return $this->json([
                'success' => false,
                'message' => 'Passwords do not match'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 6) {
            return $this->json([
                'success' => false,
                'message' => 'Password must be at least 6 characters long'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'User with this email already exists'
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        // $user->setCreatedAt(new \DateTimeImmutable()); // supprimé: la propriété n'existe pas sur User

        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'createdAt' => null
            ]
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // La déconnexion se fait via le firewall Symfony
        return $this->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
} 