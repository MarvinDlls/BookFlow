<?php

namespace App\Controller;

    use App\Form\UserType;
    use App\Service\UploaderService;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route(path: '/profil', name: 'app_user_')]
final class UserController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route(name: 'profil', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UploaderService $us,
        UserPasswordHasherInterface $uphi
    ): Response {

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

                // Redirection avec flash message
                $this->addFlash('success', 'Votre profil à été mis à jour');
            } else {
                $this->addFlash('error', 'Une erreur est survenue');
            }

            return $this->redirectToRoute('app_user_profil');
        }

        if (!$user->isVerified()) {
            $this->addFlash('warning', 'Validez votre email !');
        }

//        if ($user->getSubscription() !== null) {
//            $subs = $user->getSubscription();
//            $now = new \DateTime();
//
//            $remove = false;
//
//            if (!$subs->isActive()) {
//                $dateMax = (clone $subs->getCreatedAt())->modify('+20 minutes');
//                $remove = $now > $dateMax;
//            } else {
//                $subsEnd = (clone $subs->getUpdatedAt())->modify('+1 month');
//
//                $remove = $now > $subsEnd;
//            }
//
//            if ($remove) {
//                $this->em->remove($subs);
//                $this->em->flush();
//            }
//        }

        return $this->render('user/index.html.twig', [
            'userForm' => $form,
            'user' => $user
        ]);
    }
}
