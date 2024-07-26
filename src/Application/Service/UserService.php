<?php

namespace App\Application\Service;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;

    public function __construct(
        UserRepository $userRepository,
        BookRepository $bookRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bookRepository = $bookRepository;
    }

    public function createUser(string $name, string $email, string $password): User
    {
        $user = new User(new Name($name), new Email($email), new Password($password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(User $user, string $name, string $email, ?string $password = null): User
    {
        $user->setName(new Name($name));
        $user->setEmail(new Email($email));
        if ($password !== null) {
            $user->setPassword(new Password($password));
        }

        $this->entityManager->flush();

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function borrowBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        $book = $this->bookRepository->find($bookId);

        if (!$user || !$book) {
            throw new \Exception("User or Book not found");
        }

        $borrowing = $user->borrowBook($book);

        $this->entityManager->persist($borrowing);
        $this->entityManager->flush();
    }

    public function returnBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        $book = $this->bookRepository->find($bookId);

        if (!$user || !$book) {
            throw new \Exception("User or Book not found");
        }

        $user->returnBook($book);

        $this->entityManager->flush();
    }
}