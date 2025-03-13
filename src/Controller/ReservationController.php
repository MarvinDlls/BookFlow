<?php

namespace App\Controller;

use App\Service\GoogleApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function getBooksFromShelf(string $userId, string $shelfName, Request $request): JsonResponse
    {
        $maxResults = $request->query->getInt('maxResults', 10);
        $startIndex = $request->query->getInt('startIndex', 0);

        try {
            $books = $this->googleApiService->getBooksFromShelf($userId, $shelfName, $maxResults, $startIndex);
            return $this->json($books);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route("/{userId}/shelf/{shelfName}/add", name: "add_book_to_shelf", methods: ['POST'])]
    public function addBookToShelf(string $userId, string $shelfName, Request $request): JsonResponse
    {
        $volumeId = $request->request->get('volumeId');
        
        if (!$volumeId) {
            return $this->json(['error' => 'volumeId is required'], 400);
        }

        try {
            $success = $this->googleApiService->addBookToShelf($userId, $shelfName, $volumeId);
            return $this->json(['success' => $success]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route("/{userId}/shelf/{shelfName}/remove", name: "remove_book_from_shelf", methods: ['POST'])]
    public function removeBookFromShelf(string $userId, string $shelfName, Request $request): JsonResponse
    {
        $volumeId = $request->request->get('volumeId');

        if (!$volumeId) {
            return $this->json(['error' => 'volumeId is required'], 400);
        }

        try {
            $success = $this->googleApiService->removeBookFromShelf($userId, $shelfName, $volumeId);
            return $this->json(['success' => $success]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}