<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\User;
use App\Domain\Entity\Book;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $name = new Name('John Doe');
        $email = new Email('john.doe@example.com');
        $password = new Password('securepassword123');

        $user = new User($name, $email, $password);

        $this->assertEquals('John Doe', (string) $user->getName());
        $this->assertEquals('john.doe@example.com', (string) $user->getEmail());
        $this->assertEquals('securepassword123', (string) $user->getPassword());
    }

    public function testUserRoles(): void
    {
        $name = new Name('John Doe');
        $email = new Email('john.doe@example.com');
        $password = new Password('securepassword123');

        $user = new User($name, $email, $password);
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testUserBorrowingLimit(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has reached the maximum number of borrowed books');

        $name = new Name('John Doe');
        $email = new Email('john.doe@example.com');
        $password = new Password('securepassword123');
        $user = new User($name, $email, $password);

        for ($i = 0; $i < 6; $i++) {
            $book = $this->createMock(Book::class);
            $book->method('getStatus')->willReturn(Book::STATUS_AVAILABLE);
            $user->borrowBook($book);
        }
    }
}