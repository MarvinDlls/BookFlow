<?php

namespace App\Controller;

use App\Form\UserType;
use App\Repository\ReservationRepository;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Controller utilisé pour la gestion des utilisateurs.
 */
#[Route(path: '/profil', name: 'app_user_')]
final class UserController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route(name: 'profil', methods: ['GET', 'POST'])]
    public function index(Request $request, UploaderService $us, UserPasswordHasherInterface $uphi, ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pwd = $uphi->isPasswordValid($user, $form->get('password')->getData());
            if ($pwd) {
                $image = $form->get('image')->getData();
                if ($image != null) {
                    $user->setImage(
                        $us->uploadFile($image, $user->getImage())
                    );
                }

                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash('success', 'Votre profil à été mis à jour');
            } else {
                $this->addFlash('error', 'Une erreur est survenue');
            }

            return $this->redirectToRoute('app_user_profil');
        }

        if (!$user->isVerified()) {
            $this->addFlash('warning', 'Validez votre email !');
        }

        $reservations = $reservationRepository->findLatestReservations(5);

        return $this->render('user/index.html.twig', [
            'userForm' => $form,
            'user' => $user,
            'reservations' => $reservations
        ]);
    }
}
