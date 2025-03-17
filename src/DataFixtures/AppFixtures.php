<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

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

        for ($i = 0; $i < 150; $i++) {
            $book = new Book();

            $openlib = $faker->numberBetween(1000000, 9000000);

            try {
                $bookData = json_decode(file_get_contents("https://openlibrary.org/api/books?bibkeys=OLID:{$openlib}&format=json&jscmd=data"), true);

                if ($bookData && isset($bookData["OLID:{$openlib}"])) {
                    $data = $bookData["OLID:{$openlib}"];

                    $book->setName($data['title'] ?? $faker->sentence(1, 4));

                    if (isset($data['authors']) && !empty($data['authors'])) {
                        $book->setAuthor($data['authors'][0]['name']);
                    } else {
                        $book->setAuthor($faker->name);
                    }

                    if (isset($data['description'])) {
                        $description = is_array($data['description']) ? ($data['description']['value'] ?? '') : $data['description'];
                        $book->setDescription($description ?: $faker->paragraph(3));
                    } else {
                        $book->setDescription($faker->paragraph(3));
                    }

                    if (isset($data['cover']) && isset($data['cover']['medium'])) {
                        $book->setCover($data['cover']['medium']);
                    } else {
                        $book->setCover("https://covers.openlibrary.org/b/id/{$openlib}-M.jpg");
                    }
                } else {
                    throw new \Exception("Données non disponibles");
                }
            } catch (\Exception $e) {
                $book->setName($faker->sentence(1, 4));
                $book->setAuthor($faker->name);
                $book->setDescription($faker->paragraph(3));
                $book->setCover("https://picsum.photos/seed/book{$i}/200/300");
            }

            $book->setPopularity($faker->numberBetween(0, 100));
            $book->setSlug($faker->slug);
            $book->setIsRestricted($faker->boolean(20));
            $book->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 years', 'now')));
            $createdAtString = $book->getCreatedAt()->format('Y-m-d H:i:s');
            $book->setUpdatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween($createdAtString, 'now')));
            $book->setIsReserved($faker->boolean(30));

            $bookTags = $faker->randomElements($tags, $faker->numberBetween(1, 3));
            foreach ($bookTags as $tag) {
                $book->addTag($tag);
            }

            $manager->persist($book);
        }

        $manager->flush();
    }
}