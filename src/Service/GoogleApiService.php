<?php

namespace App\Service;

use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class GoogleApiService
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $apiBaseUrl = 'https://www.googleapis.com/books/v1/volumes';
    private PaginatorInterface $paginator;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, PaginatorInterface $paginator)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->paginator = $paginator;
    }

    public function fetchAllBooks(int $page = 1, int $limit = 40, ?string $genre = null, bool $sortByPopularity = false)
    {
        $startIndex = ($page - 1) * $limit;

        $queryParams = [
            'q' => '*', // Recherche tous les livres
            'maxResults' => $limit,
            'startIndex' => $startIndex
        ];

        if ($genre) {
            $queryParams['q'] .= "+subject:{$genre}";
        }

        $books = $this->fetchBooksFromApi($queryParams);

        if ($sortByPopularity) {
            usort($books, fn($a, $b) => ($b['ratingsCount'] ?? 0) - ($a['ratingsCount'] ?? 0));
        }

        return $this->paginator->paginate(new \Doctrine\Common\Collections\ArrayCollection($books), $page, $limit);
    }

    public function searchBooks(string $query, int $maxResults = 40, ?string $genre = null, bool $sortByPopularity = false): array
    {
        if (empty($query)) {
            return [];
        }

        $queryParams = [
            'q' => $query,
            'maxResults' => $maxResults
        ];

        if ($genre) {
            $queryParams['q'] .= "+subject:{$genre}";
        }

        $books = $this->fetchBooksFromApi($queryParams);

        if ($sortByPopularity) {
            usort($books, fn($a, $b) => ($b['ratingsCount'] ?? 0) - ($a['ratingsCount'] ?? 0));
        }

        return $books;
    }

    public function getBookById(string $id): ?array
    {
        if (empty($id)) {
            return null;
        }

        $url = "{$this->apiBaseUrl}/{$id}";

        try {
            $response = $this->client->request('GET', $url, ['timeout' => 10]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Livre non trouvé');
            }

            $data = json_decode($response->getContent(), true);

            return $this->formatBookData($data);
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $e) {
            $this->logger->error("Erreur lors de la récupération du livre ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    private function fetchBooksFromApi(array $queryParams): array
    {
        $url = "{$this->apiBaseUrl}?" . http_build_query($queryParams);

        try {
            $response = $this->client->request('GET', $url, ['timeout' => 10]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Erreur API Google Books: " . $response->getStatusCode());
            }

            $data = json_decode($response->getContent(), true);

            if (!isset($data['items']) || empty($data['items'])) {
                return [];
            }

            return array_map([$this, 'formatBookData'], $data['items']);
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $e) {
            $this->logger->error("Erreur lors de la récupération des livres: " . $e->getMessage());
            return [];
        }
    }

    private function formatBookData(array $data): array
    {
        $volumeInfo = $data['volumeInfo'] ?? [];

        return [
            'id' => $data['id'] ?? null,
            'title' => $volumeInfo['title'] ?? 'Titre inconnu',
            'authors' => $volumeInfo['authors'] ?? ['Auteur inconnu'],
            'description' => $volumeInfo['description'] ?? 'Aucune description disponible',
            'publishedDate' => $volumeInfo['publishedDate'] ?? null,
            'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            'pageCount' => $volumeInfo['pageCount'] ?? null,
            'categories' => $volumeInfo['categories'] ?? [],
            'language' => $volumeInfo['language'] ?? null,
            'previewLink' => $volumeInfo['previewLink'] ?? null,
            'publisher' => $volumeInfo['publisher'] ?? null,
            'industryIdentifiers' => $volumeInfo['industryIdentifiers'] ?? []
        ];
    }
}
