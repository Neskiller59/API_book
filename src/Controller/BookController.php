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

#[Route('/api/books')]
class BookController extends AbstractController
{
    // ---------------- List with pagination ----------------
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
    #[Route('/{id}', name: 'books_detail', methods: ['GET'])]
    public function getBook(
        Book $book,
        SerializerInterface $serializer,
        VersioningService $versioningService
    ): JsonResponse {
        $version = $versioningService->getVersion();

        $context = SerializationContext::create()
            ->setGroups(['getBooks'])
            ->setVersion($version);

        $jsonBook = $serializer->serialize($book, 'json', $context);

        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    // ---------------- Create book ----------------
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
        /** @var Book $book */
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? null;
        if ($idAuthor) {
            $author = $authorRepository->find($idAuthor);
            if (!$author) {
                return new JsonResponse(['error' => 'Auteur non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $book->setAuthor($author);
        }

        $errors = $validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse($errorMessages, Response::HTTP_BAD_REQUEST);
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

        /** @var Book $updatedBook */
        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, 'json', $context);

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? null;
        if ($idAuthor) {
            $author = $authorRepository->find($idAuthor);
            if (!$author) {
                return new JsonResponse(['error' => 'Auteur non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $updatedBook->setAuthor($author);
        }

        $errors = $validator->validate($updatedBook);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse($errorMessages, Response::HTTP_BAD_REQUEST);
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
}
