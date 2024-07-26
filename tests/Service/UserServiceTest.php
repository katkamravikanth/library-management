<?php

namespace App\Tests\Service;

use App\Application\Service\UserService;
use App\Domain\Entity\User;
use App\Domain\Entity\Book;
use App\Domain\Entity\Borrowing;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private $userRepository;
    private $bookRepository;
    private $entityManager;
    private $userService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->bookRepository,
            $this->entityManager
        );
    }

    public function testCreateUser(): void
    {
        $name = 'Test User';
        $email = 'test@example.com';
        $password = 'password123';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $user = $this->userService->createUser($name, $email, $password);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($name, $user->getName()->getValue());
        $this->assertEquals($email, $user->getEmail()->getValue());
        $this->assertEquals($password, $user->getPassword());
    }

    public function testUpdateUser(): void
    {
        $user = $this->createMock(User::class);
        $name = 'Updated User';
        $email = 'updated@example.com';
        $password = 'newpassword123';

        $user->expects($this->once())
            ->method('setName')
            ->with($this->isInstanceOf(Name::class));

        $user->expects($this->once())
            ->method('setEmail')
            ->with($this->isInstanceOf(Email::class));

        if ($password !== null) {
            $user->expects($this->once())
                ->method('setPassword')
                ->with($this->isInstanceOf(Password::class));
        }

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updatedUser = $this->userService->updateUser($user, $name, $email, $password);

        $this->assertInstanceOf(User::class, $updatedUser);
    }

    public function testDeleteUser(): void
    {
        $user = $this->createMock(User::class);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userService->deleteUser($user);
    }

    public function testBorrowBookSuccess(): void
    {
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        $borrowing = $this->createMock(Borrowing::class);

        $this->userRepository->method('find')->willReturn($user);
        $this->bookRepository->method('find')->willReturn($book);

        $user->method('borrowBook')->with($book)->willReturn($borrowing);

        $this->entityManager->expects($this->once())->method('persist')->with($borrowing);
        $this->entityManager->expects($this->once())->method('flush');

        $this->userService->borrowBook(1, 1);
    }

    public function testBorrowBookUserOrBookNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User or Book not found');

        $this->userRepository->method('find')->willReturn(null);
        $this->bookRepository->method('find')->willReturn($this->createMock(Book::class));

        $this->userService->borrowBook(1, 1);
    }

    public function testReturnBookSuccess(): void
    {
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);

        $this->userRepository->method('find')->willReturn($user);
        $this->bookRepository->method('find')->willReturn($book);

        $user->expects($this->once())->method('returnBook')->with($book);

        $this->entityManager->expects($this->once())->method('flush');

        $this->userService->returnBook(1, 1);
    }

    public function testReturnBookUserOrBookNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User or Book not found');

        $this->userRepository->method('find')->willReturn(null);
        $this->bookRepository->method('find')->willReturn($this->createMock(Book::class));

        $this->userService->returnBook(1, 1);
    }
}