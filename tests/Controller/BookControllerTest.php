<?php

namespace App\Tests\Controller;

use App\Domain\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private string $path = '/api/books/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $this->manager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testCreateBook()
    {
        $faker = Factory::create();

        $this->client->request('POST', $this->path . 'new', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => $faker->isbn10
        ]));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book created!', $responseContent['status']);
    }

    public function testUpdateBook()
    {
        $faker = Factory::create();

        $fixture = new Book();
        $fixture->setTitle('Book Title 1');
        $fixture->setAuthor('Book Author 1');
        $fixture->setIsbn($faker->isbn10);
        $fixture->setStatus(Book::STATUS_AVAILABLE);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('PUT', sprintf('%s%s', $this->path, $fixture->getId()), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Updated Test Book',
            'author' => 'Updated Test Author',
            'isbn' => $faker->isbn10
        ]));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book updated!', $responseContent['status']);
    }

    public function testDeleteBook()
    {
        $faker = Factory::create();

        $fixture = new Book();
        $fixture->setTitle('Book Title 2');
        $fixture->setAuthor('Book Author 2');
        $fixture->setIsbn($faker->isbn10);
        $fixture->setStatus(Book::STATUS_AVAILABLE);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $fixture->getId()));

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testGetAllBooks()
    {
        $this->client->request('GET', $this->path);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseContent);
    }

    public function testGetBookById()
    {
        $fixture = new Book();
        $fixture->setTitle('Book Title 3');
        $fixture->setAuthor('Book Author 3');
        $fixture->setIsbn('0-19-853453-4');
        $fixture->setStatus(Book::STATUS_AVAILABLE);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($fixture->getTitle(), $responseContent['title']);
        $this->assertEquals($fixture->getAuthor(), $responseContent['author']);
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
