<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class CommentApiController extends AbstractController
{
    #[Route('/posts/{postId}/comments', name: 'api_comments_by_post', methods: ['GET'])]
    public function getByPost(int $postId, CommentRepository $commentRepository, PostRepository $postRepository, Request $request): JsonResponse
    {
        $post = $postRepository->find($postId);
        if (!$post) {
            return $this->json([
                'success' => false,
                'message' => 'Post not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        // Récupérer les commentaires principaux (sans parent) approuvés
        $comments = $commentRepository->createQueryBuilder('c')
            ->where('c.post = :post')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isApproved = :approved')
            ->setParameter('post', $post)
            ->setParameter('approved', true)
            ->orderBy('c.createdAt', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($comments as $comment) {
            $data[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'author' => [
                    'id' => $comment->getAuthor()->getId(),
                    'email' => $comment->getAuthor()->getEmail(),
                    'avatar' => $comment->getAuthor()->getAvatarFilename() 
                        ? '/uploads/avatars/' . $comment->getAuthor()->getAvatarFilename()
                        : null
                ],
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'isApproved' => $comment->isApproved(),
                'replies' => $this->getReplies($comment, $commentRepository)
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

    #[Route('/posts/{postId}/comments', name: 'api_comments_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(int $postId, Request $request, EntityManagerInterface $em, PostRepository $postRepository): JsonResponse
    {
        $post = $postRepository->find($postId);
        if (!$post) {
            return $this->json([
                'success' => false,
                'message' => 'Post not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $content = $request->request->get('content');
        $parentId = $request->request->get('parent_id');

        if (!$content || strlen(trim($content)) < 3) {
            return $this->json([
                'success' => false,
                'message' => 'Comment content must be at least 3 characters long'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setContent(trim($content));
        $comment->setAuthor($this->getUser());
        $comment->setPost($post);
        $comment->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Africa/Conakry')));
        $comment->setIsApproved(false); // En attente d'approbation par défaut

        // Si c'est une réponse à un autre commentaire
        if ($parentId) {
            $parentComment = $em->getRepository(Comment::class)->find($parentId);
            if ($parentComment && $parentComment->getPost() === $post) {
                $comment->setParent($parentComment);
            }
        }

        $em->persist($comment);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Comment submitted successfully and will be visible after approval',
            'data' => [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'isApproved' => $comment->isApproved()
            ]
        ], JsonResponse::HTTP_CREATED);
    }

    private function getReplies(Comment $parentComment, CommentRepository $commentRepository): array
    {
        $replies = $commentRepository->findBy([
            'parent' => $parentComment,
            'isApproved' => true
        ], ['createdAt' => 'ASC']);

        $data = [];
        foreach ($replies as $reply) {
            $data[] = [
                'id' => $reply->getId(),
                'content' => $reply->getContent(),
                'author' => [
                    'id' => $reply->getAuthor()->getId(),
                    'email' => $reply->getAuthor()->getEmail(),
                    'avatar' => $reply->getAuthor()->getAvatarFilename() 
                        ? '/uploads/avatars/' . $reply->getAuthor()->getAvatarFilename()
                        : null
                ],
                'createdAt' => $reply->getCreatedAt()->format('Y-m-d H:i:s'),
                'isApproved' => $reply->isApproved()
            ];
        }

        return $data;
    }
} 