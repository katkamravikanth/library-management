<?php

namespace App\Tests\Controller;

use App\Domain\Entity\Book;
use App\Domain\Entity\User;
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
    private EntityRepository $bookRepository;
    private string $path = '/api/users/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // When dealing with actions that lead to redirects, ensuring that assertions are made on the final response after all redirects have been followed.
        $this->client->followRedirects(true);
        
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(User::class);
        $this->bookRepository = $this->manager->getRepository(Book::class);
    }

    public function testGetAllUsers()
    {
        $this->client->request('GET', $this->path);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testGetUserByIdWithInvalidId()
    {
        $this->client->request('GET', sprintf('%s%s', $this->path, 'invalid-id'));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid ID type. ID must be a positive integer.', $responseContent['message']);
    }

    public function testGetUserById()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request('GET', sprintf('%s%s', $this->path, $user->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUserWithMissingName()
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'john.doe.new@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Name, email, and password are required fields', $responseContent['message']);
    }

    public function testCreateUserWithMissingEmail()
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'John Doe',
            'password' => 'password123'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Name, email, and password are required fields', $responseContent['message']);
    }

    public function testCreateUserWithMissingPassword()
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'John Doe',
            'email' => 'john.doe.new@example.com'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Name, email, and password are required fields', $responseContent['message']);
    }

    public function testCreateUser()
    {
        $this->client->request('POST', $this->path . 'new', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'John Doe',
            'email' => 'john.doe.new@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User created!', $responseContent['status']);
    }

    public function testUpdateUserWithMissingName()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $user->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'john.smith.edit@example.com',
            'password' => 'newpassword123'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Name and email are required fields', $responseContent['message']);
    }

    public function testUpdateUserWithMissingEmail()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $user->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'John Smith',
            'password' => 'newpassword123'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Name and email are required fields', $responseContent['message']);
    }

    public function testUpdateUser()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $user->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'John Smith',
            'email' => 'john.smith.edit@example.com',
            'password' => 'newpassword123'
        ]));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User updated!', $responseContent['status']);
    }

    public function testDeleteUserWithInvalidId()
    {
        $invalidUserId = 999999; // Assuming this ID does not exist

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $invalidUserId));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User not found.', $responseContent['message']);
    }

    public function testDeleteUser()
    {
        $user = $this->repository->findOneBy(['email.email' => 'john.smith.edit@example.com']);

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $user->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User deleted!.', $responseContent['message']);

        $deletedUser = $this->repository->findOneBy(['email.email' => 'john.smith.edit@example.com']);
        $this->assertNull($deletedUser);
    }

    public function testBorrowBook()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);
        $book = $this->bookRepository->findOneBy(['title'=> 'Book Title 2']);

        $this->client->request('POST', $this->path . $user->getId() . '/borrow/' . $book->getId());

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book borrowed!', $responseContent['status']);
    }

    public function testReturnBook()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);
        $book = $this->bookRepository->findOneBy(['title'=> 'Book Title 2']);
    
        $this->client->request('POST', $this->path . $user->getId() . '/return/' . $book->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
    
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book returned!', $responseContent['status']);
    }

    public function testBorrowBookWithInvalidUser()
    {
        $this->client->request('POST', $this->path . '999/borrow/1'); // Invalid user ID

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User not found.', $responseContent['message']);
    }

    public function testBorrowBookWithInvalidBook()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);

        $this->client->request('POST', $this->path . $user->getId() . '/borrow/999'); // Invalid book ID

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book not found.', $responseContent['message']);
    }

    public function testReturnBookWithInvalidUser()
    {
        $this->client->request('POST', $this->path . '999/return/1'); // Invalid user ID

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User not found.', $responseContent['message']);
    }

    public function testReturnBookWithInvalidBook()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);

        $this->client->request('POST', $this->path . $user->getId() . '/return/999'); // Invalid book ID

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Book not found.', $responseContent['message']);
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
