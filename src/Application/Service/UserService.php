<?php

namespace App\Application\Service;

use App\Domain\Repository\UserRepository;
use App\Domain\Repository\BookRepository;
use App\Domain\Repository\BorrowingRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;
    private BorrowingRepository $borrowingRepository;

    public function __construct(UserRepository $userRepository, BookRepository $bookRepository, BorrowingRepository $borrowingRepository, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bookRepository = $bookRepository;
        $this->borrowingRepository = $borrowingRepository;
    }

    public function borrowBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        $book = $this->bookRepository->find($bookId);

        if (!$user || !$book) {
            throw new \Exception("User or Book not found");
        }

        // Make sure this logic is in place
        if ($user->hasBorrowedBook($book)) {
            throw new \Exception("Book already borrowed by this user");
        }

        $borrowing = $user->borrows($book);

        $this->entityManager->persist($borrowing);
        $this->entityManager->flush();
    }

    public function  returnBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        $book = $this->bookRepository->find($bookId);

        if (!$user || !$book) {
            throw new \Exception("User or Book not found");
        }

        $borrowing = $this->borrowingRepository->findOneBy(['user' => $user, 'book' => $book, 'checkinDate' => null]);

        if (!$borrowing) {
            throw new \Exception('Borrowing record not found');
        }

        $user->returnBook($borrowing);

        $this->entityManager->persist($borrowing);
        $this->entityManager->flush();
    }
}