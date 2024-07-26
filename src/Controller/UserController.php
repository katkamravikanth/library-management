<?php

namespace App\Controller;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use App\Application\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

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
    #[Route('', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $this->passwordHasher->hashPassword(new User(
            new Name($data['name']),
            new Email($data['email']),
            new Password($data['password'])
        ), $data['password']);
        $user = $this->userService->createUser($data['name'], $data['email'], $hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['message' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($user, Response::HTTP_CREATED);
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
    public function updateUser(Request $request, User $user): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = null;
        if (!empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        }
        $updatedUser = $this->userService->updateUser($user, $data['name'], $data['email'], $hashedPassword);

        $errors = $this->validator->validate($updatedUser);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['message' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($updatedUser);
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
    public function deleteUser(User $user): Response
    {
        $this->userService->deleteUser($user);

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
    public function getUserById(User $user): Response
    {
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
        } catch (\Exception $e) {
            return $this->json(['status' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
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
        } catch (\Exception $e) {
            return $this->json(['status' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}