<?php

namespace App\DataFixtures;

use App\Domain\Entity\User;
use App\Domain\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $faker = FakerFactory::create();

        // Create Users
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setName($faker->name);
            $user->setEmail($faker->unique()->safeEmail);
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
            $users[] = $user;
        }

        // Create Books
        $books = [];
        for ($i = 0; $i < 20; $i++) {
            $book = new Book();
            $book->setTitle($faker->sentence(3));
            $book->setAuthor($faker->name);
            $book->setIsbn($faker->isbn13);
            $book->setIsAvailable(1);
            $manager->persist($book);
            $books[] = $book;
        }

        $manager->flush();
    }
}