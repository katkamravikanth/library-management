<?php

namespace App\Tests\Controller;

use App\Domain\Entity\Book;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/api/users/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $this->manager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testCreateUser()
    {
        $this->client->request('POST', $this->path . 'new', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'John Doe',
            'email' => 'john.doe.new@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateUser()
    {
        $fixture = new User(new Name('John Doe'), new Email('edit.user@example.com'), new Password('password'));

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('PUT', sprintf('%s%s', $this->path, $fixture->getId()), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'John Smith',
            'email' => 'john.smith.edit@example.com',
            'password' => 'newpassword123'
        ]));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteUser()
    {
        $fixture = new User(new Name('John Doe'), new Email('remove.user@example.com'), new Password('password'));

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $fixture->getId()));

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testGetAllUsers()
    {
        $this->client->request('GET', $this->path);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testGetUserById()
    {
        $fixture = new User(new Name('John Doe'), new Email('show.user@example.com'), new Password('password'));

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testBorrowBook()
    {
        $userFixture = new User(new Name('John Doe'), new Email('borrow.user@example.com'), new Password('password'));
        $bookFixture = new Book();
        $bookFixture->setTitle('Book Title 1');
        $bookFixture->setAuthor('Book Author 1');
        $bookFixture->setIsbn('0-19-853453-1');
        $bookFixture->setStatus(Book::STATUS_AVAILABLE);

        $this->manager->persist($userFixture);
        $this->manager->persist($bookFixture);
        $this->manager->flush();

        $this->client->request('POST', $this->path . $userFixture->getId() . '/borrow/' . $bookFixture->getId());

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book borrowed!', $responseContent['status']);
    }

    public function testReturnBook()
    {
        $userFixture = new User(new Name('John Doe'), new Email('return.user@example.com'), new Password('password'));
        $bookFixture = new Book();
        $bookFixture->setTitle('Book Title 1');
        $bookFixture->setAuthor('Book Author 1');
        $bookFixture->setIsbn('0-19-853453-1');
        $bookFixture->setStatus(Book::STATUS_AVAILABLE);

        $this->manager->persist($userFixture);
        $this->manager->persist($bookFixture);
        $this->manager->flush();

        $this->client->request('POST', $this->path . $userFixture->getId() . '/borrow/' . $bookFixture->getId());

        $this->client->request('POST', $this->path . $userFixture->getId() . '/return/' . $bookFixture->getId());

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book returned!', $responseContent['status']);
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
