<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Book;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    public function testBookCreation(): void
    {
        $book = new Book();
        $book->setTitle('Test Book');
        $book->setAuthor('Author Name');
        $book->setIsbn('1234567890123');
        $book->setStatus(Book::STATUS_AVAILABLE);

        $this->assertEquals('Test Book', $book->getTitle());
        $this->assertEquals('Author Name', $book->getAuthor());
        $this->assertEquals('1234567890123', $book->getIsbn());
        $this->assertEquals(Book::STATUS_AVAILABLE, $book->getStatus());
    }

    public function testBookBorrow(): void
    {
        $book = new Book();
        $book->setStatus(Book::STATUS_AVAILABLE);
        
        $book->borrow();

        $this->assertEquals(Book::STATUS_BORROWED, $book->getStatus());
    }

    public function testBookReturn(): void
    {
        $book = new Book();
        $book->setStatus(Book::STATUS_BORROWED);

        $book->returnBook();

        $this->assertEquals(Book::STATUS_AVAILABLE, $book->getStatus());
    }
}