<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/authors')]
final class AuthorController extends AbstractController
{
    #[Route('', name: 'get_all_authors', methods: ['GET'])]
    public function getAllAuthors(AuthorRepository $authorRepository): JsonResponse
    {
        $authors = $authorRepository->findAll();

        return $this->json($authors, 200, [], ['groups' => ['getAuthor']]);
    }

    #[Route('/{id}', name: 'get_author', methods: ['GET'])]
    public function getAuthor(Author $author): JsonResponse
    {
        return $this->json($author, 200, [], ['groups' => ['getAuthor']]);
    }

    #[Route('', name: 'create_author', methods: ['POST'])]
    public function createAuthor(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Author $author */
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');
        $em->persist($author);
        $em->flush();

        return $this->json($author, 201, [], ['groups' => ['getAuthor']]);
    }

    #[Route('/{id}', name: 'update_author', methods: ['PUT'])]
    public function updateAuthor(
        Request $request,
        Author $author,
        SerializerInterface $serializer,
        EntityManagerInterface $em
    ): JsonResponse {
        $updatedAuthor = $serializer->deserialize(
            $request->getContent(),
            Author::class,
            'json',
            ['object_to_populate' => $author]
        );

        $em->persist($updatedAuthor);
        $em->flush();

        return $this->json($updatedAuthor, 200, [], ['groups' => ['getAuthor']]);
    }

    #[Route('/{id}', name: 'delete_author', methods: ['DELETE'])]
    public function deleteAuthor(
        Author $author,
        EntityManagerInterface $em
    ): JsonResponse {
        $em->remove($author); // ⚡ Grâce à cascade={"remove"} sur l'entité Author → les Books liés seront supprimés
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
