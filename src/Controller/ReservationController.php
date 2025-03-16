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

    #[Route('', name: 'app_reservation_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        $reservations = $this->reservationRepository->findBy(['user' => $user], ['reservation_date' => 'DESC']);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, $id, EntityManagerInterface $entityManager): Response
    {
        $id = (int) $id;

        // Récupérer le livre par son ID
        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            $this->addFlash('error', 'Livre non trouvé dans la base de données.');
            // Rediriger vers la liste des livres ou la page précédente
            $referer = $request->headers->get('referer');
            return $this->redirect($referer ?: $this->generateUrl('app_books_list'));
        }

        $user = $this->getUser();

        // Vérifications des réservations existantes et des restrictions
        $activeReservations = $entityManager->getRepository(Reservation::class)->findActiveByUser($user);

        if (count($activeReservations) >= 5) {
            $this->addFlash('error', 'Vous avez déjà atteint le maximum de 5 réservations.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $existingReservation = $entityManager->getRepository(Reservation::class)->findOneBy([
            'user' => $user,
            'book' => $book,
            'status' => true
        ]);

        if ($existingReservation) {
            $this->addFlash('warning', 'Vous avez déjà réservé ce livre.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Vérifier si le livre est restreint
        if ($book->isRestricted() && !$this->isGranted('ROLE_PREMIUM')) {
            $this->addFlash('error', 'Ce livre est réservé aux membres premium.');
            return $this->redirectToRoute('app_books_list');
        }

        // Créer une nouvelle réservation
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setBook($book);
        $reservation->setReservationDate(new \DateTime());
        $reservation->setStatus(true); // La réservation est active
        $reservation->setExpirationDate(new \DateTimeImmutable('+7 days'));
        $reservation->setCreatedAt(new \DateTimeImmutable());
        $reservation->setUpdatedAt(new \DateTimeImmutable());

        $book->setIsReserved(true); // Le livre est désormais réservé

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation du livre "' . $book->getName() . '" a été enregistrée avec succès.');

        // Rediriger vers la liste des réservations plutôt que vers les détails du livre
        return $this->redirectToRoute('app_reservation_index');
    }

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

        $reservation->setStatus(false);

        $book = $reservation->getBook();
        $book->setIsReserved(false);

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée avec succès.');

        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/extend/{id}', name: 'app_reservation_extend', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_ADMIN')]
public function extend(Reservation $reservation): Response
{
    if (!$reservation->getStatus()) {
        $this->addFlash('error', 'Cette réservation n\'est plus active.');
        return $this->redirectToRoute('app_reservation_index');
    }

    $newExpirationDate = new \DateTimeImmutable($reservation->getExpirationDate()->format('Y-m-d H:i:s') . ' +7 days');
    $reservation->setExpirationDate($newExpirationDate);

    $this->entityManager->flush();

    $this->addFlash('success', 'La réservation a été prolongée avec succès.');

    return $this->redirectToRoute('app_reservation_index');
}
}