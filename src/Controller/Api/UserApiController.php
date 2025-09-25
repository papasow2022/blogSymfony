<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class UserApiController extends AbstractController
{
    #[Route('/users', name: 'api_users_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        
        $users = $userRepository->findBy([], ['id' => 'DESC'], $limit, ($page - 1) * $limit);

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isBlocked' => $user->isBlocked(),
                'avatar' => $user->getAvatarFilename() 
                    ? '/uploads/avatars/' . $user->getAvatarFilename()
                    : null,
                'createdAt' => null
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($data)
            ]
        ]);
    }

    #[Route('/users/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur connecté peut voir ce profil
        $currentUser = $this->getUser();
        if (!$currentUser || ($currentUser->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN'))) {
            return $this->json([
                'success' => false,
                'message' => 'Access denied'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isBlocked' => $user->isBlocked(),
            'avatar' => $user->getAvatarFilename() 
                ? '/uploads/avatars/' . $user->getAvatarFilename()
                : null,
            'createdAt' => null
        ];

        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    #[Route('/profile', name: 'api_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isBlocked' => $user->isBlocked(),
            'avatar' => $user->getAvatarFilename() 
                ? '/uploads/avatars/' . $user->getAvatarFilename()
                : null,
            'createdAt' => null
        ];

        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }
} 