<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller utilisé pour gérer les réservations.
 */
#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ReservationRepository $reservationRepository;

    /**
     * Crée une nouvelle instance de ReservationController.
     *
     * @param EntityManagerInterface $entityManager
     * @param ReservationRepository $reservationRepository
     */
    public function __construct(EntityManagerInterface $entityManager, ReservationRepository $reservationRepository)
    {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
    }

    /**
     * Affiche la liste des réservations.
     *
     */
    #[Route('', name: 'app_reservation_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $reservations = $this->reservationRepository->findBy(['user' => $user], ['reservation_date' => 'DESC']);

        foreach ($reservations as $reservation) {
            $now = new \DateTimeImmutable();
            $expirationDate = $reservation->getExpirationDate();
            $book = $reservation->getBook();

            if ($expirationDate <= $now) {
                $reservation->setStatus('expire');
                $book->setIsReserved(false);
            } elseif ($expirationDate->diff($now)->days === 3) {
                $this->addFlash('warning', 'Votre réservation du livre "' . $book->getName() . '" va expirer dans 3 jours.');
            } elseif ($expirationDate->diff($now)->days === 1) {
                $this->addFlash('warning', 'Votre réservation du livre "' . $book->getName() . '" va expirer dans 1 jour.');
            }
        }

        $this->entityManager->flush();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Crée une nouvelle réservation.
     *
     * @param int $id
     * @param Request $request
     */
    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $id): Response
    {
        $user = $this->getUser();

        $book = $this->entityManager->getRepository(Book::class)->find($id);
        if (!$book) {
            $this->addFlash('error', 'Livre non trouvé.');
            return $this->redirectToRoute('app_books_list');
        }

        if (count($this->reservationRepository->findActiveByUser($user)) >= 5) {
            $this->addFlash('error', 'Vous avez atteint la limite de 5 réservations.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($this->reservationRepository->findOneBy(['user' => $user, 'book' => $book])) {
            $this->addFlash('warning', 'Vous avez déjà réservé ce livre.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($book->isRestricted() && !$this->isGranted('ROLE_PREMIUM')) {
            $this->addFlash('error', 'Ce livre est réservé aux membres premium.');
            return $this->redirectToRoute('app_books_list');
        }

        $reservation = (new Reservation())
            ->setUser($user)
            ->setBook($book)
            ->setReservationDate(new \DateTime())
            ->setExpirationDate(new \DateTimeImmutable('+7 days'))
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $book->setIsReserved(true);
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été enregistrée avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    }

    /**
     * Annule une réservation.
     *
     * @param Reservation $reservation
     */
    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['GET', 'POST'])]
    public function cancel(Reservation $reservation): Response
    {
        $user = $this->getUser();
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette réservation.');
        }

        if (!$reservation->getStatus()) {
            $this->addFlash('error', 'Cette réservation n\'est plus active.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $reservation->setStatus('annule');
        $reservation->getBook()->setIsReserved(false);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée.');
        return $this->redirectToRoute('app_reservation_index');
    }

    /**
     * Prolonge une réservation.
     *
     * @param Reservation $reservation
     *
     */
    #[Route('/extend/{id}', name: 'app_reservation_extend', methods: ['GET', 'POST'])]
    public function extend(Reservation $reservation): Response
    {
        if (!$reservation->getStatus()) {
            $this->addFlash('error', 'Cette réservation n\'est plus active.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $reservation->setStatus("prolongation");
        $this->entityManager->flush();

        $this->addFlash('success', 'La demande de prolongation de réservation a été envoyée avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    }
}
