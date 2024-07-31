<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Book;
use App\Domain\Enum\BookStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Book::class);
    }

    public function testCreateBook()
    {
        $book = new Book();
        $book->setTitle('Sample Book Title');
        $book->setAuthor('Sample Author');
        $book->setIsbn('1234567890123');
        $book->setStatus(BookStatus::AVAILABLE);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $savedBook = $this->repository->findOneBy(['isbn' => '1234567890123']);

        $this->assertInstanceOf(Book::class, $savedBook);
        $this->assertEquals('Sample Book Title', $savedBook->getTitle());
        $this->assertEquals('Sample Author', $savedBook->getAuthor());
        $this->assertEquals('1234567890123', $savedBook->getIsbn());
        $this->assertEquals(BookStatus::AVAILABLE, $savedBook->getStatus());
    }

    public function testReadBook()
    {
        $book = $this->repository->findOneBy(['isbn' => '1234567890123']);

        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('Sample Book Title', $book->getTitle());
        $this->assertEquals('Sample Author', $book->getAuthor());
    }

    public function testUpdateBook()
    {
        $book = $this->repository->findOneBy(['isbn' => '1234567890123']);
        
        $book->setTitle('Updated Book Title');
        $this->entityManager->flush();

        $updatedBook = $this->repository->findOneBy(['isbn' => '1234567890123']);
        $this->assertEquals('Updated Book Title', $updatedBook->getTitle());
    }

    public function testDeleteBook()
    {
        $book = $this->repository->findOneBy(['isbn' => '1234567890123']);

        $this->entityManager->remove($book);
        $this->entityManager->flush();

        $deletedBook = $this->repository->findOneBy(['isbn' => '1234567890123']);
        $this->assertNull($deletedBook);
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
