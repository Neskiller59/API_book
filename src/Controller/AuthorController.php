<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/authors')]
class AuthorController extends AbstractController
{
    #[Route('', name: 'authors', methods: ['GET'])]
    public function getAllAuthors(
        AuthorRepository $authorRepository,
        SerializerInterface $serializer,
        Request $request
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);

        $authors = $authorRepository->findAllWithPagination($page, $limit);
        $json = $serializer->serialize($authors, 'json', SerializationContext::create()->setGroups(['getAuthor']));

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'getAuthor', methods: ['GET'])]
    public function getDetailAuthor(
        Author $author,
        SerializerInterface $serializer
    ): JsonResponse {
        $json = $serializer->serialize($author, 'json', SerializationContext::create()->setGroups(['getAuthor']));
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'createAuthor', methods: ['POST'])]
    public function createAuthor(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var Author $author */
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $errors = $validator->validate($author);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse($errorMessages, Response::HTTP_BAD_REQUEST);
        }

        $em->persist($author);
        $em->flush();

        $jsonAuthor = $serializer->serialize($author, 'json', SerializationContext::create()->setGroups(['getAuthor']));
        $location = $urlGenerator->generate('getAuthor', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'updateAuthor', methods: ['PUT', 'PATCH'])]
    public function updateAuthor(
        Request $request,
        SerializerInterface $serializer,
        Author $currentAuthor,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $context = DeserializationContext::create();
        $context->setAttribute('target', $currentAuthor);

        /** @var Author $updatedAuthor */
        $updatedAuthor = $serializer->deserialize(
            $request->getContent(),
            Author::class,
            'json',
            $context
        );

        $errors = $validator->validate($updatedAuthor);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse($errorMessages, Response::HTTP_BAD_REQUEST);
        }

        $em->persist($updatedAuthor);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
    public function deleteAuthor(
        Author $author,
        EntityManagerInterface $em
    ): JsonResponse {
        $em->remove($author);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
