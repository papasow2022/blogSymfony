<?php

namespace App\Controller\Front;

use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\CommentReport;
use App\Form\PostType;
use App\Form\CommentType;
use App\Form\CommentReportType;
use App\Repository\PostRepository;
use App\Repository\CommentReportRepository;
use App\Repository\PostLikeRepository;
use App\Service\CacheService;
use App\Service\ImageOptimizerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/post')]
class PostController extends AbstractController
{
    #[Route('/', name: 'post_index', methods: ['GET'])]
    public function index(Request $request, CacheService $cacheService): Response
    {
        $query = $request->query->get('q');
        $category = $request->query->get('category');
        
        if ($query || $category) {
            $posts = $cacheService->getSearchResults($query, $category);
        } else {
            $posts = $cacheService->getLatestPosts(20);
        }
        
        return $this->render('front/post/index.html.twig', [
            'posts' => $posts,
            'query' => $query,
            'category' => $category
        ]);
    }

    #[Route('/new', name: 'post_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ParameterBagInterface $params, ImageOptimizerService $imageOptimizer, CacheService $cacheService): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            $optimized = true; // Par défaut, pas d'image ou optimisation réussie

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $uploadPath = $params->get('kernel.project_dir').'/public/uploads/posts/'.$newFilename;

                try {
                    $imageFile->move(
                        $params->get('kernel.project_dir').'/public/uploads/posts',
                        $newFilename
                    );

                    $optimized = $imageOptimizer->resizeAndOptimize($imageFile, $uploadPath, 1200, 800);
                    
                    if ($optimized) {
                        $post->setImageFilename($newFilename);
                    } else {
                        $post->setImageFilename($newFilename);
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                }
            }

            $post->setAuthor($this->getUser());
            $post->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Africa/Conakry')));
            $em->persist($post);
            $em->flush();

            // Invalider le cache après la sauvegarde
            $cacheService->invalidateAllPostCaches();

