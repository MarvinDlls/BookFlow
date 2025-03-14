<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => "Votre prénom",
                'label_attr' => ['class' => 'block text-md font-semibold text-red-800 mt-4'],
                'attr' => [
                    'class' => 'w-full mt-1 py-2 px-3 border border-gray-200 shadow-lg placeholder:text-black outline-none focus:border-solid focus:border-[1px]',
                    'placeholder' => 'Ex: Jean',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom']),
                    new Length(['min' => 3, 'max' => 50]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => "Votre nom de famille",
                'label_attr' => ['class' => 'block text-md font-semibold text-red-800 mt-4'],
                'attr' => [
                    'class' => 'w-full mt-1 py-2 px-3 border border-gray-200 shadow-lg placeholder:text-black outline-none focus:border-solid focus:border-[1px]',
                    'placeholder' => 'Ex: Dupont',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom de famille']),
                    new Length(['min' => 3, 'max' => 50]),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
                'label_attr' => ['class' => 'block text-md font-semibold text-red-800 mt-4'],
                'attr' => [
                    'class' => 'w-full mt-1 py-2 px-3 border border-gray-200 shadow-lg placeholder:text-black outline-none focus:border-solid focus:border-[1px]',
                    'placeholder' => 'Ex: jeanDupont',
                ],
                'constraints' => [
                    new NotBlank(['message' => "Veuillez entrer un nom d'utilisateur"]),
                    new Length(['min' => 3, 'max' => 50]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => "Photo de profil",
                'mapped' => false,
                'required' => false,
                'label_attr' => ['class' => 'block text-md font-semibold text-red-800 mt-4'],
                'attr' => [
                    'class' => 'w-full py-2 px-4 text-gray-700 cursor-pointer bg-white transition duration-300',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WebP',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => "Adresse e-mail",
                'label_attr' => ['class' => 'block text-md font-semibold text-red-800 mt-4'],
                'attr' => [
                    'class' => 'w-full mt-1 py-2 px-3 border border-gray-200 shadow-lg placeholder:text-black outline-none focus:border-solid focus:border-[1px]',
                    'placeholder' => 'Ex: jean.dupont@email.com',
                ],
                'constraints' => [
                    new NotBlank(['message' => "Veuillez entrer une adresse email"]),
                    new Email(['message' => "Adresse email non valide"]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => "Mot de passe",
                    'label_attr' => ['class' => 'block text-md font-semibold text-red-800 mt-4'],
                    'attr' => [
                        'class' => 'w-full mt-1 py-2 px-3 border border-gray-200 shadow-lg placeholder:text-black outline-none focus:border-solid focus:border-[1px]',
                        'placeholder' => '••••••••',
                    ],
                ],
                'second_options' => [
                    'label' => "Confirmez votre mot de passe",
                    'label_attr' => ['class' => 'block text-md font-semibold text-red-800 mt-4'],
                    'attr' => [
                        'class' => 'w-full mt-1 py-2 px-3 border border-gray-200 shadow-lg placeholder:text-black outline-none focus:border-solid focus:border-[1px]',
                        'placeholder' => '••••••••',
                    ],
                ],
                'constraints' => [
                    new NotBlank(['message' => "Veuillez entrer un mot de passe"]),
                    new Length(['min' => 6, 'minMessage' => "Votre mot de passe doit faire au moins 6 caractères"]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Modifier mes informations',
                'attr' => [
                    'class' => 'w-full bg-red-800 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 shadow-md mt-6',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
