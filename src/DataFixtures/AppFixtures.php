<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author; // si tu as une entité Author
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Exemple : liste d'auteurs fictifs si tu as une entité Author
        $listAuthor = [
            'Auteur 1',
            'Auteur 2',
            'Auteur 3'
        ];

        // Création d'une vingtaine de livres
        for ($i = 0; $i < 20; $i++) {
            $book = new Book();
            $book->setTitle("Titre " . $i);
            $book->setCoverText("Quatrième de couverture numéro : " . $i);

            // Si tu as une relation avec Author
            // $author = new Author();
            // $author->setName($listAuthor[array_rand($listAuthor)]);
            // $book->setAuthor($author);

            $manager->persist($book);
        }

        $manager->flush();
    }
}
