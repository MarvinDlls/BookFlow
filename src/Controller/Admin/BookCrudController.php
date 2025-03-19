<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class BookCrudController extends AbstractCrudController
{
    /**
     * Retourne le nom complet de l'entité gérée par ce contrôleur.
     *
     * @return string Le nom complet de la classe de l'entité Book.
     */
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    /**
     * Configure les champs à afficher dans le CRUD pour la gestion des livres.
     *
     * @param string $pageName Le nom de la page actuelle (index, new, edit, etc.).
     * @return iterable La liste des champs à afficher dans le formulaire de CRUD.
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name', 'Titre'),
            TextField::new('author', 'Auteur'),
            TextEditorField::new('description', 'Description'),
            ImageField::new('cover', 'Couverture'),
            BooleanField::new('isRestricted', 'Restreint'),
            DateTimeField::new('createdAt', 'Ajouté le'),
        ];
    }

    /**
     * Configure les options générales du CRUD pour la gestion des livres.
     *
     * @param Crud $crud L'objet de configuration du CRUD.
     * @return Crud L'objet de configuration modifié avec les nouvelles options.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', '📚 Gestion des livres')
            ->setEntityLabelInSingular('Livre')
            ->setEntityLabelInPlural('Livres')
            ->setSearchFields(['id', 'name', 'author', 'description'])
            ;
    }
}
