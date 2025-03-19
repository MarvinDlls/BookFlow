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
            ->andWhere('r.status IN (:status)')
            ->setParameter('user', $user)
            ->setParameter('status', ['en_attente', 'réservé'])
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une réservation active d'un utilisateur pour un livre.
     *
     * @param User $user
     * @param Book $book
     * @return Reservation|null
     */
    public function findActiveReservation(User $user, Book $book): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.book = :book')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->setParameter('status', 'reserve')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les dernières réservations.
     *
     * @param int $limit Nombre maximum de réservations à retourner (par défaut 5).
     * @return Reservation[]
     */
    public function findLatestReservations(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.reservation_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

}