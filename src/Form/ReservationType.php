<?php
namespace App\Form;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reservation_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de réservation'
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Réservé' => 'reserved',
                    'Annulé' => 'cancelled',
                    'En attente' => 'pending',
                ],
                'label' => 'Statut'
            ])
            ->add('expiration_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'expiration'
            ])
            // 'created_at' et 'updated_at' sont omis, car gérés automatiquement par Doctrine
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',  // Affiche le nom d'utilisateur au lieu de l'id
                'data' => $options['user'], // Associe l'utilisateur actuel à la réservation
                'disabled' => true, // Empêche la modification de l'utilisateur
            ])
            ->add('book', EntityType::class, [
                'class' => Book::class,
                'choice_label' => 'title',  // Affiche le titre du livre au lieu de l'id
                'data' => $options['book'], // Associe le livre spécifique à la réservation
                'disabled' => true, // Empêche la modification du livre
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'user' => null,  // L'utilisateur connecté doit être passé en paramètre
            'book' => null,  // Le livre réservé doit être passé en paramètre
        ]);
    }
}