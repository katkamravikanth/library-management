<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(User::class);
    }

    public function testCreateUser()
    {
        $user = new User(new Name('John Doe Me'), new Email('john.doe.me@example.com'), new Password('password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $savedUser = $this->repository->findOneBy(['email.email' => 'john.doe.me@example.com']);

        $this->assertInstanceOf(User::class, $savedUser);
        $this->assertEquals('John Doe Me', $savedUser->getName());
        $this->assertEquals('john.doe.me@example.com', $savedUser->getEmail());
    }

    public function testReadUser()
    {
        $user = $this->repository->findOneBy(['email.email' => 'john.doe.me@example.com']);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe Me', $user->getName());
    }

    public function testUpdateUser()
    {
        $user = $this->repository->findOneBy(['email.email' => 'john.doe.me@example.com']);
        
        $user->setName(new Name('Jane Doe'));
        $this->entityManager->flush();

        $updatedUser = $this->repository->findOneBy(['email.email' => 'john.doe.me@example.com']);
        $this->assertEquals('Jane Doe', $updatedUser->getName());
    }

    public function testDeleteUser()
    {
        $user = $this->repository->findOneBy(['email.email' => 'john.doe.me@example.com']);

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $deletedUser = $this->repository->findOneBy(['email.email' => 'john.doe.me@example.com']);
        $this->assertNull($deletedUser);
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