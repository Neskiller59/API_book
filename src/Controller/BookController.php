<?php
// src/Controller/BookController.php
namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'books', methods: ['GET'])]
    public function getAllBooks(
        BookRepository $bookRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize(
            $bookList,
            'json',
            ['groups' => 'getBooks']
        );

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
    public function getDetailBook(
        Book $book,
        SerializerInterface $serializer
    ): JsonResponse {
        $jsonBook = $serializer->serialize(
            $book,
            'json',
            ['groups' => 'getBooks']
        );

        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

   #[Route('/api/books', name: "createBook", methods: ['POST'])]
public function createBook(
    Request $request,
    SerializerInterface $serializer,
    EntityManagerInterface $em,
    UrlGeneratorInterface $urlGenerator,
    AuthorRepository $authorRepository
): JsonResponse {
    // On désérialise le JSON envoyé en Book
    /** @var Book $book */
    $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

    // On transforme la requête en tableau pour extraire idAuthor
    $content = $request->toArray();
    $idAuthor = $content['idAuthor'] ?? -1;

    // On assigne l'auteur si trouvé, sinon null
    $book->setAuthor($authorRepository->find($idAuthor));

    $em->persist($book);
    $em->flush();

    // On sérialise le livre créé
    $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);

    // On génère l’URL du livre créé
    $location = $urlGenerator->generate(
        'detailBook',
        ['id' => $book->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
}



    #[Route('/api/books/{id}', name: 'updateBook', methods: ['PUT'])]
public function updateBook(
    Request $request,
    SerializerInterface $serializer,
    Book $currentBook,
    EntityManagerInterface $em,
    AuthorRepository $authorRepository
): JsonResponse {
    // On met à jour l'entité existante avec les nouvelles données
    $updatedBook = $serializer->deserialize(
        $request->getContent(),
        Book::class,
        'json',
        [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
    );

    // Récupération de l'idAuthor dans la requête
    $content = $request->toArray();
    $idAuthor = $content['idAuthor'] ?? -1;

    // Mise à jour de l’auteur (si trouvé, sinon null)
    $updatedBook->setAuthor($authorRepository->find($idAuthor));

    $em->persist($updatedBook);
    $em->flush();

    return new JsonResponse(null, Response::HTTP_NO_CONTENT);
}

    #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
    public function deleteBook(
        Book $book,
        EntityManagerInterface $em
    ): JsonResponse {
        $em->remove($book);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
