<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, [
                'label' => 'Votre nom',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'Votre nom',
                ]
            ])
            ->add('lastname', null, [
                'label' => 'Votre prénom',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'Votre prénom',
                ]
            ])
            ->add('username', null, [
                'label' => 'Votre nom d\'utilisateur',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'Votre nom',
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse e-mail',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'exemple@exemple.com',
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Votre mot de passe',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('isTerms', CheckboxType::class, [
                'label' => 'J\'accepter les termes',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez confirmer les termes.',
                    ]),
                ],
            ])
            ->add('isGpdr', CheckboxType::class, [
                'label' => 'J\'accepter la politique de confidentialité',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez confirmer les termes.',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => "S'inscrire",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
