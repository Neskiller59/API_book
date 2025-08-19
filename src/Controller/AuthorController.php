<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AuthorController extends AbstractController
{
    #[Route('/authors', name: 'get_all_authors', methods: ['GET'])]
    public function getAllAuthor(AuthorRepository $authorRepository): JsonResponse
    {
        $authors = $authorRepository->findAll();

        return $this->json(
            $authors,
            200,
            [],
            ['groups' => ['getAuthor']]
        );
    }

    #[Route('/authors/{id}', name: 'get_author', methods: ['GET'])]
    public function getAuthor(Author $author): JsonResponse
    {
        return $this->json(
            $author,
            200,
            [],
            ['groups' => ['getAuthor']]
        );
    }
}
