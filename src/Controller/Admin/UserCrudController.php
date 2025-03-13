<?php

namespace App\Controller\Admin;

use App\Entity\User;
use phpDocumentor\Reflection\Types\Boolean;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('email'),
            TextField::new('firstname', 'Nom'),
            TextField::new('lastname', 'Prénom'),
            TextField::new('username', 'Pseudo'),
            BooleanField::new('isVerified', 'Vérifié')
            ->renderAsSwitch(false) // Désactive le switch
            ->formatValue(static function ($value) {
                return $value ? 'Oui' : 'Non';
            }),

        ];
    }

}
