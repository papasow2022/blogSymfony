<?php

namespace App\Controller\Api;

use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class PostApiController extends AbstractController
{
    #[Route('/posts', name: 'api_posts_index', methods: ['GET'])]
    public function index(PostRepository $postRepository, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(1, $request->query->getInt('limit', 10)));
        $category = $request->query->get('category');
        $search = $request->query->get('search');
        $sort = $request->query->get('sort', 'createdAt');
        $order = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ($page - 1) * $limit;

        // Total
        $total = $postRepository->count([]);

        // Récupérer les posts
        if ($search || $category) {
            $posts = $postRepository->search($search, $category); // TODO: adapter pour pagination si besoin
        } else {
            // Sécurité sur le champ de tri
            $allowedSort = ['createdAt','updatedAt','viewsCount','likesCount','id'];
            if (!in_array($sort, $allowedSort, true)) { $sort = 'createdAt'; }
            $posts = $postRepository->findBy([], [$sort => $order], $limit, $offset);
        }

        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'author' => [
                    'id' => $post->getAuthor()->getId(),
                    'email' => $post->getAuthor()->getEmail(),
                    'avatar' => $post->getAuthor()->getAvatarFilename() ? '/uploads/avatars/' . $post->getAuthor()->getAvatarFilename() : null
                ],
                'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $post->getUpdatedAt() ? $post->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                'likesCount' => $post->getLikesCount(),
                'viewsCount' => $post->getViewsCount(),
                'category' => $post->getCategory() ? [ 'id' => $post->getCategory()->getId(), 'name' => $post->getCategory()->getName() ] : null,
                'tags' => $post->getTagsArray(),
                'image' => $post->getImageFilename() ? '/uploads/posts/' . $post->getImageFilename() : null
            ];
        }

        $response = $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total
            ]
        ]);

        // Headers de pagination
        $base = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
        $qs = function($p) use ($request, $limit, $sort, $order, $category, $search) {
            $params = [ 'page' => $p, 'limit' => $limit, 'sort' => $sort, 'order' => $order ];
            if ($category) { $params['category'] = $category; }
            if ($search) { $params['search'] = $search; }
            return '?' . http_build_query($params);
        };
        $links = [];
        $lastPage = max(1, (int) ceil($total / $limit));
        $links[] = "<{$base}{$qs(max(1, $page-1))}>; rel=\"prev\"";
        $links[] = "<{$base}{$qs(min($lastPage, $page+1))}>; rel=\"next\"";
        $links[] = "<{$base}{$qs(1)}>; rel=\"first\"";
        $links[] = "<{$base}{$qs($lastPage)}>; rel=\"last\"";

        $response->headers->set('X-Total-Count', (string) $total);
        $response->headers->set('Link', implode(', ', $links));
        $response->headers->set('Access-Control-Expose-Headers', 'X-Total-Count, Link');

        return $response;
    }

    #[Route('/posts/popular', name: 'api_posts_popular', methods: ['GET'])]
    public function popular(PostRepository $postRepository, Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 5);
        $posts = $postRepository->findBy([], ['viewsCount' => 'DESC'], $limit);

        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'viewsCount' => $post->getViewsCount(),
                'likesCount' => $post->getLikesCount(),
                'image' => $post->getImageFilename() ? '/uploads/posts/' . $post->getImageFilename() : null
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    #[Route('/posts/{id}', name: 'api_posts_show', methods: ['GET'])]
    public function show(int $id, PostRepository $postRepository, EntityManagerInterface $em): JsonResponse
    {
        $post = $postRepository->find($id);
        
        if (!$post) {
            return $this->json([
                'success' => false,
                'message' => 'Post not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Incrémenter le compteur de vues
        $post->incrementViews();
        $em->flush();

        $data = [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'author' => [
                'id' => $post->getAuthor()->getId(),
                'email' => $post->getAuthor()->getEmail(),
                'avatar' => $post->getAuthor()->getAvatarFilename() 
                    ? '/uploads/avatars/' . $post->getAuthor()->getAvatarFilename()
                    : null
            ],
            'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $post->getUpdatedAt() ? $post->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            'likesCount' => $post->getLikesCount(),
            'viewsCount' => $post->getViewsCount(),
            'category' => $post->getCategory() ? [
                'id' => $post->getCategory()->getId(),
                'name' => $post->getCategory()->getName()
            ] : null,
            'tags' => $post->getTagsArray(),
            'image' => $post->getImageFilename() ? '/uploads/posts/' . $post->getImageFilename() : null
        ];

        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }
} 