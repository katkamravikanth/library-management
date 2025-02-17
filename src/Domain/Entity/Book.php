<?php

namespace App\Domain\Entity;

use App\Domain\Enum\BookStatus;
use App\Domain\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: "books")]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Book
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['book', 'borrowing', 'user'])]
    private $id;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: "Title should not be blank.")]
    #[Assert\Length(max: 150, maxMessage: 'Your title cannot be longer than {{ limit }} characters')]
    #[Groups(['book', 'borrowing', 'user'])]
    private $title;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: "Author should not be blank.")]
    #[Assert\Length(max: 150, maxMessage: 'Your author cannot be longer than {{ limit }} characters')]
    #[Groups(['book', 'borrowing', 'user'])]
    private $author;

    #[ORM\Column(type: 'string', length: 13)]
    #[Assert\NotBlank(message: "ISBN should not be blank.")]
    #[Assert\Isbn(
        type: Assert\Isbn::ISBN_10,
        message: 'ISBN value is not valid.',
    )]
    #[Groups(['book'])]
    private $isbn;

    #[ORM\Column(type: 'string', enumType: BookStatus::class)]
    #[Groups(['book'])]
    private $status;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: Borrowing::class, cascade: ['persist', 'remove'])]
    #[Groups(['book'])]
    private $borrowings;

    public function __construct()
    {
        $this->borrowings = new ArrayCollection();
        $this->status = BookStatus::AVAILABLE; // Default status
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

    public function getStatus(): BookStatus
    {
        return $this->status;
    }

    public function setStatus(BookStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection|Borrowing[]
     */
    public function getBorrowings(): Collection
    {
        return $this->borrowings;
    }

    public function markBookBorrowed(): void
    {
        if ($this->status !== BookStatus::AVAILABLE) {
            throw new \Exception("Book not available");
        }

        $this->status = BookStatus::BORROWED;
    }

    public function markBookAvailable(): void
    {
        $this->status = BookStatus::AVAILABLE;
    }
}