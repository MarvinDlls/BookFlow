<?php

namespace App\Controller;

use App\Service\GoogleApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/library')]
class ReservationController extends AbstractController
{
    private GoogleApiService $googleApiService;

    public function __construct(GoogleApiService $googleApiService)
    {
        $this->googleApiService = $googleApiService;
    }

    #[Route('/{userId}/shelf/{shelfName}', name: 'get_books_from_shelf', methods: ['GET'])]
    public function getBooksFromShelf(string $userId, string $shelfName, Request $request): Response
    {
        $maxResults = $request->query->getInt('maxResults', 5);
        $startIndex = $request->query->getInt('startIndex', 0);

        try {
            $books = $this->googleApiService->getBooksFromShelf($userId, $shelfName, $maxResults, $startIndex);
            
            if ($request->getPreferredFormat() === 'json') {
                return $this->json($books);
            }
            
            return $this->render('library/books_shelf.html.twig', [
                'books' => $books['items'] ?? [],
                'totalItems' => $books['totalItems'] ?? 0,
                'userId' => $userId,
                'shelfName' => $shelfName,
                'maxResults' => $maxResults,
                'startIndex' => $startIndex
            ]);
        } catch (\Exception $e) {
            if ($request->getPreferredFormat() === 'json') {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            
            return $this->render('library/error.html.twig', [
                'error' => $e->getMessage()
            ], new Response('', 400));
        }
    }

    #[Route("/{userId}/shelf/{shelfName}/add", name: "add_book_to_shelf", methods: ['GET', 'POST'])]
    public function addBookToShelf(string $userId, string $shelfName, Request $request): Response
    {
        if ($request->isMethod('GET')) {
            return $this->render('library/add_book.html.twig', [
                'userId' => $userId,
                'shelfName' => $shelfName
            ]);
        }
        
        $volumeId = $request->request->get('volumeId');
        
        if (!$volumeId) {
            if ($request->getPreferredFormat() === 'json') {
                return $this->json(['error' => 'volumeId is required'], 400);
            }
            
            return $this->render('library/add_book.html.twig', [
                'userId' => $userId,
                'shelfName' => $shelfName,
                'error' => 'L\'ID du volume est requis'
            ]);
        }

        try {
            $success = $this->googleApiService->addBookToShelf($userId, $shelfName, $volumeId);
            
            if ($request->getPreferredFormat() === 'json') {
                return $this->json(['success' => $success]);
            }
            
            $this->addFlash('success', 'Le livre a été ajouté avec succès !');
            return $this->redirectToRoute('get_books_from_shelf', [
                'userId' => $userId,
                'shelfName' => $shelfName
            ]);
        } catch (\Exception $e) {
            if ($request->getPreferredFormat() === 'json') {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            
            return $this->render('library/add_book.html.twig', [
                'userId' => $userId,
                'shelfName' => $shelfName,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route("/{userId}/shelf/{shelfName}/remove/{volumeId}", name: "confirm_remove_book", methods: ['GET'])]
    public function confirmRemoveBook(string $userId, string $shelfName, string $volumeId): Response
    {
        try {
            $bookDetails = $this->googleApiService->getBookDetails($volumeId);
            
            return $this->render('library/remove_book_confirm.html.twig', [
                'userId' => $userId,
                'shelfName' => $shelfName,
                'volumeId' => $volumeId,
                'book' => $bookDetails
            ]);
        } catch (\Exception $e) {
            return $this->render('library/remove_book_confirm.html.twig', [
                'userId' => $userId,
                'shelfName' => $shelfName,
                'volumeId' => $volumeId,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route("/{userId}/shelf/{shelfName}/remove", name: "remove_book_from_shelf", methods: ['POST'])]
    public function removeBookFromShelf(string $userId, string $shelfName, Request $request): Response
    {
        $volumeId = $request->request->get('volumeId');

        if (!$volumeId) {
            if ($request->getPreferredFormat() === 'json') {
                return $this->json(['error' => 'volumeId is required'], 400);
            }
            
            $this->addFlash('error', 'L\'ID du volume est requis');
            return $this->redirectToRoute('get_books_from_shelf', [
                'userId' => $userId, 
                'shelfName' => $shelfName
            ]);
        }

        try {
            $success = $this->googleApiService->removeBookFromShelf($userId, $shelfName, $volumeId);
            
            if ($request->getPreferredFormat() === 'json') {
                return $this->json(['success' => $success]);
            }
            
            $this->addFlash('success', 'Le livre a été retiré avec succès !');
            return $this->redirectToRoute('get_books_from_shelf', [
                'userId' => $userId,
                'shelfName' => $shelfName
            ]);
        } catch (\Exception $e) {
            if ($request->getPreferredFormat() === 'json') {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('get_books_from_shelf', [
                'userId' => $userId, 
                'shelfName' => $shelfName
            ]);
        }
    }
}