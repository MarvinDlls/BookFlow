<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\Reservation;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    /**
     * Affiche le tableau de bord de l'administration.
     *
     * @Route("/admin", name="admin")
     * @return Response La réponse contenant la vue du tableau de bord.
     */
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
        ]);
    }

    /**
     * Configure le tableau de bord de l'administration.
     *
     * @return Dashboard L'objet de configuration du tableau de bord.
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('BookFlow - Admin');
    }

    /**
     * Configure les éléments du menu dans le tableau de bord.
     *
     * @return iterable La liste des éléments de menu à afficher dans le tableau de bord.
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Livres', 'fas fa-book', Book::class);
        yield MenuItem::linkToCrud('Réservations', 'fas fa-ticket', Reservation::class);
        yield MenuItem::linkToRoute('Accueil', 'fa fa-arrow-left', 'app_homepage');
    }
}
