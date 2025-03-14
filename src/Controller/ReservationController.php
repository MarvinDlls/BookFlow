<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ReservationRepository $reservationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
    }

    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $reservations = $this->reservationRepository->findBy(['user' => $user], ['reservation_date' => 'DESC']);
        
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
    
    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Book $book, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a déjà 5 réservations actives
        $activeReservations = $this->reservationRepository->findActiveByUser($user);
        
        if (count($activeReservations) >= 5) {
            $this->addFlash('error', 'Vous avez déjà atteint le maximum de 5 réservations.');
            return $this->redirectToRoute('app_books_list');
        }
        
        // Vérifier si le livre est déjà réservé par l'utilisateur
        $existingReservation = $this->reservationRepository->findOneBy([
            'user' => $user,
            'book' => $book,
            'status' => true
        ]);
        
        if ($existingReservation) {
            $this->addFlash('warning', 'Vous avez déjà réservé ce livre.');
            return $this->redirectToRoute('app_book_details', ['id' => $book->getId()]);
        }
        
        // Vérifier si le livre est restreint
        if ($book->isRestricted()) {
            // Vérifier si l'utilisateur a le rôle nécessaire pour réserver des livres restreints
            if (!$this->isGranted('ROLE_PREMIUM')) {
                $this->addFlash('error', 'Ce livre est réservé aux membres premium.');
                return $this->redirectToRoute('app_book_details', ['id' => $book->getId()]);
            }
        }
        
        // Créer une nouvelle réservation
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setBook($book);
        $reservation->setReservationDate(new \DateTime());
        
        // Date d'expiration : aujourd'hui + 7 jours
        $expirationDate = new \DateTimeImmutable();
        $expirationDate->modify('+7 days');
        $reservation->setExpirationDate($expirationDate);
        
        $reservation->setStatus(true);
        
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Votre réservation du livre "' . $book->getName() . '" a été enregistrée avec succès.');
        
        return $this->redirectToRoute('app_reservation_index');
    }
    
    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['GET', 'POST'])]
    public function cancel(Reservation $reservation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la réservation appartient à l'utilisateur
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette réservation.');
        }
        
        // Annuler la réservation
        $reservation->setStatus(false);
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Votre réservation a été annulée avec succès.');
        
        return $this->redirectToRoute('app_reservation_index');
    }
    
    #[Route('/extend/{id}', name: 'app_reservation_extend', methods: ['GET', 'POST'])]
    public function extend(Reservation $reservation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la réservation appartient à l'utilisateur
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à prolonger cette réservation.');
        }
        
        // Vérifier si la réservation est active
        if (!$reservation->isStatus()) {
            $this->addFlash('error', 'Cette réservation n\'est plus active.');
            return $this->redirectToRoute('app_reservation_index');
        }
        
        // Prolonger la date d'expiration de 7 jours
        $expirationDate = $reservation->getExpirationDate();
        $expirationDate->modify('+7 days');
        $reservation->setExpirationDate($expirationDate);
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Votre réservation a été prolongée avec succès.');
        
        return $this->redirectToRoute('app_reservation_index');
    }
}