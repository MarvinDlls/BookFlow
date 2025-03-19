<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller utilisé pour gérer les actions liées aux livres.
 */
final class BookController extends AbstractController
{
    private BookService $bookService;
    private EntityManagerInterface $entityManager;

    /**
     * Injecte les dépendances nécessaires pour gérer les livres.
     *
     * @param BookService $bookService Le service de gestion des livres
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     */
    public function __construct(BookService $bookService, EntityManagerInterface $entityManager)
    {
        $this->bookService = $bookService;
        $this->entityManager = $entityManager;
    }

    /**
     * Affiche la page d'accueil du site.
     *
     * @return Response La réponse HTTP
     */
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

    /**
     * Affiche la liste des livres disponibles avec pagination et filtres.
     *
     * @param Request $request La requête HTTP
     * @return Response La réponse HTTP
     */
    #[Route('/books', name: 'app_books_list', methods: ['GET'])]
    public function index(Request $request): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 40);
            $tagId = $request->query->getInt('tag', 0);
            $sortByPopularity = $request->query->getBoolean('popular', false);

            $books = $this->bookService->fetchAllBooks($page, $limit, $tagId, $sortByPopularity);
            $tags = $this->bookService->getAllTags();

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

    /**
     * Recherche des livres par mot-clé.
     *
     * @param Request $request La requête HTTP
     * @param PaginatorInterface $paginator Le service de pagination
     * @return Response La réponse HTTP
     */
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

            $tags = $this->bookService->getAllTags();

            return $this->render('book/books.html.twig', [
                'pagination' => $pagination,
                'query' => $query,
                'title' => 'Recherche: ' . $query,
                'tags' => $tags,
                'selectedTagId' => 0,
                'sortByPopularity' => false
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la recherche.');
            return $this->render('book/books.html.twig', [
                'pagination' => [],
                'query' => $query,
                'title' => 'Recherche : ' . $query . ' - Erreur',
                'tags' => []
            ]);
        }
    }


    /**
     * Affiche les détails d'un livre.
     *
     * @param int $id L'ID du livre
     * @return Response La réponse HTTP
     */
    #[Route('/book/{id}', name: 'app_book_details', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $book = $this->bookService->fetchBookById($id);

        if (!$book) {
            $this->addFlash('error', 'Le livre demandé est introuvable.');
            return $this->redirectToRoute('app_books_list');
        }

        $reservation = $this->entityManager->getRepository(Reservation::class)
            ->findOneBy([
                'user' => $this->getUser(),
                'book' => $book,
                'status' => 'reserve'
            ]);

        $canRead = false;
        if ($reservation && $reservation->getExpirationDate() > new \DateTime()) {
            $canRead = true;
        }

        return $this->render('book/details.html.twig', [
            'book' => $book,
            'title' => $book->getName(),
            'tags' => $book->getTags(),
            'canRead' => $canRead,
        ]);
    }

    /**
     * Prévisualiser les 10 premières pages d'un livre en PDF avec un filigrane.
     *
     * @param string $id L'ID du livre
     * @return Response La réponse HTTP
     */
    #[Route('/book/preview/{id}', name: 'app_book_preview', methods: ['GET'])]
    public function preview(string $id): Response
    {
        $book = $this->bookService->fetchBookById($id);

        if (!$book) {
            throw $this->createNotFoundException('Livre non trouvé.');
        }

        $pdfPath = $this->getParameter('kernel.project_dir') . '/public' . $book->getPdfFile();

        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('Fichier PDF non trouvé.');
        }

        $previewPdfPath = $this->extractFirstPages($pdfPath, 10, $book->getName());

        $response = new Response(file_get_contents($previewPdfPath));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'preview.pdf'
        ));

        unlink($previewPdfPath);

        return $response;
    }

    /**
     * Visionnage d'un livre.
     *
     * @param int $id L'ID du livre
     * @param Security $security Le service de sécurité
     * @param ReservationRepository $reservationRepository Le dépôt des réservations
     * @return Response La réponse HTTP
     */
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

    /**
     * Extrait les premières pages d'un fichier PDF avec un filigrane.
     *
     * @param string $pdfPath Le chemin du fichier PDF
     * @param int $pageCount Le nombre de pages à extraire
     * @param string $bookTitle Le titre du livre
     * @return string Le chemin du fichier PDF extrait
     */
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

        $previewPdfPath = sys_get_temp_dir() . '/preview_' . uniqid() . '.pdf';
        $pdf->Output($previewPdfPath, 'F');

        return $previewPdfPath;
    }
}
