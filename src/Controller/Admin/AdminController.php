<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\CommentReport;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use App\Repository\CommentReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        CommentReportRepository $reportRepository
    ): Response {
        $stats = [
            'total_users' => $userRepository->count([]),
            'total_posts' => $postRepository->count([]),
            'total_comments' => $commentRepository->count([]),
            'pending_reports' => $reportRepository->count(['resolved' => false]),
            'blocked_users' => $userRepository->count(['isBlocked' => true]),
            'pending_comments' => $commentRepository->count(['isApproved' => false])
        ];

        $popularPosts = $postRepository->findBy([], ['viewsCount' => 'DESC'], 5);
        $recentReports = $reportRepository->findBy(['resolved' => false], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'popular_posts' => $popularPosts,
            'recent_reports' => $recentReports
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/users/{id}/block', name: 'admin_user_block', methods: ['POST'])]
    public function blockUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('block_user_'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsBlocked(true);
            $em->flush();
            $this->addFlash('success', 'Utilisateur bloqué avec succès.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/unblock', name: 'admin_user_unblock', methods: ['POST'])]
    public function unblockUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('unblock_user_'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsBlocked(false);
            $em->flush();
            $this->addFlash('success', 'Utilisateur débloqué avec succès.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_user_'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/comments', name: 'admin_comments')]
    public function comments(CommentRepository $commentRepository): Response
    {
        $pendingComments = $commentRepository->findBy(['isApproved' => false], ['createdAt' => 'DESC']);
        $allComments = $commentRepository->findBy([], ['createdAt' => 'DESC'], 50);

        return $this->render('admin/comments.html.twig', [
            'pending_comments' => $pendingComments,
            'all_comments' => $allComments
        ]);
    }

    #[Route('/comments/{id}/approve', name: 'admin_comment_approve', methods: ['POST'])]
    public function approveComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve_comment_'.$comment->getId(), $request->request->get('_token'))) {
            $comment->setIsApproved(true);
            $em->flush();
            $this->addFlash('success', 'Commentaire approuvé avec succès.');
        }

        return $this->redirectToRoute('admin_comments');
    }

    #[Route('/comments/{id}/delete', name: 'admin_comment_delete', methods: ['POST'])]
    public function deleteComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_comment_'.$comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_comments');
    }

    #[Route('/reports', name: 'admin_reports')]
    public function reports(CommentReportRepository $reportRepository): Response
    {
        $pendingReports = $reportRepository->findBy(['resolved' => false], ['createdAt' => 'DESC']);
        $resolvedReports = $reportRepository->findBy(['resolved' => true], ['createdAt' => 'DESC'], 20);

        return $this->render('admin/reports.html.twig', [
            'pending_reports' => $pendingReports,
            'resolved_reports' => $resolvedReports
        ]);
    }

    #[Route('/reports/{id}/resolve', name: 'admin_report_resolve', methods: ['POST'])]
    public function resolveReport(CommentReport $report, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('resolve_report_'.$report->getId(), $request->request->get('_token'))) {
            $report->setResolved(true);
            $em->flush();
            $this->addFlash('success', 'Signalement résolu avec succès.');
        }

        return $this->redirectToRoute('admin_reports');
    }

    #[Route('/statistics', name: 'admin_statistics')]
    public function statistics(
        PostRepository $postRepository,
        UserRepository $userRepository,
        CommentRepository $commentRepository
    ): Response {
        $popularPosts = $postRepository->findBy([], ['viewsCount' => 'DESC'], 10);
        $mostLikedPosts = $postRepository->findBy([], ['likesCount' => 'DESC'], 10);
        $activeUsers = $userRepository->findBy([], [], 10);

        return $this->render('admin/statistics.html.twig', [
            'popular_posts' => $popularPosts,
            'most_liked_posts' => $mostLikedPosts,
            'active_users' => $activeUsers
        ]);
    }

    #[Route('/create-admin', name: 'admin_create_admin')]
    public function createAdmin(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $existingAdmin = $em->getRepository(User::class)->findOneBy(['roles' => ['ROLE_ADMIN']]);
        
        if ($existingAdmin) {
            $this->addFlash('warning', 'Un administrateur existe déjà.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsBlocked(false);

        $em->persist($admin);
        $em->flush();

        $this->addFlash('success', 'Utilisateur administrateur créé avec succès ! Email: admin@example.com, Mot de passe: admin123');
        return $this->redirectToRoute('admin_dashboard');
    }
}