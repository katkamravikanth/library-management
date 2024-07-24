<?php

namespace App\Tests\Service;

use App\Application\Service\UserService;
use App\Domain\Entity\User;
use App\Domain\Entity\Book;
use App\Domain\Entity\Borrowing;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\BookRepository;
use App\Domain\Repository\BorrowingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private $userRepository;
    private $bookRepository;
    private $borrowingRepository;
    private $entityManager;
    private $userService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->borrowingRepository = $this->createMock(BorrowingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->bookRepository,
            $this->borrowingRepository,
            $this->entityManager
        );
    }

    public function testBorrowBookSuccess(): void
    {
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        $borrowing = $this->createMock(Borrowing::class);

        $this->userRepository->method('find')->willReturn($user);
        $this->bookRepository->method('find')->willReturn($book);

        $user->method('hasBorrowedBook')->with($book)->willReturn(false);
        $user->method('borrows')->with($book)->willReturn($borrowing);

        $this->entityManager->expects($this->once())->method('persist')->with($borrowing);
        $this->entityManager->expects($this->once())->method('flush');

        $this->userService->borrowBook(1, 1);
    }

    public function testBorrowBookUserNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User or Book not found');

        $this->userRepository->method('find')->willReturn(null);
        $this->bookRepository->method('find')->willReturn($this->createMock(Book::class));

        $this->userService->borrowBook(1, 1);
    }

    public function testBorrowBookAlreadyBorrowed(): void
    {
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);

        $this->userRepository->method('find')->willReturn($user);
        $this->bookRepository->method('find')->willReturn($book);

        $user->method('hasBorrowedBook')->with($book)->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Book already borrowed by this user');

        $this->userService->borrowBook(1, 1);
    }

    public function testReturnBookSuccess(): void
    {
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        $borrowing = $this->createMock(Borrowing::class);

        $this->userRepository->method('find')->willReturn($user);
        $this->bookRepository->method('find')->willReturn($book);
        $this->borrowingRepository->method('findOneBy')->willReturn($borrowing);

        $user->expects($this->once())->method('returnBook')->with($borrowing);

        $this->entityManager->expects($this->once())->method('persist')->with($borrowing);
        $this->entityManager->expects($this->once())->method('flush');

        $this->userService->returnBook(1, 1);
    }

    public function testReturnBookUserNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User or Book not found');

        $this->userRepository->method('find')->willReturn(null);
        $this->bookRepository->method('find')->willReturn($this->createMock(Book::class));

        $this->userService->returnBook(1, 1);
    }

    public function testReturnBookNotBorrowed(): void
    {
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);

        $this->userRepository->method('find')->willReturn($user);
        $this->bookRepository->method('find')->willReturn($book);
        $this->borrowingRepository->method('findOneBy')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Borrowing record not found');

        $this->userService->returnBook(1, 1);
    }
}