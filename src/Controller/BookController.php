<?php

namespace App\Controller;

use App\Application\Service\BookService;
use App\Domain\Entity\Book;
use App\Domain\Repository\BookRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
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
    private BookService $bookService;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(BookService $bookService, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->bookService = $bookService;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[OA\Post(
        summary: "Create a new book",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string", example: "book titie"),
                    new OA\Property(property: "author", type: "string", example: "author name"),
                    new OA\Property(property: "isbn", type: "string", example: "0-061-96436-0")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Book created!"),
            new OA\Response(response: 400, description: "Invalid data"),
            new OA\Response(response: 406, description: "Title, author, and ISBN are required fields"),
            new OA\Response(response: 409, description: "A book with this ISBN already exists."),
        ]
    )]
    #[Route('/new', methods: ['POST'])]
    public function createBook(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        if (empty($data['title']) || empty($data['author']) || empty($data['isbn'])) {
            return $this->json(['message' => 'Title, author, and ISBN are required fields'], Response::HTTP_NOT_ACCEPTABLE);
        }

        $book = $this->bookService->createBook($data);

        try {
            $this->bookService->saveBook($book);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json(['message' => 'A book with this ISBN already exists.'], Response::HTTP_CONFLICT);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->json(['message' => 'Foreign key constraint violation.'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => 'Book created!'], Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: "Update an existing book",
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Book ID", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string", example: "book titie"),
                    new OA\Property(property: "author", type: "string", example: "author name"),
                    new OA\Property(property: "isbn", type: "string", example: "0-061-96436-0")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Book updated!"),
            new OA\Response(response: 400, description: "Invalid data"),
            new OA\Response(response: 404, description: "Book not found."),
            new OA\Response(response: 406, description: "Title, author, and ISBN are required fields"),
            new OA\Response(response: 409, description: "A book with this ISBN already exists.")
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function updateBook(Request $request, int $id, BookRepository $bookRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['message' => 'Book not found.'], Response::HTTP_NOT_FOUND);
        }

        // Validate required fields
        if (empty($data['title']) || empty($data['author']) || empty($data['isbn'])) {
            return $this->json(['message' => 'Title, author, and ISBN are required fields'], Response::HTTP_NOT_ACCEPTABLE);
        }

        $updatedBook = $this->bookService->updateBook($book, $data);

        try {
            $this->bookService->saveBook($updatedBook);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json(['message' => 'A book with this ISBN already exists.'], Response::HTTP_CONFLICT);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->json(['message' => 'Foreign key constraint violation.'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => 'Book updated!']);
    }

    #[OA\Delete(
        summary: "Delete a book",
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Book ID", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 204, description: ""),
            new OA\Response(response: 404, description: "Book not found."),
            new OA\Response(response: 400, description: "Foreign key constraint violation."),
            new OA\Response(response: 500, description: "An error occurred while deleting the book.")
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteBook(int $id, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['message' => 'Book not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->bookService->deleteBook($book);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->json(['message' => 'Foreign key constraint violation.'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred while deleting the book.'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        try {
            $books = $bookRepository->findAll();

            return $this->json($books, 200, [], ['groups' => ['book']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred while fetching the books'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        summary: "Get a book by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Book ID", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "", content: new OA\JsonContent(ref: new Model(type: Book::class))),
            new OA\Response(response: 400, description: "Invalid ID type. ID must be a positive integer."),
            new OA\Response(response: 404, description: "Book not found.")
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function getBookById($id, BookRepository $bookRepository): Response
    {
        $intId = (int) $id;
        if (!is_numeric($id) || $intId <= 0) {
            return $this->json(['message' => 'Invalid ID type. ID must be a positive integer.'], Response::HTTP_BAD_REQUEST);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['message' => 'Book not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($book->isDeleted()) {
            return $this->json(['message' => 'This book has been deleted.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($book, 200, [], ['groups' => ['book']]);
    }
}