<?php

namespace App\Tests\Service;

use App\Application\Service\UserService;
use App\Domain\Entity\Book;
use App\Domain\Entity\Borrowing;
use App\Domain\Entity\User;
use App\Domain\Repository\BookRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use App\Exception\UserNotFoundException;
use App\Exception\BookNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;
    private ValidatorInterface $validator;
    private UserService $userService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->bookRepository = $container->get(BookRepository::class);
        $this->validator = $container->get(ValidatorInterface::class);
        $this->userService = new UserService($this->userRepository, $this->bookRepository, $this->entityManager, $this->validator);
    }

    public function testCreateUser(): void
    {
        $user = $this->userService->createUser('John Doe', 'john.doe@example.com', 'password123');
        $this->userService->saveUser($user);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->getName()->getValue());
        $this->assertEquals('john.doe@example.com', $user->getEmail()->getValue());
    }

    public function testUpdateUser(): void
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'john.doe@example.com']);

        $updatedUser = $this->userService->updateUser($user, 'Jane Doe', 'jane.doe@example.com', 'newpassword123');
        $this->userService->saveUser($updatedUser);

        $this->assertEquals('Jane Doe', $updatedUser->getName()->getValue());
        $this->assertEquals('jane.doe@example.com', $updatedUser->getEmail()->getValue());
    }

    public function testDeleteUser(): void
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'jane.doe@example.com']);

        $this->userService->deleteUser($user);

        $deletedUser = $this->userRepository->findOneBy(['email.email' => 'jane.doe@example.com']);
        $this->assertNull($deletedUser);
    }

    public function testBorrowBook(): void
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user3@example.com']);
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 3']);

        $this->userService->borrowBook($user->getId(), $book->getId());

        $borrowing = $this->entityManager->getRepository(Borrowing::class)->findOneBy(['user' => $user, 'book' => $book]);
        $this->assertInstanceOf(Borrowing::class, $borrowing);
    }

    public function testReturnBook()
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user3@example.com']);
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 3']);

        $this->userService->returnBook($user->getId(), $book->getId());

        $borrowing = $this->entityManager->getRepository(Borrowing::class)->findOneBy(['user' => $user, 'book' => $book]);
        $this->assertNotNull($borrowing->getCheckinDate());
    }

    public function testBorrowBookUserNotFound()
    {
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 4']);

        $this->expectException(UserNotFoundException::class);
        $this->userService->borrowBook(999, $book->getId());
    }

    public function testBorrowBookBookNotFound()
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user4@example.com']);

        $this->expectException(BookNotFoundException::class);
        $this->userService->borrowBook($user->getId(), 999);
    }

    public function testReturnBookUserNotFound()
    {
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 4']);

        $this->expectException(UserNotFoundException::class);
        $this->userService->returnBook(999, 1);
    }

    public function testReturnBookBookNotFound()
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user4@example.com']);

        $this->expectException(BookNotFoundException::class);
        $this->userService->returnBook($user->getId(), 999);
    }

    protected function restoreExceptionHandler(): void
    {
        while (true) {
            $previousHandler = set_exception_handler(static fn() => null);
            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->restoreExceptionHandler();
    }
}