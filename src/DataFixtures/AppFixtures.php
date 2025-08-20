<?php

namespace App\DataFixtures;
use App\Entity\Book;
use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
   public function load(ObjectManager $manager): void
   {
      // Création des auteurs.
      $listAuthor = [];
      for ($i = 0; $i < 10; $i++) {
   $author = new Author();
   $author->setName("Prénom $i Nom $i");
   $manager->persist($author);
   $listAuthor[] = $author;
      }

      // Création d'une vingtaine de livres ayant pour titre
      for ($i = 0; $i < 20; $i++) {
         $livre = new Book();
         $livre->setTitle('Livre ' . $i);
         $livre->setCoverText('Quatrième de couverture numéro :' . $i);
         $livre->setAuthor($listAuthor[array_rand($listAuthor)]);
         $manager->persist($livre);
      }

      $manager->flush();
   }
}