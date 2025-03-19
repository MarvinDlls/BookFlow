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

/**
 * Contrôleur CRUD pour la gestion des réservations.
 */
class ReservationCrudController extends AbstractCrudController
{
    /**
     * Retourne le nom complet de l'entité gérée par ce contrôleur.
     *
     * @return string Le nom complet de la classe de l'entité Reservation.
     */
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    /**
     * Configure les champs à afficher dans le CRUD pour la gestion des réservations.
     *
     * @param string $pageName Le nom de la page actuelle (index, new, edit, etc.).
     * @return iterable La liste des champs à afficher dans le formulaire de CRUD.
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('user.username', 'Utilisateur'),
            TextField::new('book.name', 'Titre'),
            TextField::new('book.author', 'Auteur'),
            ChoiceField::new('status')
                ->setChoices([
                    'En attente' => 'en_attente',
                    'Réservée' => 'reserve',
                    'Annulée' => 'annule',
                    'Expirée' => 'expire',
                    'Prolongée' => 'prolonge',
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

    /**
     * Configure les options générales du CRUD pour la gestion des réservations.
     *
     * @param Crud $crud L'objet de configuration du CRUD.
     * @return Crud L'objet de configuration modifié avec les nouvelles options.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', '📚 Gestion des réservations')
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setSearchFields(['id', 'user.username', 'book.name', 'book.author', 'status', 'createdAt', 'updatedAt', 'reservation_date', 'expiration_date'])
        ;
    }

    /**
     * Configure les actions à afficher dans le CRUD pour la gestion des réservations.
     *
     * @param Actions $actions L'objet de configuration des actions.
     * @return Actions L'objet de configuration modifié avec les nouvelles actions.
     */
    public function configureActions(Actions $actions): Actions
    {
        $prolongerAction = Action::new('prolonge', 'Prolonger')
            ->linkToCrudAction('prolongerReservation')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'prolongation' || $entity->getStatus() === 'reserve';
            });

        $reserverAction = Action::new('reserve', 'Accepter')
            ->linkToCrudAction('reserverReservation')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'en_attente';
            });

        $annulerAction = Action::new('annule', 'Annuler')
            ->linkToCrudAction('annulerReservation')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'en_attente' || $entity->getStatus() === 'reserve' || $entity->getStatus() === 'prolonge' || $entity->getStatus() === 'prolongation';
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $prolongerAction)
            ->add(Crud::PAGE_INDEX, $reserverAction)
            ->add(Crud::PAGE_INDEX, $annulerAction)
        ;
    }

    /**
     * Gère l'action de prolongation d'une réservation.
     */
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

    public function reserverReservation(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $reservation = $context->getEntity()->getInstance();

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
        } else {
            $reservation->setStatus('reserve');
            $reservation->getBook()->setIsReserved(true);
            $reservation->setUpdatedAt(new \DateTimeImmutable());
            $reservation->setReservationDate(new \DateTime());
            $reservation->setExpirationDate(new \DateTimeImmutable('+7 days'));
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation activée avec succès.');
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    public function annulerReservation(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $reservation = $context->getEntity()->getInstance();

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable.');
        } else {
            $reservation->setStatus('annule');
            $reservation->getBook()->setIsReserved(false);
            $reservation->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation annulée avec succès.');
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    /**
     * Met à jour une entité Reservation en fonction de son statut.
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Reservation) {
            return;
        }

        $now = new \DateTimeImmutable();

        switch ($entityInstance->getStatus()) {
            case 'reserve':
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
