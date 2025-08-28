<?php

namespace App\Repository;

use App\Entity\CommentReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentReport>
 */
class CommentReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentReport::class);
    }

    public function findByCommentAndReporter($comment, $reporter): ?CommentReport
    {
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.comment = :comment')
            ->andWhere('cr.reporter = :reporter')
            ->setParameter('comment', $comment)
            ->setParameter('reporter', $reporter)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
