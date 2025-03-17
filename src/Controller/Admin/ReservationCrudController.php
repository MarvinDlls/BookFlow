<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('user.username', 'Utilisateur'),
            TextField::new('book.name', 'Titre'),
            TextField::new('book.author', 'Auteur'),
            ChoiceField::new('status')
                ->setChoices([
                    'En attente' => 'en_attente',
                    'Active' => 'active',
                    'Annulé' => 'annule',
                    'Terminé' => 'termine',
                    'Prolongé' => 'prolonge',
                    'Prolongation' => 'prolongation',
                ])
                ->renderAsBadges()
                ->setHelp('Choisissez le statut de la réservation'),
            DateTimeField::new('createdAt', 'Ajouté le')->setSortable(true),
            DateTimeField::new('updatedAt', 'Modifié le'),
            DateTimeField::new('reservation_date', 'Reservé le'),
            DateTimeField::new('expiration_date', 'Reservé jusqu\'au'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', '📚 Gestion des réservations')
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setSearchFields(['id', 'user.username', 'book.name', 'book.author', 'status', 'createdAt', 'updatedAt', 'reservation_date', 'expiration_date'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $prolongerAction = Action::new('prolonger', 'Prolonger')
            ->linkToCrudAction('prolongerReservation') // Méthode exécutée
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'prolongation' || $entity->getStatus() === 'active';
            });



        return $actions
            ->add(Crud::PAGE_INDEX, $prolongerAction)
            ->add(Crud::PAGE_DETAIL, $prolongerAction)
        ;
    }

    public function prolongerReservation(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $reservation = $context->getEntity()->getInstance();

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
        } else {
            $reservation->setStatus('prolonge');
            $reservation->setUpdatedAt(new \DateTimeImmutable());
            $reservation->setExpirationDate(new \DateTimeImmutable('+7 days'));
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation prolongée avec succès.');
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Reservation) {
            return;
        }

        $now = new \DateTimeImmutable();

        switch ($entityInstance->getStatus()) {
            case 'active':
                $entityInstance->setUpdatedAt($now);
                $entityInstance->setReservationDate(new \DateTime());
                $entityInstance->setExpirationDate(new \DateTimeImmutable('+7 days'));
                break;

            case 'prolonge':
                $entityInstance->setUpdatedAt($now);
                $entityInstance->setExpirationDate(new \DateTimeImmutable('+7 days'));
                break;

            case 'annule':
            case 'prolongation':
                $entityInstance->setUpdatedAt($now);
                break;
        }

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}
