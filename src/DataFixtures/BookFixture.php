<?php

namespace App\DataFixtures;

use App\Domain\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class BookFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        // Create Books
        for ($i = 1; $i <= 20; $i++) {
            $book = new Book();
            $book->setTitle("Book Title {$i}");
            $book->setAuthor("Author Name {$i}");
            $book->setIsbn($faker->isbn10);

            $manager->persist($book);
        }

        $manager->flush();
    }
}