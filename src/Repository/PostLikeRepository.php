<?php

namespace App\Repository;

use App\Entity\PostLike;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostLike>
 *
 * @method PostLike|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostLike|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostLike[]    findAll()
 * @method PostLike[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostLike::class);
    }

    public function findByPostAndUser(Post $post, User $user): ?PostLike
    {
        return $this->createQueryBuilder('pl')
            ->andWhere('pl.post = :post')
            ->andWhere('pl.user = :user')
            ->setParameter('post', $post)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByPost(Post $post): int
    {
        return $this->createQueryBuilder('pl')
            ->select('COUNT(pl.id)')
            ->andWhere('pl.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
