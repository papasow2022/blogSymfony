<?php

namespace App\Controller\Front;

use App\Repository\PostRepository;
use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(CacheService $cacheService): Response
    {
        $posts = $cacheService->getLatestPosts(20);
        return $this->render('front/home/index.html.twig', [
            'posts' => $posts,
            'query' => null,
            'category' => null
        ]);
    }
}