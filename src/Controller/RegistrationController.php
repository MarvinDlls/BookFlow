<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Controller utilisé pour gérer l'inscription des utilisateurs.
 */
class RegistrationController extends AbstractController
{
    /**
     * Crée une nouvelle instance de RegistrationController.
     *
     * @param EmailVerifier $emailVerifier
     */
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    /**
     * Affiche le formulaire d'inscription.
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $entityManager
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_homepage');
        }
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('contact@bookflow.com', 'BookFlow'))
                    ->to((string) $user->getEmail())
                    ->subject('BookFlow - Vérification de votre adresse email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Vérifie l'adresse email de l'utilisateur. (Prochainement)
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param UserRepository $userRepository
     */
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Votre adresse email a bien été vérifiée. Merci.');

        return $this->redirectToRoute('app_homepage');
    }
}
