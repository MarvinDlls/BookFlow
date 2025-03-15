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
    private string $apiKey;
    
    private const SHELF_IDS = [
        'favorites' => 0,
        'purchased' => 1,
        'to_read' => 2,
        'reading' => 3,
        'read' => 4,
        'reviewed' => 5,
        'recently_viewed' => 6, 
        'my_ebooks' => 7
    ];

    public function __construct(
        HttpClientInterface $client, 
        LoggerInterface $logger, 
        PaginatorInterface $paginator,
        string $apiKey = ''
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->paginator = $paginator;
        $this->apiKey = $apiKey;
    }
    
    public function fetchAllBooks(int $page = 1, int $limit = 40, ?string $genre = null, bool $sortByPopularity = false)
    {
        $startIndex = ($page - 1) * $limit;

        $queryParams = [
            'q' => '*',
            'maxResults' => $limit,
            'startIndex' => $startIndex
        ];

        if ($this->apiKey) {
            $queryParams['key'] = $this->apiKey;
        }

        if ($genre) {
            $queryParams['q'] .= "+subject:{$genre}";
        }

        // Récupération des livres depuis l'API Google Books
        $books = $this->fetchBooksFromApi($queryParams);

        if ($sortByPopularity) {
            usort($books, fn($a, $b) => ($b['ratingsCount'] ?? 0) - ($a['ratingsCount'] ?? 0));
        }

        return $this->paginator->paginate(new \Doctrine\Common\Collections\ArrayCollection($books), $page, $limit);
    }

    // Renommé cette méthode pour éviter le conflit avec la méthode searchBooks ci-dessous
    public function findBooks(string $query, int $maxResults = 40, ?string $genre = null, bool $sortByPopularity = false): array
    {
        if (empty($query)) {
            return [];
        }

        $queryParams = [
            'q' => $query,
            'maxResults' => $maxResults
        ];

        if ($this->apiKey) {
            $queryParams['key'] = $this->apiKey;
        }

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
        $options = ['timeout' => 10];
        
        if ($this->apiKey) {
            $options['query'] = ['key' => $this->apiKey];
        }

        try {
            $response = $this->client->request('GET', $url, $options);

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

            $filteredBooks = array_filter($data['items'], function ($book) {
                $volumeInfo = $book['volumeInfo'] ?? [];

                $description = $volumeInfo['description'] ?? null;
                $thumbnail = $volumeInfo['imageLinks']['thumbnail'] ?? null;
                $previewLink = $volumeInfo['previewLink'] ?? null;

                // Vérifier si les valeurs sont réellement utiles
                $hasValidDescription = $description && $description !== 'Pas de description';
                $hasValidThumbnail = $thumbnail && $thumbnail !== 'https://via.placeholder.com/150';
                $hasValidPreview = $previewLink && !str_contains($previewLink, 'output=embed');

                return $hasValidDescription && $hasValidThumbnail && $hasValidPreview;
            });

            return array_map([$this, 'formatBookData'], $filteredBooks);
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
            'industryIdentifiers' => $volumeInfo['industryIdentifiers'] ?? [],
            'ratingsCount' => $volumeInfo['ratingsCount'] ?? 0
        ];
    }

    // public function getBooksFromShelf(string $userId, string $shelfName, int $maxResults = 10, int $startIndex = 0): array
    // {
    //     if (!array_key_exists($shelfName, self::SHELF_IDS)) {
    //         throw new \Exception("Étagère inconnue: $shelfName");
    //     }

    //     $shelfId = self::SHELF_IDS[$shelfName];
    //     $url = "https://www.googleapis.com/books/v1/users/{$userId}/bookshelves/{$shelfId}/volumes";
        
    //     $queryParams = [
    //         'maxResults' => $maxResults,
    //         'startIndex' => $startIndex
    //     ];
        
    //     if ($this->apiKey) {
    //         $queryParams['key'] = $this->apiKey;
    //     }
        
    //     try {
    //         $response = $this->client->request('GET', $url, [
    //             'query' => $queryParams,
    //             'timeout' => 10
    //         ]);

    //         if ($response->getStatusCode() !== 200) {
    //             throw new \Exception("Erreur lors de la récupération des livres de l'étagère: " . $response->getStatusCode());
    //         }

    //         $data = json_decode($response->getContent(), true);
            
    //         if (!isset($data['items'])) {
    //             return [];
    //         }
            
    //         return array_map([$this, 'formatBookData'], $data['items']);
            
    //     } catch (\Exception $e) {
    //         $this->logger->error("Erreur lors de la récupération des livres de l'étagère: " . $e->getMessage());
    //         return [];
    //     }
    // }

    // public function addBookToShelf(string $userId, string $shelfName, string $volumeId): bool
    // {
    //     if (!array_key_exists($shelfName, self::SHELF_IDS)) {
    //         throw new \Exception("Étagère inconnue: $shelfName");
    //     }

    //     $shelfId = self::SHELF_IDS[$shelfName];
    //     $url = "https://www.googleapis.com/books/v1/users/{$userId}/bookshelves/{$shelfId}/addVolume";
        
    //     $queryParams = [
    //         'volumeId' => $volumeId
    //     ];
        
    //     if ($this->apiKey) {
    //         $queryParams['key'] = $this->apiKey;
    //     }
        
    //     try {
    //         $response = $this->client->request('POST', $url, [
    //             'query' => $queryParams,
    //             'timeout' => 10
    //         ]);

    //         return $response->getStatusCode() === 204;
            
    //     } catch (\Exception $e) {
    //         $this->logger->error("Erreur lors de l'ajout du livre à l'étagère: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // public function removeBookFromShelf(string $userId, string $shelfName, string $volumeId): bool
    // {
    //     if (!array_key_exists($shelfName, self::SHELF_IDS)) {
    //         throw new \Exception("Étagère inconnue: $shelfName");
    //     }

    //     $shelfId = self::SHELF_IDS[$shelfName];
    //     $url = "https://www.googleapis.com/books/v1/users/{$userId}/bookshelves/{$shelfId}/removeVolume";
        
    //     $queryParams = [
    //         'volumeId' => $volumeId
    //     ];
        
    //     if ($this->apiKey) {
    //         $queryParams['key'] = $this->apiKey;
    //     }
        
    //     try {
    //         $response = $this->client->request('POST', $url, [
    //             'query' => $queryParams,
    //             'timeout' => 10
    //         ]);

    //         return $response->getStatusCode() === 204;
            
    //     } catch (\Exception $e) {
    //         $this->logger->error("Erreur lors du retrait du livre de l'étagère: " . $e->getMessage());
    //         return false;
    //     }
    // }

    public function searchBooks(string $query, int $maxResults = 10, ?int $startIndex = 0): array
    {
        if (empty($query)) {
            return [];
        }
        
        $url = "{$this->apiBaseUrl}";
        
        $queryParams = [
            'q' => $query,
            'maxResults' => $maxResults,
            'startIndex' => $startIndex ?? 0
        ];
        
        if ($this->apiKey) {
            $queryParams['key'] = $this->apiKey;
        }
        
        try {
            $response = $this->client->request('GET', $url, [
                'query' => $queryParams,
                'timeout' => 10
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Erreur lors de la recherche de livres: " . $response->getStatusCode());
            }

            $data = json_decode($response->getContent(), true);
            
            if (!isset($data['items'])) {
                return [];
            }
            
            return array_map([$this, 'formatBookData'], $data['items']);
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la recherche de livres: " . $e->getMessage());
            return [];
        }
    }

    public function getBookDetails(string $volumeId): ?array
    {
        if (empty($volumeId)) {
            return null;
        }
        
        return $this->getBookById($volumeId);
    }
}