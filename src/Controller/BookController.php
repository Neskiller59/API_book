<?php
// src/Controller/BookController.php
namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'book_list', methods: ['GET'])]
    public function getBookList(
        BookRepository $bookRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList, 'json');

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'book_detail', methods: ['GET'])]
    public function getDetailBook(
        int $id,
        SerializerInterface $serializer,
        BookRepository $bookRepository
    ): JsonResponse {
        $book = $bookRepository->find($id);

        if (!$book) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $jsonBook = $serializer->serialize($book, 'json');
        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }
}
