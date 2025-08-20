<?php
// src/Controller/BookController.php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use App\Service\VersioningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api/books')]
class BookController extends AbstractController
{
    // ---------------- List with pagination ----------------
    /**
     * @OA\Get(
     *     path="/api/books",
     *     summary="Récupère la liste des livres",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre de livres par page",
     *         @OA\Schema(type="integer", default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des livres",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref=@Model(type=Book::class, groups={"getBooks"}))
     *         )
     *     )
     * )
     * @Security(name="bearerAuth")
     */
    #[Route('', name: 'books_list', methods: ['GET'])]
    public function getAllBooks(
        BookRepository $bookRepository,
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);
        $idCache = "getAllBooks-{$page}-{$limit}";

        $jsonBookList = $cache->get($idCache, function (ItemInterface $item) use ($bookRepository, $serializer, $page, $limit) {
            $item->tag("booksCache");
            $books = $bookRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($books, 'json', SerializationContext::create()->setGroups(['getBooks']));
        });

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    // ---------------- Get detail book ----------------
    /**
     * @OA\Get(
     *     path="/api/books/{id}",
     *     summary="Récupère un livre par son ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du livre",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Livre récupéré",
     *         @OA\JsonContent(ref=@Model(type=Book::class, groups={"getBooks"}))
     *     ),
     *     @OA\Response(response=404, description="Livre non trouvé")
     * )
     * @Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'books_detail', methods: ['GET'])]
    public function getBook(
        Book $book,
        SerializerInterface $serializer,
        VersioningService $versioningService
    ): JsonResponse {
        $version = $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(['getBooks'])->setVersion($version);

        $jsonBook = $serializer->serialize($book, 'json', $context);

        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    // ---------------- Create book ----------------
    /**
     * @OA\Post(
     *     path="/api/books",
     *     summary="Créer un livre",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref=@Model(type=Book::class))
     *     ),
     *     @OA\Response(response=201, description="Livre créé"),
     *     @OA\Response(response=400, description="Erreur de validation"),
     *     @OA\Response(response=404, description="Auteur non trouvé")
     * )
     * @Security(name="bearerAuth")
     */
    #[Route('', name: 'books_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour créer un livre.')]
    public function createBook(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        AuthorRepository $authorRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $this->setAuthorFromRequest($book, $request->toArray(), $authorRepository);

        $errors = $validator->validate($book);
        if (count($errors) > 0) {
            return $this->formatValidationErrors($errors);
        }

        $em->persist($book);
        $em->flush();
        $cache->invalidateTags(["booksCache"]);

        $jsonBook = $serializer->serialize($book, 'json', SerializationContext::create()->setGroups(['getBooks']));
        $location = $urlGenerator->generate('books_detail', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    // ---------------- Update book ----------------
    #[Route('/{id}', name: 'books_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour éditer un livre.')]
    public function updateBook(
        Request $request,
        SerializerInterface $serializer,
        Book $currentBook,
        EntityManagerInterface $em,
        AuthorRepository $authorRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $context = DeserializationContext::create();
        $context->setAttribute('target', $currentBook);

        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, 'json', $context);

        $this->setAuthorFromRequest($updatedBook, $request->toArray(), $authorRepository);

        $errors = $validator->validate($updatedBook);
        if (count($errors) > 0) {
            return $this->formatValidationErrors($errors);
        }

        $em->persist($updatedBook);
        $em->flush();
        $cache->invalidateTags(["booksCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // ---------------- Delete book ----------------
    #[Route('/{id}', name: 'books_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un livre.')]
    public function deleteBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $em->remove($book);
        $em->flush();
        $cache->invalidateTags(["booksCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // ---------------- Helper methods ----------------
    private function setAuthorFromRequest(Book $book, array $content, AuthorRepository $authorRepository): void
    {
        $idAuthor = $content['idAuthor'] ?? null;
        if ($idAuthor) {
            $author = $authorRepository->find($idAuthor);
            if (!$author) {
                throw $this->createNotFoundException('Auteur non trouvé');
            }
            $book->setAuthor($author);
        }
    }

    private function formatValidationErrors($errors): JsonResponse
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[] = [
                'field' => $error->getPropertyPath(),
                'message' => $error->getMessage()
            ];
        }
        return new JsonResponse(['errors' => $formatted], Response::HTTP_BAD_REQUEST);
    }
}
