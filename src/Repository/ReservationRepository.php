<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Trouve les réservations actives d'un utilisateur.
     *
     * @param User $user
     * @return Reservation[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.status IN (:statuses)') // 📌 Vérifie les bons statuts
            ->setParameter('user', $user)
            ->setParameter('statuses', ['en_attente', 'réservé']) // 📌 Statuts actifs
            ->getQuery()
            ->getResult();
    }

    // src/Repository/ReservationRepository.php
    public function findActiveReservation(User $user, Book $book): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.book = :book')
            ->andWhere('r.status = :status') // Vérifie que la réservation est active
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->setParameter('status', 'reserve')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}