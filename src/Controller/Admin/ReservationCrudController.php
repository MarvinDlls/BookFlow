<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use phpDocumentor\Reflection\Types\Boolean;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
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
                ])
                ->renderAsBadges()
                ->setHelp('Choisissez le statut de la réservation'),
            DateTimeField::new('createdAt', 'Ajouté le'),
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
}
