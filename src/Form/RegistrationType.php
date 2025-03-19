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
                    'placeholder' => 'Entrez votre nom',
                    'class' => 'capitalize shadow-2xl p-3 w-full outline-none focus:border-solid focus:border-[1px] border-red-800 placeholder:text-gray-400',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 80,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Votre nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Votre nom ne peut contenir que des lettres, des espaces et des tirets.',
                    ]),
                ],
            ])
            ->add('lastname', null, [
                'label' => 'Votre prénom',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'Entrez votre prénom',
                    'class' => 'capitalize shadow-2xl p-3 w-full outline-none focus:border-solid focus:border-[1px] border-red-800 placeholder:text-gray-400',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre prénom.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 80,
                        'minMessage' => 'Votre prénom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Votre prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Votre prénom ne peut contenir que des lettres, des espaces et des tirets.',
                    ]),
                ],
            ])
            ->add('username', null, [
                'label' => 'Nom d\'utilisateur',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'Choisissez un nom d\'utilisateur',
                    'class' => 'shadow-2xl p-3 w-full outline-none focus:border-solid focus:border-[1px] border-red-800 placeholder:text-gray-400',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom d\'utilisateur.',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 80,
                        'minMessage' => 'Votre nom d\'utilisateur doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Votre nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9_\-]+$/',
                        'message' => 'Votre nom d\'utilisateur ne peut contenir que des lettres, des chiffres, des tirets et des underscores.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse e-mail',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'placeholder' => 'exemple@exemple.com',
                    'class' => 'shadow-2xl p-3 w-full outline-none focus:border-solid focus:border-[1px] border-red-800 placeholder:text-gray-400',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une adresse e-mail.',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer une adresse e-mail valide.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                        'message' => 'Veuillez entrer une adresse e-mail valide.',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'row_attr' => ['class' => 'mb-3'],
                'label' => 'Mot de passe',
                'label_attr' => [ 'class' => 'form-label'],
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'label_attr' => ['class' => 'form-label'],
                    'attr' => [
                        'placeholder' => 'Entrez votre mot de passe',
                        'class' => 'shadow-2xl p-3 w-full outline-none focus:border-solid focus:border-[1px] border-red-800 placeholder:text-gray-400',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'label_attr' => ['class' => 'form-label'],
                    'attr' => [
                        'placeholder' => 'Confirmez votre mot de passe',
                        'class' => 'shadow-2xl p-3 w-full outline-none focus:border-solid focus:border-[1px] border-red-800 placeholder:text-gray-400',
                    ],
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe.',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
                        'message' => 'Votre mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
                    ]),
                ],
            ])
            ->add('isTerms', CheckboxType::class, [
                'label' => 'J\'accepte les termes et conditions',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les termes et conditions.',
                    ]),
                ],
            ])
            ->add('isGpdr', CheckboxType::class, [
                'label' => 'J\'accepte la politique de confidentialité',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter la politique de confidentialité.',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => "S'inscrire",
                'attr' => [
                    'class' => 'outline-none glass shadow-2xl w-full p-3 bg-[#ffffff42] hover:border-red-800 hover:border-solid hover:border-[1px] hover:text-red-800 font-bold',
                ],
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
