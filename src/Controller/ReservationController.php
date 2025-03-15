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
        $user = $this->getUser();

        $reservations = $this->reservationRepository->findBy(['user' => $user], ['reservation_date' => 'DESC']);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, $id): Response
    {
        $id = (int) $id;
        // Récupérer le livre explicitement par ID
        $book = $this->entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            $this->addFlash('error', 'Livre non trouvé dans la base de données.');
            // Rediriger vers la liste des livres ou la page précédente
            $referer = $request->headers->get('referer');
            return $this->redirect($referer ?: $this->generateUrl('app_books_list'));
        }

        $user = $this->getUser();

        // Vérifications des réservations existantes et des restrictions
        $activeReservations = $this->reservationRepository->findActiveByUser($user);

        if (count($activeReservations) >= 5) {
            $this->addFlash('error', 'Vous avez déjà atteint le maximum de 5 réservations.');
            return $this->redirectToRoute('app_book_details', ['id' => $book->getId()]);
        }

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
        if ($book->isRestricted() && !$this->isGranted('ROLE_PREMIUM')) {
            $this->addFlash('error', 'Ce livre est réservé aux membres premium.');
            return $this->redirectToRoute('app_book_details', ['id' => $book->getId()]);
        }

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setBook($book);
        $reservation->setReservationDate(new \DateTime());
        $reservation->setStatus(true);
        $reservation->setExpirationDate(new \DateTimeImmutable('+7 days'));
        $reservation->setCreatedAt(new \DateTimeImmutable());
        $reservation->setUpdatedAt(new \DateTimeImmutable());

        $book->setIsReserved(true);

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation du livre "' . $book->getName() . '" a été enregistrée avec succès.');

        // Rediriger vers la page de détails du livre
        return $this->redirectToRoute('app_book_details', ['id' => $book->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['GET', 'POST'])]
    public function cancel(Reservation $reservation): Response
    {
        $user = $this->getUser();

        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette réservation.');
        }

        if (!$reservation->isStatus()) {
            $this->addFlash('error', 'Cette réservation n\'est plus active.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $reservation->setStatus(false);

        $book = $reservation->getBook();
        $book->setIsReserved(false);

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée avec succès.');

        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/extend/{id}', name: 'app_reservation_extend', methods: ['GET', 'POST'])]
    public function extend(Reservation $reservation): Response
    {
        $user = $this->getUser();

        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à prolonger cette réservation.');
        }

        if (!$reservation->isStatus()) {
            $this->addFlash('error', 'Cette réservation n\'est plus active.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $newExpirationDate = new \DateTimeImmutable($reservation->getExpirationDate()->format('Y-m-d H:i:s') . ' +7 days');
        $reservation->setExpirationDate($newExpirationDate);

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été prolongée avec succès.');

        return $this->redirectToRoute('app_reservation_index');
    }
}
