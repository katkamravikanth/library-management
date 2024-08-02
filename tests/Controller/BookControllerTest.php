<?php

namespace App\Tests\Controller;

use App\Domain\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/api/books/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);//
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Book::class);
    }

    public function testGetAllBooks()
    {
        $this->client->request('GET', $this->path);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseContent);
    }

    public function testGetBookByIdWithInvalidId()
    {
        $this->client->request('GET', sprintf('%s%s', $this->path, 'invalid-id'));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid ID type. ID must be a positive integer.', $responseContent['message']);
    }

    public function testGetBookById()
    {
        $book = $this->repository->findOneBy(['title'=> 'Book Title 1']);

        $this->client->request('GET', sprintf('%s%s', $this->path, $book->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateBookWithMissingTitle()
    {
        $faker = Factory::create();

        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'author' => 'Test Author',
            'isbn' => $faker->isbn10()
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    public function testCreateBookWithMissingAuthor()
    {
        $faker = Factory::create();

        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Test Book',
            'isbn' => $faker->isbn10()
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    public function testCreateBookWithMissingIsbn()
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Title',
            'author' => 'New Author'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    public function testCreateBookWithInvalidIsbn()
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => 'invalidisbn'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('ISBN value is not valid.', $responseContent['message']);
    }

    public function testCreateBook()
    {
        $faker = Factory::create();

        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => $faker->isbn10()
        ]));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book created!', $responseContent['status']);
    }

    public function testUpdateBookWithMissingTitle()
    {
        $faker = Factory::create();

        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'author' => 'New Author',
            'isbn' => $faker->isbn10()
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    public function testUpdateBookWithMissingAuthor()
    {
        $faker = Factory::create();

        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Title',
            'isbn' => $faker->isbn10()
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    public function testUpdateBookWithMissingIsbn()
    {
        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Title',
            'author' => 'New Author'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    public function testUpdateBookWithInvalidIsbn()
    {
        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Title',
            'author' => 'New Author',
            'isbn' => 'invalidisbn'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('ISBN value is not valid.', $responseContent['message']);
    }

    public function testUpdateBookNotFound()
    {
        $faker = Factory::create();

        $invalidBookId = 999999; // Assuming this ID does not exist

        $this->client->request('PUT', sprintf('%s%s', $this->path, $invalidBookId), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Title',
            'author' => 'New Author',
            'isbn' => $faker->isbn10()
        ]));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book not found.', $responseContent['message']);
    }

    public function testUpdateBook()
    {
        $faker = Factory::create();
        $book = $this->repository->findOneBy(['title'=> 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Updated Test Book',
            'author' => 'Updated Test Author',
            'isbn' => $faker->isbn10()
        ]));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book updated!', $responseContent['status']);
    }

    public function testDeleteBookNotFound()
    {
        $invalidBookId = 999999; // Assuming this ID does not exist

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $invalidBookId));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book not found.', $responseContent['message']);
    }

    public function testDeleteBook()
    {
        $book = $this->repository->findOneBy(['title'=> 'Updated Test Book']);

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $book->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book deleted!.', $responseContent['message']);

        $deletedBook = $this->repository->findOneBy(['title'=> 'Updated Test Book']);
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
