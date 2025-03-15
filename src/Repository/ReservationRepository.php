<?php
namespace App\Repository;

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
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', true) // Pour les réservations actives
            ->getQuery()
            ->getResult();
    }
}
