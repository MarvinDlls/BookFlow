<?php
namespace App\Service;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Book;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;

class ReservationService
{
    private EntityManagerInterface $entityManager;
    private ReservationRepository $reservationRepository;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository,
        UserRepository $userRepository,
        BookRepository $bookRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
        $this->userRepository = $userRepository;
        $this->bookRepository = $bookRepository;
        $this->logger = $logger;
    }

    /**
     * Récupère les réservations d'un utilisateur
     */
    public function getReservationsByUser(User $user): array
    {
        return $this->reservationRepository->findBy(['user' => $user]);
    }

    /**
     * Ajoute une réservation pour un utilisateur
     */
    public function addReservation(User $user, Book $book, \DateTimeImmutable $expirationDate): bool
    {
        try {
            $reservation = new Reservation();
            $reservation->setUser($user);
            $reservation->setBook($book);
            $reservation->setExpirationDate($expirationDate);

            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'ajout d'une réservation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une réservation d'un utilisateur
     */
    public function removeReservation(User $user, Book $book): bool
    {
        try {
            $reservation = $this->reservationRepository->findOneBy([
                'user' => $user,
                'book' => $book
            ]);

            if (!$reservation) {
                return false;
            }

            $this->entityManager->remove($reservation);
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la suppression d'une réservation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie et supprime les réservations expirées
     */
    public function removeExpiredReservations(): int
    {
        try {
            $now = new DateTimeImmutable();
            $expiredReservations = $this->reservationRepository->findExpiredReservations($now);

            foreach ($expiredReservations as $reservation) {
                $this->entityManager->remove($reservation);
            }

            $this->entityManager->flush();

            return count($expiredReservations);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la suppression des réservations expirées : " . $e->getMessage());
            return 0;
        }
    }
}