            // Message de succès conditionnel selon l'optimisation d'image
            if ($imageFile && !$optimized) {
                $this->addFlash('success', 'Votre article a été créé avec succès ! (Image uploadée mais optimisation échouée)');
            } else {
                $this->addFlash('success', 'Votre article a été créé avec succès !');
            }
            return $this->redirectToRoute('post_index');
        }

        return $this->render('front/post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'post_show', methods: ['GET','POST'])]
    public function show(Post $post, Request $request, EntityManagerInterface $em, CommentReportRepository $reportRepo, PaginatorInterface $paginator, CacheService $cacheService): Response
    {
        $post->incrementViews();
        $em->flush();

        $cacheService->invalidatePostCache($post->getId());
        $cacheService->invalidateStatsCache();

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($this->getUser() && $form->isSubmitted() && $form->isValid()) {
            $parentId = $request->request->get('parent_id');
            if ($parentId) {
                $parentComment = $em->getRepository(Comment::class)->find($parentId);
                if ($parentComment && $parentComment->getPost() === $post) {
                    $comment->setParent($parentComment);
                }
            }
            
            $comment->setAuthor($this->getUser());
            $comment->setPost($post);
            $comment->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Africa/Conakry')));
            $comment->setIsApproved(false);
            $em->persist($comment);
            $em->flush();
            
            $this->addFlash('success', 'Votre commentaire a été soumis et sera visible après approbation !');
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        $commentsQuery = $em->getRepository(Comment::class)->createQueryBuilder('c')
            ->where('c.post = :post')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isApproved = :approved')
            ->setParameter('post', $post)
            ->setParameter('approved', true)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery();

        $comments = $paginator->paginate(
            $commentsQuery,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('front/post/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'commentForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'post_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Post $post, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ParameterBagInterface $params, ImageOptimizerService $imageOptimizer, CacheService $cacheService): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $post, 'Vous ne pouvez pas éditer cet article.');

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $uploadPath = $params->get('kernel.project_dir').'/public/uploads/posts/'.$newFilename;

                try {
                    $imageFile->move(
                        $params->get('kernel.project_dir').'/public/uploads/posts',
                        $newFilename
                    );

                    $optimized = $imageOptimizer->resizeAndOptimize($imageFile, $uploadPath, 1200, 800);
                    
                    if ($optimized) {
                        $post->setImageFilename($newFilename);
                        $this->addFlash('success', 'Image optimisée et mise à jour avec succès !');
                    } else {
                        $post->setImageFilename($newFilename);
                        $this->addFlash('warning', 'Image mise à jour mais optimisation échouée.');
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                }
            }

            $post->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $cacheService->invalidatePostCache($post->getId());
            $cacheService->invalidateAllPostCaches();
            
            $this->addFlash('success', 'Votre article a été modifié avec succès !');
            return $this->redirectToRoute('post_index');
        }

        return $this->render('front/post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/{id}/delete', name: 'post_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, EntityManagerInterface $em, CacheService $cacheService, PostRepository $postRepository): Response
    {
        $id = $request->attributes->get('id');
        $post = $postRepository->find($id);
        
        if (!$post) {
            $this->addFlash('error', 'L\'article demandé n\'existe pas.');
            return $this->redirectToRoute('post_index');
        }
        
        $this->denyAccessUnlessGranted('DELETE', $post, 'Vous ne pouvez pas supprimer cet article.');
        if ($this->isCsrfTokenValid('delete_post_'.$post->getId(), (string) $request->request->get('_token'))) {
            // Sauvegarder l'ID avant la suppression
            $postId = $post->getId();
            
            $em->remove($post);
            $em->flush();

            // Invalider le cache avec l'ID sauvegardé
            $cacheService->invalidatePostCache($postId);
            $cacheService->invalidateAllPostCaches();
            
            $this->addFlash('success', 'L\'article a été supprimé avec succès !');
        }
        return $this->redirectToRoute('post_index');
    }

    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->getUser() !== $comment->getAuthor()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce commentaire.');
        }

        if ($this->isCsrfTokenValid('delete_comment_'.$comment->getId(), (string) $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            
            $this->addFlash('success', 'Le commentaire a été supprimé.');
        }

        return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
    }

    #[Route('/comment/{id}/report', name: 'comment_report', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function reportComment(Comment $comment, Request $request, EntityManagerInterface $em, CommentReportRepository $reportRepo): Response
    {
        $user = $this->getUser();
        
        $existingReport = $reportRepo->findByCommentAndReporter($comment, $user);
        if ($existingReport) {
            $this->addFlash('warning', 'Vous avez déjà signalé ce commentaire.');
            return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
        }

        $report = new CommentReport();
        $form = $this->createForm(CommentReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setReporter($user);
            $report->setComment($comment);
            $report->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Africa/Conakry')));
            $em->persist($report);
            $em->flush();

            $this->addFlash('success', 'Le commentaire a été signalé. Merci pour votre vigilance.');
            return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render('front/comment/report.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/like', name: 'post_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function like(Post $post, Request $request, EntityManagerInterface $em, PostLikeRepository $likeRepo): Response
    {
        $user = $this->getUser();
        
        $existingLike = $likeRepo->findByPostAndUser($post, $user);
        
        if ($existingLike) {
            $em->remove($existingLike);
            $post->setLikesCount($post->getLikesCount() - 1);
            $message = 'Like retiré';
        } else {
            $like = new \App\Entity\PostLike();
            $like->setUser($user);
            $like->setPost($post);
            $em->persist($like);
            $post->setLikesCount($post->getLikesCount() + 1);
            $message = 'Article liké !';
        }
        
        $em->flush();
        
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'likesCount' => $post->getLikesCount(),
                'isLiked' => $existingLike ? false : true,
                'message' => $message
            ]);
        }
        
        $this->addFlash('success', $message);
        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
    }

    #[Route('/author/{id}', name: 'post_by_author', methods: ['GET'])]
    public function byAuthor(User $author, PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy(['author' => $author], ['createdAt' => 'DESC']);
        
        return $this->render('front/post/by_author.html.twig', [
            'author' => $author,
            'posts' => $posts
        ]);
    }
}