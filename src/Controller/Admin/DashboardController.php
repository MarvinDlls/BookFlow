<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();
        return $this->render('admin/dashboard.html.twig', [
            // 'users' => $users,

        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('BookFlow - Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Users', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Books', 'fas fa-book', Book::class);
        yield MenuItem::linkToRoute('Back to website', 'fa fa-arrow-left', 'app_homepage');
    }
}
