<?php

namespace App\DataFixtures;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        // Create Users
        for ($i = 1; $i <= 10; $i++) {
            $user = new User(
                new Name("User {$i}"),
                new Email("user{$i}@example.com"),
                new Password('password')
            );
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword(new Password($hashedPassword));

            $manager->persist($user);
        }

        $manager->flush();
    }
}