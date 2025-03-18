<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use setasign\Fpdi\Fpdi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class BookController extends AbstractController
{
    private BookService $bookService;
    private EntityManagerInterface $entityManager;

    public function __construct(BookService $bookService, EntityManagerInterface $entityManager)
    {
        $this->bookService = $bookService;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_homepage', methods: ['GET'])]
    public function homepage(): Response
    {
        try {
            $popularBooks = $this->bookService->fetchPopularBooks(5);

            return $this->render('page/index.html.twig', [
                'popularBooks' => $popularBooks,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la récupération des livres populaires.');
            return $this->render('page/index.html.twig', [
                'popularBooks' => [],
            ]);
        }
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
            $pagination = $paginator->paginate($books, $page, $limit);

            // Récupérer les tags pour éviter l'erreur dans le template
            $tags = $this->bookService->getAllTags();

            return $this->render('book/books.html.twig', [
                'pagination' => $pagination,
                'query' => $query,
                'title' => 'Recherche: ' . $query,
                'tags' => $tags, // Ajout de la variable tags
                'selectedTagId' => 0, // Aucune sélection active par défaut
                'sortByPopularity' => false // Pas de tri par popularité par défaut
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la recherche.');
            return $this->render('book/books.html.twig', [
                'pagination' => [],
                'query' => $query,
                'title' => 'Recherche : ' . $query . ' - Erreur',
                'tags' => [] // Passer un tableau vide pour éviter l'erreur
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

        // Vérifier si l'utilisateur a une réservation active pour ce livre
        $reservation = $this->entityManager->getRepository(Reservation::class)
            ->findOneBy([
                'user' => $this->getUser(),
                'book' => $book,
                'status' => 'reserve'
            ]);

        // Vérifier que la réservation est active et que la date d'expiration n'est pas passée
        $canRead = false;
        if ($reservation && $reservation->getExpirationDate() > new \DateTime()) {
            $canRead = true;
        }

        // Passer la variable canRead au template
        return $this->render('book/details.html.twig', [
            'book' => $book,
            'title' => $book->getName(),
            'tags' => $book->getTags(),
            'canRead' => $canRead,  // Passer la variable à la vue
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

    #[Route('/book/download/{id}', name: 'app_book_download', methods: ['GET'])]
    public function download(int $id, Security $security, ReservationRepository $reservationRepository): Response
    {
        $user = $security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour télécharger ce livre.');
        }

        $book = $this->bookService->fetchBookById($id);

        if (!$book) {
            throw $this->createNotFoundException('Livre non trouvé.');
        }

        $reservation = $reservationRepository->findActiveReservation($user, $book);

        if (!$reservation) {
            $this->addFlash('warning', 'Vous n\'avez pas de réservation active pour ce livre. Veuillez réserver ce livre avant de le lire.');
            return $this->redirectToRoute('app_book_details', ['id' => $id]);
        }

        $pdfPath = $this->getParameter('kernel.project_dir') . '/public' . $book->getPdfFile();

        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('Fichier PDF non trouvé.');
        }

        $response = new StreamedResponse(function () use ($pdfPath) {
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);

            $pdf->SetProtection(['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'], '', '', 1);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            $pdf->Output();
        });

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'book.pdf'
        ));

        return $response;
    }


    private function extractFirstPages(string $pdfPath, int $pageCount, string $bookTitle): string
    {
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();

        $pdf->SetTitle($bookTitle);

        $pdf->SetProtection(['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'], '', '', 1);

        $pageCountOriginal = $pdf->setSourceFile($pdfPath);

        $pageCount = min($pageCount, $pageCountOriginal);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            $pdf->SetFont('helvetica', 'B', 50);
            $pdf->SetTextColor(200, 200, 200);
            $pdf->SetAlpha(0.6);

            $watermarkText = 'PRÉVISUALISATION';

            $horizontalCount = 1;
            $verticalCount = 4;

            $xSpacing = $size['width'] / $horizontalCount;
            $ySpacing = $size['height'] / $verticalCount;

            for ($i = 0; $i < $horizontalCount; $i++) {
                for ($j = 0; $j < $verticalCount; $j++) {
                    $x = $i * $xSpacing + 25;
                    $y = $j * $ySpacing + 25;

                    $pdf->StartTransform();
                    $pdf->Rotate(35, $x, $y);
                    $pdf->Text($x + 5, $y - 5, $watermarkText);
                    $pdf->StopTransform();
                }
            }

            $pdf->SetAlpha(1);
        }

        // Sauvegarder le PDF temporaire
        $previewPdfPath = sys_get_temp_dir() . '/preview_' . uniqid() . '.pdf';
        $pdf->Output($previewPdfPath, 'F');

        return $previewPdfPath;
    }
}
