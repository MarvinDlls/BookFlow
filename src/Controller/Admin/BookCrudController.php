<?php

namespace App\Controller\Admin;

use BcMath\Number;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

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
            TextField::new('name', 'Titre'),
            TextField::new('author', 'Auteur'),
            TextEditorField::new('description', 'Description'),
            ImageField::new('cover', 'Couverture')
            ->setUploadDir('public/uploads/cover')
            ->setBasePath('uploads/cover'),
            NumberField::new('popularity', 'Popularité'),
            TextField::new('slug', 'Slug'),
            BooleanField::new('isRestricted', 'Restreint'),
            BooleanField::new('isReserved', 'Réservé'),
            DateTimeField::new('createdAt', 'Ajouté le'),
            DateTimeField::new('updatedAt', 'Mise à jour le'),
            TextField::new('pdfFile', 'Livre')
                ->onlyOnIndex()
                ->setTemplatePath('admin/fields/pdf_file.html.twig')
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

    public function createEntity(string $entityFqcn)
    {
        $book = new Book();
        $book->setPdfFile('/uploads/pdf/Lorem.pdf');
        $book->setCreatedAt(new \DateTimeImmutable());
        $book->setUpdatedAt(new \DateTimeImmutable());
        return $book;
    }
}
