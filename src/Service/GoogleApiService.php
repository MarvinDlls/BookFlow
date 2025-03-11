<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleApiService
{
    private HttpClientInterface $client;
    private string $apiBaseUrl = 'https://www.googleapis.com/books/v1/volumes';
    
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }
    
    public function fetchAllBooks(): array
    {
        // L'URL de base de l'API Google Books
        $queryParams = [
            'q' => '*', // Recherche tous les livres
            'maxResults' => 40, // Nombre de résultats par page
            'startIndex' => 0 // Index de départ
        ];
        
        // Construction de l'URL avec les paramètres
        $url = $this->apiBaseUrl . '?' . http_build_query($queryParams);
        
        // Exécution de la requête
        $response = $this->client->request('GET', $url);
        
        // Vérification du statut de la réponse
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erreur lors de la récupération des livres: ' . $response->getStatusCode());
        }
        
        // Décodage de la réponse JSON
        $data = json_decode($response->getContent(), true);
        
        // Vérification que des livres ont été trouvés
        if (!isset($data['items']) || empty($data['items'])) {
            return [];
        }
        
        return $this->formatBooksData($data['items']);
    }
    
    public function searchBooks(string $query): array
    {
        if (empty($query)) {
            return [];
        }
        
        $queryParams = [
            'q' => $query,
            'maxResults' => 40
        ];
        
        $url = $this->apiBaseUrl . '?' . http_build_query($queryParams);
        $response = $this->client->request('GET', $url);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erreur lors de la recherche: ' . $response->getStatusCode());
        }
        
        $data = json_decode($response->getContent(), true);
        
        if (!isset($data['items']) || empty($data['items'])) {
            return [];
        }
        
        return $this->formatBooksData($data['items']);
    }
    
    public function getBookById(string $id): ?array
    {
        if (empty($id)) {
            return null;
        }
        
        $url = $this->apiBaseUrl . '/' . $id;
        $response = $this->client->request('GET', $url);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Livre non trouvé');
        }
        
        $data = json_decode($response->getContent(), true);
        
        if (!$data) {
            return null;
        }
        
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
    
    private function formatBooksData(array $items): array
    {
        $books = [];
        foreach ($items as $item) {
            $volumeInfo = $item['volumeInfo'] ?? [];
            
            $books[] = [
                'id' => $item['id'] ?? null,
                'title' => $volumeInfo['title'] ?? 'Titre inconnu',
                'authors' => $volumeInfo['authors'] ?? ['Auteur inconnu'],
                'description' => $volumeInfo['description'] ?? 'Aucune description disponible',
                'publishedDate' => $volumeInfo['publishedDate'] ?? null,
                'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
                'pageCount' => $volumeInfo['pageCount'] ?? null,
                'categories' => $volumeInfo['categories'] ?? [],
                'language' => $volumeInfo['language'] ?? null,
                'previewLink' => $volumeInfo['previewLink'] ?? null
            ];
        }
        
        return $books;
    }
}