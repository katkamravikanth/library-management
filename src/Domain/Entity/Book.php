<?php

namespace App\Domain\Entity;

use App\Domain\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: "books")]
#[ORM\HasLifecycleCallbacks]
class Book
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['book', 'borrowing', 'user'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['book', 'borrowing', 'user'])]
    private $title;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['book', 'borrowing', 'user'])]
    private $author;

    #[ORM\Column(type: 'string', length: 13)]
    #[Assert\NotBlank]
    #[Groups(['book'])]
    private $isbn;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['book'])]
    private $isAvailable;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: Borrowing::class, cascade: ['persist', 'remove'])]
    #[Groups(['book'])]
    private $borrowings;

    public function __construct()
    {
        $this->borrowings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): self
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    /**
     * @return Collection|Borrowing[]
     */
    public function getBorrowings(): Collection
    {
        return $this->borrowings;
    }

    public function borrow(): void
    {
        if (!$this->isAvailable) {
            throw new \Exception("Book not available");
        }

        $this->isAvailable = false;
    }

    public function returnBook(): void
    {
        $this->isAvailable = true;
    }
}