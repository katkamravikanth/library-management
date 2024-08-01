<?php

namespace App\Tests\Service;

use App\Application\Service\BookService;
use App\Domain\Entity\Book;
use App\Domain\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $repository;
    private ValidatorInterface $validator;
    private BookService $bookService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(BookRepository::class);
        $this->validator = $container->get(ValidatorInterface::class);
        $this->bookService = new BookService($this->repository, $this->entityManager, $this->validator);
    }

    public function testCreateBook(): void
    {
        $faker = Factory::create();

        $data = ['title' => 'New Book Title 1', 'author' => 'New Author 1', 'isbn' => $faker->isbn10()];
        $book = $this->bookService->createBook($data);
        $this->bookService->saveBook($book);

        $savedBook = $this->repository->findOneBy(['title' => 'New Book Title 1']);

        $this->assertInstanceOf(Book::class, $savedBook);
        $this->assertEquals('New Book Title 1', $savedBook->getTitle());
        $this->assertEquals('New Author 1', $savedBook->getAuthor());
    }

    public function testUpdateBook(): void
    {
        $book = $this->repository->findOneBy(['title' => 'New Book Title 1']);
        $data = ['title' => 'New Book Title 1', 'author' => 'Updated Author 1', 'isbn' => $book->getIsbn()];

        $updatBook = $this->bookService->updateBook($book, $data);
        $this->bookService->saveBook($updatBook);

        $updatedBook = $this->repository->findOneBy(['title' => 'New Book Title 1']);
        $this->assertEquals('Updated Author 1', $updatedBook->getAuthor());
    }

    public function testDeleteBook(): void
    {
        $book = $this->repository->findOneBy(['title' => 'New Book Title 1']);

        $this->bookService->deleteBook($book);

        $deletedBook = $this->repository->findOneBy(['title' => 'New Book Title 1']);
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