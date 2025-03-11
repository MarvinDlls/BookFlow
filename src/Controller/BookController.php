<?php

namespace App\Controller;

use App\Service\GoogleApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BookController extends AbstractController
{
    private GoogleApiService $googleApiService;

    public function __construct(GoogleApiService $googleApiService)
    {
        $this->googleApiService = $googleApiService;
    }

    #[Route('/books', name: 'app_books_list')]
    public function index(): Response
    {
        try {
            $books = $this->googleApiService->fetchAllBooks();
            
            return $this->render('book/books.html.twig', [
                'books' => $books,
                'title' => 'Liste des livres'
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la récupération des livres: ' . $e->getMessage());
            return $this->render('book/index.html.twig', [
                'books' => [],
                'title' => 'Liste des livres - Erreur'
            ]);
        }
    }

    #[Route('/books/search', name: 'app_books_search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->redirectToRoute('app_books_list');
        }
        
        try {
            $books = $this->googleApiService->searchBooks($query);
            
            return $this->render('book/search.html.twig', [
                'books' => $books,
                'query' => $query,
                'title' => 'Recherche: ' . $query
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la recherche: ' . $e->getMessage());
            return $this->render('book/search.html.twig', [
                'books' => [],
                'query' => $query,
                'title' => 'Recherche: ' . $query . ' - Erreur'
            ]);
        }
    }

    #[Route('/books/{id}', name: 'app_book_details')]
    public function details(string $id): Response
    {
        try {
            $book = $this->googleApiService->getBookById($id);
            
            if (!$book) {
                throw new \Exception('Livre non trouvé');
            }
            
            return $this->render('book/details.html.twig', [
                'book' => $book,
                'title' => $book['title'] ?? 'Détails du livre'
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            return $this->redirectToRoute('app_books_list');
        }
    }

    #[Route("/api/books/{id}", name:"api_book_details", methods:["GET"])]
    public function apiBookDetails(string $id): JsonResponse
{
    try {
        $book = $this->googleApiService->getBookById($id);
        
        if (!$book) {
            return $this->json(['error' => 'Livre non trouvé'], 404);
        }
        
        return $this->json($book);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 500);
    }
}
}