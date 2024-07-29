<?php

namespace App\Controller;

use App\Application\Service\UserService;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use App\Exception\UserNotFoundException;
use App\Exception\BookNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private UserService $userService;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(UserService $userService, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator)
    {
        $this->userService = $userService;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    #[OA\Post(
        summary: "Create a new user",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: User::class, groups: ["write"]))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User created",
                content: new OA\JsonContent(ref: new Model(type: User::class))
            )
        ]
    )]
    #[Route('/new', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return $this->json(['message' => 'Name, email, and password are required fields'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $this->passwordHasher->hashPassword(new User(
            new Name($data['name']),
            new Email($data['email']),
            new Password($data['password'])
        ), $data['password']);
        $user = $this->userService->createUser($data['name'], $data['email'], $hashedPassword);

        try {
            $this->userService->saveUser($user);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json(['message' => 'A user with this email already exists.'], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred while creating the user.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['status' => 'User created!'], Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: "Update an existing user",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: User::class, groups: ["write"]))
        ),
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User updated",
                content: new OA\JsonContent(ref: new Model(type: User::class))
            )
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function updateUser(Request $request, int $id, UserRepository $userRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        // Fetch user entity
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Validate required fields
        if (empty($data['name']) || empty($data['email'])) {
            return $this->json(['message' => 'Name and email are required fields'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = null;
        if (!empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        }
        $updatedUser = $this->userService->updateUser($user, $data['name'], $data['email'], $hashedPassword);

        try {
            $this->userService->saveUser($updatedUser);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json(['message' => 'A user with this email already exists.'], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred while updating the user.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['status' => 'User updated!']);
    }

    #[OA\Delete(
        summary: "Delete a user",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "User deleted")
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteUser(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->userService->deleteUser($user);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->json(['message' => 'Foreign key constraint violation.'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred while deleting the user.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(
        summary: "Get all users",
        responses: [
            new OA\Response(
                response: 200,
                description: "List of users",
                content: new OA\JsonContent(type: "array", items: new OA\Items(ref: new Model(type: User::class)))
            )
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function getUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->json($users, 200, [], ['groups' => ['user']]);
    }

    #[OA\Get(
        summary: "Get a user by ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User details",
                content: new OA\JsonContent(ref: new Model(type: User::class))
            )
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function getUserById(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($user->isDeleted()) {
            return $this->json(['message' => 'This user has been deleted.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, 200, [], ['groups' => ['user']]);
    }

    #[OA\Post(
        summary: "Borrow a book",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "book_id", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Book borrowed",
                content: new OA\JsonContent(ref: new Model(type: Borrowing::class))
            ),
            new OA\Response(response: 404, description: "Book or User not found"),
            new OA\Response(response: 400, description: "Book is not available")
        ]
    )]
    #[Route('/{user}/borrow/{book}', name: 'borrow_book', methods: ['POST'])]
    public function borrowBook(int $user, int $book): Response
    {
        try {
            $this->userService->borrowBook($user, $book);
            return $this->json(['status' => 'Book borrowed!'], Response::HTTP_CREATED);
        } catch (UserNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (BookNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[OA\Post(
        summary: "Return a borrowed book",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "book_id", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: "Book returned"),
            new OA\Response(response: 400, description: "Book is already returned")
        ]
    )]
    #[Route('/{user}/return/{book}', name: 'return_book', methods: ['POST'])]
    public function returnBook(int $user, int $book): Response
    {
        try {
            $this->userService->returnBook($user, $book);
            return $this->json(['status' => 'Book returned!']);
        } catch (UserNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (BookNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}