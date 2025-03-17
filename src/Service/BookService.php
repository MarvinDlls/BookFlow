<?php

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class BookService
{
    private BookRepository $bookRepository;
    private PaginatorInterface $paginator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BookRepository $bookRepository,
        PaginatorInterface $paginator,
        EntityManagerInterface $entityManager
    ) {
        $this->bookRepository = $bookRepository;
        $this->paginator = $paginator;
        $this->entityManager = $entityManager;
    }

    /**
     * Récupère tous les livres.
     */
    public function fetchAllBooks(int $page = 1, int $limit = 40, ?string $genre = null, bool $sortByPopularity = false)
    {
        $queryBuilder = $this->bookRepository->createQueryBuilder('b');

        if ($genre) {
            $queryBuilder->andWhere('b.categories LIKE :genre')
                ->setParameter('genre', '%' . $genre . '%');
        }

        if ($sortByPopularity) {
            $queryBuilder->orderBy('b.popularity', 'DESC');
        }

        $query = $queryBuilder->getQuery();

        // Pagination
        return $this->paginator->paginate($query, $page, $limit);
    }

    /**
     * Recherche des livres par mot-clé.
     */
    public function searchBooks(string $query, int $maxResults = 10, ?int $startIndex = 0): array
    {
        return $this->bookRepository->search($query, $maxResults, $startIndex);
    }

    /**
     * Récupère les détails d'un livre par son ID.
     */
    public function fetchBookById(int $id): ?Book
    {
        $book = $this->bookRepository->find($id);
        if (!$book) {
            dump("Livre avec l'ID $id non trouvé.");
        }
        return $book;
    }


    /**
     * Récupère les livres les plus populaires.
     */
    public function fetchPopularBooks(int $maxResults = 5): array
    {
        return $this->bookRepository->findBy([], ['popularity' => 'DESC'], $maxResults);
    }

    /**
     * Ajoute un livre à la base de données.
     */
    public function addBook(array $data): Book
    {
        $book = new Book();
        $book->setName($data['name']);
        $book->setAuthor($data['author']);
        $book->setDescription($data['description']);
        $book->setCover($data['cover']);
        $book->setPopularity($data['popularity'] ?? 0);
        $book->setSlug($data['slug']);
        $book->setIsRestricted($data['is_restricted'] ?? false);
        $book->setCreatedAt(new \DateTimeImmutable());
        $book->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $book;
    }
}