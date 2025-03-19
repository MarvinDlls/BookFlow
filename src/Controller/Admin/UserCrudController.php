<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UserCrudController extends AbstractCrudController
{
    /**
     * Retourne le nom complet de l'entité gérée par ce contrôleur.
     *
     * @return string Le nom complet de la classe de l'entité User.
     */
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /**
     * Configure les champs à afficher dans le CRUD pour la gestion des utilisateurs.
     *
     * @param string $pageName Le nom de la page actuelle (index, new, edit, etc.).
     * @return iterable La liste des champs à afficher dans le formulaire de CRUD.
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('email'),
            TextField::new('firstname', 'Nom'),
            TextField::new('lastname', 'Prénom'),
            TextField::new('username', 'Pseudo'),
            DateTimeField::new('createdAt', 'Créé le'),
            BooleanField::new('isVerified', 'Vérifié')
                ->renderAsSwitch(false)
                ->formatValue(static function ($value) {
                    return $value ? 'Oui' : 'Non';
                }),
        ];
    }

    /**
     * Configure le CRUD pour la gestion des utilisateurs.
     *
     * @param Crud $crud L'objet de configuration du CRUD.
     * @return Crud L'objet de configuration du CRUD mis à jour.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', '👤 Gestion des utilisateurs')
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setSearchFields(['id', 'email', 'firstname', 'lastname', 'username'])
            ;
    }

    /**
     * Crée une requête pour récupérer les utilisateurs.
     *
     * @param SearchDto $searchDto Les paramètres de recherche.
     * @param EntityDto $entityDto Les informations sur l'entité.
     * @param FieldCollection $fields Les champs de l'entité.
     * @param FilterCollection $filters Les filtres de recherche.
     * @return QueryBuilder La requête pour récupérer les utilisateurs.
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder
            ->andWhere('entity.roles LIKE :role')
            ->setParameter('role', '%"ROLE_USER"%');

        return $queryBuilder;
    }
}
