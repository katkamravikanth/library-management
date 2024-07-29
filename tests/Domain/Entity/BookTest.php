<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Book;
use App\Domain\Enum\BookStatus;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    public function testBookCreation(): void
    {
        $faker = Factory::create();
        $isbn = $faker->isbn10;

        $book = new Book();
        $book->setTitle('Test Book');
        $book->setAuthor('Author Name');
        $book->setIsbn($isbn);

        $this->assertEquals('Test Book', $book->getTitle());
        $this->assertEquals('Author Name', $book->getAuthor());
        $this->assertEquals($isbn, $book->getIsbn());
        $this->assertEquals(BookStatus::AVAILABLE, $book->getStatus());
    }

    public function testBookBorrow(): void
    {
        $book = new Book();
        $book->setStatus(BookStatus::AVAILABLE);
        
        $book->borrow();

        $this->assertEquals(BookStatus::BORROWED, $book->getStatus());
    }

    public function testBookReturn(): void
    {
        $book = new Book();
        $book->setStatus(BookStatus::BORROWED);

        $book->returnBook();

        $this->assertEquals(BookStatus::AVAILABLE, $book->getStatus());
    }
}