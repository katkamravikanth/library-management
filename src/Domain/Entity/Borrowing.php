<?php

namespace App\Domain\Entity;

use App\Domain\Repository\BorrowingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BorrowingRepository::class)]
#[ORM\Table(name: "borrowings")]
#[ORM\HasLifecycleCallbacks]
class Borrowing
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['borrowing', 'user', 'book'])]
    private $id;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'borrowings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['borrowing', 'user'])]
    private $book;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'borrowings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['borrowing', 'book'])]
    private $user;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['borrowing'])]
    #[Assert\NotNull]
    private $checkoutDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['borrowing'])]
    private $checkinDate;

    public function __construct(User $user, Book $book)
    {
        $this->user = $user;
        $this->book = $book;
        $this->checkoutDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCheckoutDate(): ?\DateTimeInterface
    {
        return $this->checkoutDate;
    }

    public function setCheckoutDate(\DateTimeInterface $checkoutDate): self
    {
        $this->checkoutDate = $checkoutDate;

        return $this;
    }

    public function getCheckinDate(): ?\DateTimeInterface
    {
        return $this->checkinDate;
    }

    public function setCheckinDate(?\DateTimeInterface $checkinDate): self
    {
        $this->checkinDate = $checkinDate;

        return $this;
    }

    public function return(): void
    {
        if ($this->checkinDate !== null) {
            throw new \Exception("This book is already returned");
        }

        $this->checkinDate = new \DateTime();
        $this->book->returnBook();
    }
}