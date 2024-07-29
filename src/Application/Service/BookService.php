<?php

namespace App\Application\Service;

use App\Domain\Entity\Book;
use App\Domain\Enum\BookStatus;
use App\Domain\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookService
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private ValidatorInterface $validator;

    public function __construct(
        BookRepository $bookRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->bookRepository = $bookRepository;
        $this->validator = $validator;
    }

    public function createBook(array $data): Book
    {
        $book = new Book();
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);

        return $book;
    }

    public function updateBook(Book $book, array $data): Book
    {
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);

        return $book;
    }

    public function saveBook(Book $book): void
    {
        $errors = $this->validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \Exception(implode(', ', $errorMessages));
        }

        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }

    public function deleteBook(Book $book): void
    {
        $book->setStatus(BookStatus::DELETED);

        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }
}