<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Tag;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Faker\Generator;

class AppFixtures extends Fixture
{
    /**
     * Charge les données dans la base de données.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        /**
         * Générateur de données aléatoires.
         */
        $faker = Factory::create('fr_FR');

        /**
         * Création d'un utilisateur admin.
         */
        $user = new User();
        $user->setEmail('admin@admin.fr');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword('$2y$13$TwahBDrP8dCyB8JybLCNQ.IbvLY0mMcLHRsmW77pCanBHZ.S/kdau'); // passwordAdmin
        $user->setFirstname('Admin');
        $user->setLastname('Admin');
        $user->setUsername('admin');
        $this->extracted($user, $faker, $manager);

        /**
         * Création d'un utilisateur user.
         */
        $user = new User();
        $user->setEmail('user@user.fr');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('$2y$13$rlbC/thW.VOfN9.6F24zdeB7rOTag9TdJV28u3GGQ5k87.HBBo9z.'); // password1234
        $user->setFirstname('User');
        $user->setLastname('User');
        $user->setUsername('user');
        $this->extracted($user, $faker, $manager);

        /**
         * Création des tags.
         */
        $tagNames = [
            'Romance', 'Science-Fiction', 'Policier', 'Fantasy', 'Historique',
            'Biographie', 'Philosophie', 'Thriller', 'Jeunesse', 'Classique',
            'Poésie', 'Horreur', 'Aventure', 'Dystopie', 'Développement personnel',
            'Science', 'Économie', 'Politique', 'Cuisine', 'Voyage'
        ];

        $tags = [];
        foreach ($tagNames as $tagName) {
            $tag = new Tag();
            $tag->setName($tagName);
            $manager->persist($tag);
            $tags[] = $tag;
        }

        /**
         * Chemin du fichier PDF des livres.
         */
        $pdfFilePath = '/uploads/pdf/Lorem.pdf';

        /**
         * Création de 150 livres.
         */
        for ($i = 0; $i < 150; $i++) {
            $book = new Book();
            $book->setName($faker->sentence(1,4));
            $book->setAuthor($faker->name);
            $book->setDescription($faker->paragraph(3));
            $book->setCover("https://covers.openlibrary.org/b/id/" . $faker->numberBetween(1000000, 9000000) . "-M.jpg");
            $book->setPopularity($faker->numberBetween(0, 100));
            $book->setSlug($faker->slug);
            $book->setIsRestricted($faker->boolean(1));
            $book->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 years', 'now')));
            $createdAtString = $book->getCreatedAt()->format('Y-m-d H:i:s');
            $book->setUpdatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween($createdAtString, 'now')));
            $book->setIsReserved($faker->boolean(1));
            $book->setPdfFile($pdfFilePath);

            $bookTags = $faker->randomElements($tags, $faker->numberBetween(1, 3));
            foreach ($bookTags as $tag) {
                $book->addTag($tag);
            }

            $manager->persist($book);
        }

        $manager->flush();
    }

    /**
     * Extrait les données communes à tous les utilisateurs.
     *
     * @param User $user
     * @param Generator $faker
     * @param ObjectManager $manager
     * @return void
     */
    public function extracted(User $user, Generator $faker, ObjectManager $manager): void
    {
        $user->setImage('default.png');
        $user->setIsVerified(true);
        $user->setIsTerms(true);
        $user->setIsGpdr(true);
        $user->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 years', 'now')));
        $user->setUpdatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween($user->getCreatedAt()->format('Y-m-d H:i:s'), 'now')));
        $manager->persist($user);
    }
}