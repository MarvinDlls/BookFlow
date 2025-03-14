<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Book;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/library')]
class ReservationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ReservationRepository $reservationRepository;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;
    private ReservationService $reservationService;

    public function __construct(EntityManagerInterface $entityManager, ReservationRepository $reservationRepository, UserRepository $userRepository, BookRepository $bookRepository, ReservationService $reservationService)
    {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
        $this->userRepository = $userRepository;
        $this->bookRepository = $bookRepository;
        $this->reservationService = $reservationService;
    }

    #[Route('/{userId}/reservations', name: 'get_user_reservations', methods: ['GET'])]
    public function getUserReservations(int $userId): Response
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $reservations = $this->reservationService->getReservationsByUser($user);
        return $this->json($reservations);
    }

    #[Route('/{userId}/reserve/{bookId}', name: 'reserve_book', methods: ['POST'])]
    public function reserveBook(int $userId, int $bookId): Response
    {
        $user = $this->userRepository->find($userId);
        $book = $this->bookRepository->find($bookId);

        if (!$user || !$book) {
            return $this->json(['error' => 'Utilisateur ou livre non trouvé.'], 404);
        }

        $activeReservations = $this->reservationRepository->countActiveReservationsByUser($user);

        if ($activeReservations >= 5) {
            return $this->json(['error' => 'Vous ne pouvez pas réserver plus de 5 livres.'], 400);
        }

        $expirationDate = (new \DateTimeImmutable())->modify('+30 days');
        $success = $this->reservationService->addReservation($user, $book, $expirationDate);

        if (!$success) {
            return $this->json(['error' => 'Erreur lors de la réservation du livre.'], 500);
        }

        return $this->json(['success' => 'Livre réservé avec succès.']);
    }

    #[Route('/{userId}/cancel/{bookId}', name: 'cancel_reservation', methods: ['DELETE'])]
    public function cancelReservation(int $userId, int $bookId): Response
    {
        $user = $this->userRepository->find($userId);
        $book = $this->bookRepository->find($bookId);

        if (!$user || !$book) {
            return $this->json(['error' => 'Utilisateur ou livre non trouvé.'], 404);
        }

        $success = $this->reservationService->removeReservation($user, $book);

        if (!$success) {
            return $this->json(['error' => 'Erreur lors de l\'annulation de la réservation.'], 500);
        }

        return $this->json(['success' => 'Réservation annulée avec succès.']);
    }
}