<?php

namespace App\Controller;

use App\Entity\Book;
use App\Service\BookService;
use Knp\Component\Pager\PaginatorInterface;
use setasign\Fpdi\Fpdi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class BookController extends AbstractController
{
    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    #[Route('/books', name: 'app_books_list', methods: ['GET'])]
    public function index(Request $request): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 40);
            $tagId = $request->query->getInt('tag', 0); // Récupérer l'ID du tag plutôt que le nom
            $sortByPopularity = $request->query->getBoolean('popular', false);

            $books = $this->bookService->fetchAllBooks($page, $limit, $tagId, $sortByPopularity);
            $tags = $this->bookService->getAllTags(); // Récupérer tous les tags (objets complets)

            if (empty($books)) {
                $this->addFlash('warning', 'Aucun livre trouvé.');
            }

            return $this->render('book/books.html.twig', [
                'pagination' => $books,
                'title' => 'Liste des livres',
                'selectedTagId' => $tagId,
                'sortByPopularity' => $sortByPopularity,
                'tags' => $tags
            ]);
        } catch (\Exception $e) {
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
            $books = $this->bookService->searchBooks($query, $limit, $startIndex);

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
            $this->addFlash('error', 'Une erreur est survenue lors de la recherche.');
            return $this->render('book/books.html.twig', [
                'pagination' => [],
                'query' => $query,
                'title' => 'Recherche : ' . $query . ' - Erreur'
            ]);
        }
    }

    #[Route('/book/{id}', name: 'app_book_details', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $book = $this->bookService->fetchBookById($id);

        if (!$book) {
            $this->addFlash('error', 'Le livre demandé est introuvable.');
            return $this->redirectToRoute('app_books_list');
        }

        return $this->render('book/details.html.twig', [
            'book' => $book,
            'title' => $book->getName(),
            'tags' => $book->getTags(),
        ]);
    }

    #[Route('/book/preview/{id}', name: 'app_book_preview', methods: ['GET'])]
    public function preview(string $id): Response
    {
        // Récupérer le livre depuis la base de données
        $book = $this->bookService->fetchBookById($id);

        if (!$book) {
            throw $this->createNotFoundException('Livre non trouvé.');
        }

        // Chemin vers le fichier PDF
        $pdfPath = $this->getParameter('kernel.project_dir') . '/public' . $book->getPdfFile();

        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('Fichier PDF non trouvé.');
        }

        // Extraire les 10 premières pages du PDF
        $previewPdfPath = $this->extractFirstPages($pdfPath, 10, $book->getName());

        // Retourner le PDF en tant que réponse
        $response = new Response(file_get_contents($previewPdfPath));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'preview.pdf'
        ));

        // Supprimer le fichier temporaire après l'envoi
        unlink($previewPdfPath);

        return $response;
    }

    private function extractFirstPages(string $pdfPath, int $pageCount, string $bookTitle): string
    {
        // Créer une instance de FPDI (qui étend TCPDF)
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();

        // Définir le titre du PDF
        $pdf->SetTitle($bookTitle);

        // Désactiver l'impression et la copie du texte
        $pdf->SetProtection([], '', '', 1);

        // Ouvrir le fichier PDF original
        $pageCountOriginal = $pdf->setSourceFile($pdfPath);

        // Limiter le nombre de pages à extraire
        $pageCount = min($pageCount, $pageCountOriginal);

        // Ajouter chaque page au nouveau PDF
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            // Ajouter un filigrane après avoir ajouté chaque page
            $pdf->SetFont('helvetica', 'B', 50);
            $pdf->SetTextColor(200, 200, 200);
            $pdf->SetAlpha(0.6);
            $pdf->Rotate(50, 100, 200);
            $pdf->Text(75, 125, 'PRÉVISUALISATION');
            $pdf->Rotate(0);
            $pdf->SetAlpha(1);
        }

        // Sauvegarder le PDF temporaire
        $previewPdfPath = sys_get_temp_dir() . '/preview_' . uniqid() . '.pdf';
        $pdf->Output($previewPdfPath, 'F');

        return $previewPdfPath;
    }
}
