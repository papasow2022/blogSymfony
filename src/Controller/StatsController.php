<?php

namespace App\Controller;

use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stats')]
class StatsController extends AbstractController
{
    #[Route('/', name: 'stats_index', methods: ['GET'])]
    public function index(CacheService $cacheService): Response
    {
        $stats = $cacheService->getBlogStats();
        $popularPosts = $cacheService->getPopularPosts(5);
        $mostLikedPosts = $cacheService->getMostLikedPosts(5);

        return $this->render('stats/index.html.twig', [
            'stats' => $stats,
            'popularPosts' => $popularPosts,
            'mostLikedPosts' => $mostLikedPosts,
        ]);
    }

    #[Route('/api', name: 'stats_api', methods: ['GET'])]
    public function api(CacheService $cacheService): JsonResponse
    {
        $stats = $cacheService->getBlogStats();
        $popularPosts = $cacheService->getPopularPosts(5);
        $mostLikedPosts = $cacheService->getMostLikedPosts(5);

        return $this->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'popular_posts' => array_map(function($post) {
                    return [
                        'id' => $post->getId(),
                        'title' => $post->getTitle(),
                        'views_count' => $post->getViewsCount(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                    ];
                }, $popularPosts),
                'most_liked_posts' => array_map(function($post) {
                    return [
                        'id' => $post->getId(),
                        'title' => $post->getTitle(),
                        'views_count' => $post->getViewsCount(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                    ];
                }, $mostLikedPosts),
            ]
        ]);
    }
}