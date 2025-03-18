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
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

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
