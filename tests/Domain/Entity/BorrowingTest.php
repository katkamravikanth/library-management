<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Book;
use App\Domain\Entity\Borrowing;
use App\Domain\Entity\User;
use App\Domain\Enum\BookStatus;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use PHPUnit\Framework\TestCase;

class BorrowingTest extends TestCase
{
    public function testBorrowingCreation(): void
    {
        $name = new Name('John Doe');
        $email = new Email('john.doe@example.com');
        $password = new Password('securepassword123');
        $user = new User($name, $email, $password);

        $book = new Book();
        $book->setStatus(BookStatus::AVAILABLE);

        $borrowing = new Borrowing($user, $book);

        $this->assertEquals($user, $borrowing->getUser());
        $this->assertEquals($book, $borrowing->getBook());
        $this->assertInstanceOf(\DateTimeInterface::class, $borrowing->getCheckoutDate());
    }

    public function testBorrowingReturn(): void
    {
        $name = new Name('John Doe');
        $email = new Email('john.doe@example.com');
        $password = new Password('securepassword123');
        $user = new User($name, $email, $password);

        $book = new Book();
        $book->setStatus(BookStatus::AVAILABLE);

        $borrowing = new Borrowing($user, $book);

        $this->assertNull($borrowing->getCheckinDate());

        $borrowing->return();

        $this->assertInstanceOf(\DateTimeInterface::class, $borrowing->getCheckinDate());
    }

    public function testReturnAlreadyReturnedBook(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This book is already returned');

        $name = new Name('John Doe');
        $email = new Email('john.doe@example.com');
        $password = new Password('securepassword123');
        $user = new User($name, $email, $password);

        $book = new Book();
        $book->setStatus(BookStatus::AVAILABLE);

        $borrowing = new Borrowing($user, $book);
        $borrowing->return();

        $borrowing->return();  // This should throw an exception
    }
}