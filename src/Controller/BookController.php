<?php

namespace App\Controller;

use App\Domain\Entity\Book;
use App\Domain\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api/books')]
class BookController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[OA\Post(
        summary: "Create a new book",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: Book::class, groups: ["write"]))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Book created",
                content: new OA\JsonContent(ref: new Model(type: Book::class))
            )
        ]
    )]
    #[Route('/new', methods: ['POST'])]
    public function createBook(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $book = new Book();
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);
        $book->setStatus(Book::STATUS_AVAILABLE);

        $errors = $this->validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['message' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($book);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw $e; // Ensure the exception is re-thrown
        }

        return $this->json(['status' => 'Book created!'], Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: "Update an existing book",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: Book::class, groups: ["write"]))
        ),
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Book ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Book updated",
                content: new OA\JsonContent(ref: new Model(type: Book::class))
            )
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function updateBook(Request $request, Book $book): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);

        $errors = $this->validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['message' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw $e; // Ensure the exception is re-thrown
        }

        return $this->json(['status' => 'Book updated!']);
    }

    #[OA\Delete(
        summary: "Delete a book",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Book ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Book deleted")
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteBook(Book $book): Response
    {
        $book->setStatus(Book::STATUS_DELETED);

        try {
            $this->entityManager->remove($book);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw $e; // Ensure the exception is re-thrown
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(
        summary: "Get all books",
        responses: [
            new OA\Response(
                response: 200,
                description: "List of books",
                content: new OA\JsonContent(type: "array", items: new OA\Items(ref: new Model(type: Book::class)))
            )
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function getBooks(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findAll();

        return $this->json($books, 200, [], ['groups' => ['book']]);
    }

    #[OA\Get(
        summary: "Get a book by ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Book ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Book details",
                content: new OA\JsonContent(ref: new Model(type: Book::class))
            )
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function getBook(Book $book): Response
    {
        if ($book->isDeleted()) {
            return $this->json(['message' => 'This book has been deleted.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($book, 200, [], ['groups' => ['book']]);
    }
}