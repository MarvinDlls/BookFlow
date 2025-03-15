<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Regex;

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
                ],
                'required' => true,
            ])
            ->add('lastname', null, [
                'label' => 'Votre prénom',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'Votre prénom',
                ],
                'required' => true,
            ])
            ->add('username', null, [
                'label' => 'Votre nom d\'utilisateur',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'Votre nom',
                ],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse e-mail',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'exemple@exemple.com',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir une adresse e-mail.',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer une adresse e-mail valide.',
                    ]),
                ]

            ])
            ->add('plainPassword', RepeatedType::class, [
                'row_attr' => ['class' => 'mb-3'],
                'label' => 'Mot de passe',
                'label_attr' => [ 'class' => 'form-label'],
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'mapped' => false,
                'first_options' => [
                    'label_attr' => ['class' => 'form-label'],
                    'label' => 'Mot de passe',
                    'attr' => ['class' => 'form-control mb-3']
                ],
                'second_options' => [
                    'label_attr' => ['class' => 'form-label'],
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['class' => 'form-control mb-3']
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ]
            ])
            ->add('isTerms', CheckboxType::class, [
                'label' => 'J\'accepter les termes',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les termes et conditions.',
                    ]),
                ]
            ])
            ->add('isGpdr', CheckboxType::class, [
                'label' => 'J\'accepter la politique de confidentialité',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter la politique de confidentialité.',
                    ]),
                ]
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
