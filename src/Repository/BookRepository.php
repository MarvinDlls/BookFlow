<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function search(string $query, int $maxResults = 10, ?int $startIndex = 0): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.name LIKE :query OR b.author LIKE :query OR b.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($maxResults)
            ->setFirstResult($startIndex)
            ->getQuery()
            ->getResult();
    }

    public function save(Book $book, bool $flush = true): void
    {
        $this->_em->persist($book);
        if ($flush) {
            $this->_em->flush();
        }
    }


    //    /**
    //     * @return Book[] Returns an array of Book objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Book
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
