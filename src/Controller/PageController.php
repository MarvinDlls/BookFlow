<?php

namespace App\Controller;

use App\Service\GoogleApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(GoogleApiService $googleApiService): Response
    {
        $popularBooks = $googleApiService->fetchPopularBooks(5);

        return $this->render('page/index.html.twig', [
            'controller_name' => 'PageController',
            'popularBooks' => $popularBooks
        ]);
    }
}
