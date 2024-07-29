<?php

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\Entity\Book;
use App\Domain\Entity\Borrowing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getActiveBorrowingsCount(User $user): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('count(b.id)')
            ->join('u.borrowings', 'b')
            ->where('u.id = :user_id')
            ->andWhere('b.checkinDate IS NULL')
            ->setParameter('user_id', $user->getId())
            ->getQuery();

        return (int) $qb->getSingleScalarResult();
    }

    public function findActiveBorrowing(User $user, Book $book): ?Borrowing
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('b')
            ->from(Borrowing::class, 'b')
            ->where('b.user = :user_id')
            ->andWhere('b.book = :book_id')
            ->andWhere('b.checkinDate IS NULL')
            ->setParameter('user_id', $user->getId())
            ->setParameter('book_id', $book->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
