<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    private CacheInterface $cache;
    private PostRepository $postRepository;

    public function __construct(CacheInterface $cache, PostRepository $postRepository)
    {
        $this->cache = $cache;
        $this->postRepository = $postRepository;
    }

    /**
     * Récupère les articles populaires avec cache
     */
    public function getPopularPosts(int $limit = 5): array
    {
        return $this->cache->get('popular_posts_' . $limit, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(3600); // Cache pendant 1 heure
            
            return $this->postRepository->findBy(
                [], 
                ['viewsCount' => 'DESC'], 
                $limit
            );
        });
    }

    /**
     * Récupère les derniers articles avec cache
     */
    public function getLatestPosts(int $limit = 10): array
    {
        return $this->cache->get('latest_posts_' . $limit, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(1800); // Cache pendant 30 minutes
            
            return $this->postRepository->findBy(
                [], 
                ['createdAt' => 'DESC'], 
                $limit
            );
        });
    }

    /**
     * Récupère les articles par catégorie avec cache
     */
    public function getPostsByCategory(int $categoryId, int $limit = 10): array
    {
        return $this->cache->get('posts_category_' . $categoryId . '_' . $limit, function (ItemInterface $item) use ($categoryId, $limit) {
            $item->expiresAfter(1800); // Cache pendant 30 minutes
            
            return $this->postRepository->findBy(
                ['category' => $categoryId], 
                ['createdAt' => 'DESC'], 
                $limit
            );
        });
    }

    /**
     * Récupère les résultats de recherche avec cache
     */
    public function getSearchResults(string $query, ?string $category = null, int $limit = 20): array
    {
        $cacheKey = 'search_' . md5($query . '_' . $category . '_' . $limit);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $category, $limit) {
            $item->expiresAfter(900); // Cache pendant 15 minutes
            
            return $this->postRepository->search($query, $category);
        });
    }

    /**
     * Récupère les statistiques du blog avec cache
     */
    public function getBlogStats(): array
    {
        return $this->cache->get('blog_stats', function (ItemInterface $item) {
            $item->expiresAfter(3600); // Cache pendant 1 heure
            
            $totalPosts = $this->postRepository->count([]);
            $totalViews = $this->postRepository->getTotalViews();
            $totalLikes = $this->postRepository->getTotalLikes();
            
            return [
                'total_posts' => $totalPosts,
                'total_views' => $totalViews,
                'total_likes' => $totalLikes,
                'average_views_per_post' => $totalPosts > 0 ? round($totalViews / $totalPosts, 2) : 0,
                'average_likes_per_post' => $totalPosts > 0 ? round($totalLikes / $totalPosts, 2) : 0,
            ];
        });
    }

    /**
     * Récupère un article avec cache
     */
    public function getPost(int $postId): ?Post
    {
        return $this->cache->get('post_' . $postId, function (ItemInterface $item) use ($postId) {
            $item->expiresAfter(1800); // Cache pendant 30 minutes
            
            return $this->postRepository->find($postId);
        });
    }

    /**
     * Invalide le cache d'un article spécifique
     */
    public function invalidatePostCache(int $postId): void
    {
        $this->cache->delete('post_' . $postId);
    }

    /**
     * Invalide tous les caches liés aux articles
     */
    public function invalidateAllPostCaches(): void
    {
        // Supprimer les caches principaux
        $this->cache->delete('popular_posts_5');
        $this->cache->delete('latest_posts_10');
        $this->cache->delete('latest_posts_20'); // Ajouter le cache utilisé par la page index
        $this->cache->delete('blog_stats');
        
        // Note: Pour une invalidation complète, il faudrait supprimer tous les caches
        // de recherche et par catégorie, mais c'est plus complexe
    }

    /**
     * Invalide le cache des statistiques
     */
    public function invalidateStatsCache(): void
    {
        $this->cache->delete('blog_stats');
    }

    /**
     * Récupère les articles les plus likés avec cache
     */
    public function getMostLikedPosts(int $limit = 5): array
    {
        return $this->cache->get('most_liked_posts_' . $limit, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(3600); // Cache pendant 1 heure
            
            return $this->postRepository->findBy(
                [], 
                ['likesCount' => 'DESC'], 
                $limit
            );
        });
    }

    /**
     * Récupère les articles récents par auteur avec cache
     */
    public function getRecentPostsByAuthor(int $authorId, int $limit = 5): array
    {
        return $this->cache->get('author_posts_' . $authorId . '_' . $limit, function (ItemInterface $item) use ($authorId, $limit) {
            $item->expiresAfter(1800); // Cache pendant 30 minutes
            
            return $this->postRepository->findBy(
                ['author' => $authorId], 
                ['createdAt' => 'DESC'], 
                $limit
            );
        });
    }
}