<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserHistory;
use App\Repository\UserHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/history')]
final class UserHistoryController extends AbstractController
{
    #[Route('/', name: 'app_user_history_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(UserHistoryRepository $userHistoryRepository): Response
    {
        $user = $this->getUser();

        return $this->render('user_history/by_user.html.twig', [
            'user_histories' => $userHistoryRepository->findBy(['user' => $user], ['created_at' => 'DESC']),
            'title' => 'Mon historique'
        ]);
    }

    #[Route('/admin', name: 'app_user_history_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(UserHistoryRepository $userHistoryRepository): Response
    {
        return $this->render('user_history/admin_index.html.twig', [
            'user_histories' => $userHistoryRepository->findBy([], ['created_at' => 'DESC']),
            'title' => 'Historique des utilisateurs'
        ]);
    }

    #[Route('/create/{book}', name: 'app_user_history_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {
        $actionType = $request->request->get('action_type');
        $validActions = ['view', 'borrow', 'return', 'favorite', 'search'];

        if (!in_array($actionType, $validActions)) {
            $this->addFlash('error', 'Action non valide.');
            return $this->redirectToRoute('app_book_details', ['id' => $book->getVolumeId()]);
        }

        $userHistory = new UserHistory();
        $userHistory->setUser($this->getUser());
        $userHistory->setBook($book);
        $userHistory->setActionType($actionType);

        $entityManager->persist($userHistory);
        $entityManager->flush();

        return $this->redirectToRoute('app_book_details', ['id' => $book->getVolumeId()]);
    }

    #[Route('/user/{user}', name: 'app_user_history_by_user', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function historyByUser(User $user, UserHistoryRepository $userHistoryRepository): Response
    {
        return $this->render('user_history/by_user.html.twig', [
            'user' => $user,
            'user_histories' => $userHistoryRepository->findBy(['user' => $user], ['created_at' => 'DESC']),
            'title' => 'Historique de ' . $user->getUsername()
        ]);
    }

    #[Route('/book/{book}', name: 'app_user_history_by_book', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function historyByBook(Book $book, UserHistoryRepository $userHistoryRepository): Response
    {
        return $this->render('user_history/by_book.html.twig', [
            'book' => $book,
            'user_histories' => $userHistoryRepository->findBy(['book' => $book], ['created_at' => 'DESC']),
            'title' => 'Historique du livre : ' . $book->getName()
        ]);
    }

    #[Route('/delete/{id}', name: 'app_user_history_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, UserHistory $userHistory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userHistory->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($userHistory);
            $entityManager->flush();

            $this->addFlash('success', 'Entrée d\'historique supprimée avec succès.');
        }

        return $this->redirectToRoute('app_user_history_admin');
    }

    #[Route('/api/track', name: 'app_user_history_api_track', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function apiTrackAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bookId = $request->getPayload()->getString('book_id');
        $actionType = $request->getPayload()->getString('action_type');

        if (!$bookId || !$actionType) {
            return $this->json(['success' => false, 'message' => 'Paramètres manquants'], 400);
        }

        $bookRepository = $entityManager->getRepository(Book::class);
        $book = $bookRepository->findOneBy(['volumeId' => $bookId]);

        if (!$book) {
            return $this->json(['success' => false, 'message' => 'Livre non trouvé'], 404);
        }

        $userHistory = new UserHistory();
        $userHistory->setUser($this->getUser());
        $userHistory->setBook($book);
        $userHistory->setActionType($actionType);

        $entityManager->persist($userHistory);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/clear', name: 'app_user_history_clear', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function clearHistory(Request $request, UserHistoryRepository $userHistoryRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('clear_history', $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_user_history_index');
        }

        $user = $this->getUser();
        $histories = $userHistoryRepository->findBy(['user' => $user]);

        foreach ($histories as $history) {
            $entityManager->remove($history);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Votre historique a été effacé avec succès.');

        return $this->redirectToRoute('app_user_history_index');
    }
}