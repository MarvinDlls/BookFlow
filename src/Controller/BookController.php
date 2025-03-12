<?php

namespace App\Controller;

use App\Service\GoogleApiService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BookController extends AbstractController
{
    private GoogleApiService $googleApiService;
    private LoggerInterface $logger;

    public function __construct(GoogleApiService $googleApiService, LoggerInterface $logger)
    {
        $this->googleApiService = $googleApiService;
        $this->logger = $logger;
    }

    #[Route('/books', name: 'app_books_list', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $books = $this->googleApiService->fetchAllBooks();

            return $this->render('book/books.html.twig', [
                'books' => $books,
                'title' => 'Liste des livres'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des livres: ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la récupération des livres.');

            return $this->render('book/index.html.twig', [
                'books' => [],
                'title' => 'Liste des livres - Erreur'
            ]);
        }
    }

    #[Route('/books/search', name: 'app_books_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $query = trim($request->query->get('q', ''));

        if ($query === '') {
            $this->addFlash('warning', 'Veuillez entrer un mot-clé pour rechercher un livre.');
            return $this->redirectToRoute('app_books_list');
        }

        try {
            $books = $this->googleApiService->searchBooks($query);

            return $this->render('book/books.html.twig', [
                'books' => $books,
                'query' => $query,
                'title' => 'Recherche: ' . $query
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche de livres: ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la recherche.');

            return $this->render('book/books.html.twig', [
                'books' => [],
                'query' => $query,
                'title' => 'Recherche: ' . $query . ' - Erreur'
            ]);
        }
    }

    #[Route('/books/{id}', name: 'app_book_details', methods: ['GET'])]
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
            $this->logger->error("Erreur lors de la récupération du livre ID: {$id}. " . $e->getMessage());
            $this->addFlash('error', 'Le livre demandé est introuvable.');
            return $this->redirectToRoute('app_books_list');
        }
    }

    #[Route('/api/books/{id}', name: 'api_book_details', methods: ['GET'])]
    public function apiBookDetails(string $id): JsonResponse
    {
        try {
            $book = $this->googleApiService->getBookById($id);

            if (!$book) {
                return $this->json(['error' => 'Livre non trouvé'], 404);
            }

            return $this->json($book);
        } catch (\Exception $e) {
            $this->logger->error("Erreur API pour le livre ID: {$id}. " . $e->getMessage());
            return $this->json(['error' => 'Une erreur interne est survenue.'], 500);
        }
    }



    // Dans ton contrôleur
    #[Route('/book/preview/{id}', name: 'app_book_preview', methods: ['GET'])]
    public function preview(string $id, HttpClientInterface $httpClient): Response
    {
        // Construire l'URL de l'API Google Books
        $url = 'https://www.googleapis.com/books/v1/volumes/' . $id;

        // Envoyer la requête à l'API
        $response = $httpClient->request('GET', $url);

        // Récupérer les données JSON
        $data = $response->toArray();

        // Vérifier si le livre existe
        if (!isset($data['volumeInfo'])) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        // Extraire les informations du livre
        $book = [
            'title' => $data['volumeInfo']['title'] ?? 'Titre inconnu',
            'authors' => $data['volumeInfo']['authors'] ?? ['Auteur inconnu'],
            'pageCount' => $data['volumeInfo']['pageCount'] ?? 'Non spécifié',
            'description' => $data['volumeInfo']['description'] ?? 'Pas de description',
            'thumbnail' => $data['volumeInfo']['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/150',
            'previewLink' => $data['volumeInfo']['previewLink'] ?? '',
            'id' => $id
        ];

        // Passer les données à la vue
        return $this->render('book/preview.html.twig', [
            'book' => $book
        ]);
    }
}