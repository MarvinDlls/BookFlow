<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\GoogleApiService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
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
    public function index(Request $request): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 40);
            $genre = $request->query->get('genre'); // Filtre par genre
            $sortByPopularity = $request->query->getBoolean('popular', false); // Tri par popularité

            $books = $this->googleApiService->fetchAllBooks($page, $limit, $genre, $sortByPopularity);

            if (empty($books)) {
                $this->addFlash('warning', 'Aucun livre trouvé.');
            }

            return $this->render('book/books.html.twig', [
                'pagination' => $books,
                'title' => 'Liste des livres',
                'selectedGenre' => $genre,
                'sortByPopularity' => $sortByPopularity
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
    public function search(Request $request, PaginatorInterface $paginator): Response
    {
        $query = trim($request->query->get('q', ''));
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 40);
        $startIndex = ($page - 1) * $limit;

        if ($query === '') {
            $this->addFlash('warning', 'Veuillez entrer un mot-clé pour rechercher un livre.');
            return $this->redirectToRoute('app_books_list');
        }

        try {
            // Utilisation de la méthode searchBooks avec les paramètres dans le bon ordre
            $books = $this->googleApiService->searchBooks($query, $limit, $startIndex);

            $pagination = $paginator->paginate(
                $books,
                $page,
                $limit
            );

            return $this->render('book/books.html.twig', [
                'pagination' => $pagination,
                'query' => $query,
                'title' => 'Recherche: ' . $query
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche de livres: ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la recherche.');

            return $this->render('book/books.html.twig', [
                'pagination' => [],
                'query' => $query,
                'title' => 'Recherche : ' . $query . ' - Erreur'
            ]);
        }
    }

    #[Route('/books/{id}', name: 'app_book_details', methods: ['GET'])]
    public function details(string $id, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        try {
            // 1. Récupérer les détails du livre depuis l'API Google
            $googleBook = $this->googleApiService->getBookById($id);

            if (!$googleBook) {
                throw new \Exception('Livre non trouvé dans Google Books API');
            }

            // 2. Créer un slug à partir du titre pour vérifier si le livre existe déjà
            $baseSlug = $slugger->slug($googleBook['title'] ?? 'livre')->lower();

            // 3. Vérifier si le livre existe déjà dans la base de données locale
            $localBook = $entityManager->getRepository(Book::class)->findOneBy(['slug' => $baseSlug]);

            // 4. Si le livre n'existe pas dans la base de données locale, l'ajouter
            if (!$localBook) {
                $localBook = new Book();
                $localBook->setName($googleBook['title'] ?? 'Sans titre');
                $localBook->setAuthor(implode(', ', $googleBook['authors'] ?? ['Auteur inconnu']));
                $localBook->setDescription($googleBook['description'] ?? 'Pas de description disponible');
                $localBook->setCover($googleBook['thumbnail'] ?? '');
                $localBook->setPopularity(0);

                // Générer un slug unique si nécessaire
                $slug = (string) $baseSlug;
                $counter = 1;
                while ($entityManager->getRepository(Book::class)->findOneBy(['slug' => $slug])) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $localBook->setSlug($slug);
                $localBook->setIsRestricted(false);
                $localBook->setCreatedAt(new \DateTimeImmutable());
                $localBook->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->persist($localBook);
                $entityManager->flush();
            }

            // 5. Passer à la fois les données Google ET l'ID local à la vue
            return $this->render('book/details.html.twig', [
                'book' => $googleBook,
                'localBook' => $localBook, // Passer l'objet complet
                'title' => $googleBook['title'] ?? 'Détails du livre'
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération du livre ID: {$id}. " . $e->getMessage());
            $this->addFlash('error', 'Le livre demandé est introuvable.');
            return $this->redirectToRoute('app_books_list');
        }
    }

    #[Route('/book/add', name: 'app_book_add', methods: ['POST'])]
    public function addBook(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository, SluggerInterface $slugger): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'], $data['author'], $data['description'], $data['cover'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données incomplètes'], Response::HTTP_BAD_REQUEST);
        }

        // Générer un slug à partir du nom si aucun n'est fourni
        $baseSlug = $data['slug'] ?? $slugger->slug($data['name'])->lower();
        $slug = (string) $baseSlug;

        // S'assurer que le slug est unique
        $counter = 1;
        while ($bookRepository->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $book = new Book();
        $book->setName($data['name']);
        $book->setAuthor($data['author']);
        $book->setDescription($data['description']);
        $book->setCover($data['cover']);
        $book->setPopularity($data['popularity'] ?? 0);
        $book->setSlug($slug); // Utiliser le slug unique
        $book->setIsRestricted(false);
        $book->setCreatedAt(new \DateTimeImmutable());
        $book->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($book);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Livre ajouté avec succès', 'slug' => $slug]);
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