<?php

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\TagRepository;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class BookService
{
    private BookRepository $bookRepository;
    private TagRepository $tagRepository;
    private PaginatorInterface $paginator;
    private EntityManagerInterface $entityManager;

    /**
     * Injecte les dépendances nécessaires pour la gestion des livres.
     *
     * @param BookRepository $bookRepository
     * @param TagRepository $tagRepository
     * @param PaginatorInterface $paginator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(BookRepository $bookRepository, TagRepository $tagRepository, PaginatorInterface $paginator, EntityManagerInterface $entityManager)
    {
        $this->bookRepository = $bookRepository;
        $this->tagRepository = $tagRepository;
        $this->paginator = $paginator;
        $this->entityManager = $entityManager;
    }

    /**
     * Récupère tous les livres.
     *
     * @param int $page
     * @param int $limit
     * @param int $tagId
     * @param bool $sortByPopularity
     * @return object
     */
    public function fetchAllBooks(int $page, int $limit, int $tagId, bool $sortByPopularity): object
    {
        $queryBuilder = $this->entityManager->getRepository(Book::class)->createQueryBuilder('b');

        if ($tagId > 0) {
            $queryBuilder
                ->innerJoin('b.tags', 't')
                ->andWhere('t.id = :tagId')
                ->setParameter('tagId', $tagId);
        }

        if ($sortByPopularity) {
            $queryBuilder->orderBy('b.popularity', 'DESC');
        } else {
            $queryBuilder->orderBy('b.name', 'ASC');
        }


        return $this->paginator->paginate($queryBuilder, $page, $limit);
    }

    /**
     * Recherche des livres par mot-clé.
     *
     * @param string $query
     * @param int $maxResults
     * @param int|null $startIndex
     * @return array
     */
    public function searchBooks(string $query, int $maxResults = 10, ?int $startIndex = 0): array
    {
        return $this->bookRepository->search($query, $maxResults, $startIndex);
    }

    /**
     * Récupère les détails d'un livre par son ID.
     *
     * @param int $id
     * @return Book|null
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
     *
     * @param int $maxResults
     * @return array
     */
    public function fetchPopularBooks(int $maxResults = 5): array
    {
        $books = $this->bookRepository->findBy([], ['popularity' => 'DESC'], $maxResults);
        return $books;
    }
    
    /**
     * Ajoute un livre à la base de données.
     *
     * @param array $data
     * @return Book
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

    /**
     * Sélectionne un Tag depuis la base de données.
     *
     * @return array
     */
    public function getAllTags(): array
    {
        return $this->tagRepository->findAll();
    }
}
