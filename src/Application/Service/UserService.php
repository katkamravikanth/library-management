<?php

namespace App\Application\Service;

use App\Domain\Entity\Borrowing;
use App\Domain\Entity\User;
use App\Domain\Enum\UserStatus;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\BookRepository;
use App\Exception\UserNotFoundException;
use App\Exception\BookNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;
    private ValidatorInterface $validator;

    public function __construct(
        UserRepository $userRepository,
        BookRepository $bookRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bookRepository = $bookRepository;
        $this->validator = $validator;
    }

    public function createUser(string $name, string $email, string $password): User
    {
        $user = new User(new Name($name), new Email($email), new Password($password));

        return $user;
    }

    public function updateUser(User $user, string $name, string $email, ?string $password = null): User
    {
        $user->setName(new Name($name));
        $user->setEmail(new Email($email));
        if ($password !== null) {
            $user->setPassword(new Password($password));
        }

        return $user;
    }

    public function saveUser(User $user): void
    {
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \Exception(implode(', ', $errorMessages));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function deleteUser(User $user): void
    {
        $user->setStatus(UserStatus::DELETED);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function borrowBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $book = $this->bookRepository->find($bookId);
        if (!$book) {
            throw new BookNotFoundException();
        }

        if ($this->userRepository->getActiveBorrowingsCount($user) >= 5) {
            throw new \Exception("User has reached the maximum number of borrowed books");
        }

        $borrowing = new Borrowing($user, $book);
        $book->markBookBorrowed();

        $this->entityManager->persist($borrowing);
        $this->entityManager->flush();
    }

    public function returnBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $book = $this->bookRepository->find($bookId);
        if (!$book) {
            throw new BookNotFoundException();
        }

        $borrowing = $this->userRepository->findActiveBorrowing($user, $book);
        if (!$borrowing) {
            throw new \Exception('Borrowing record not found or book is already returned');
        }

        $borrowing->return();

        $this->entityManager->flush();
    }
}