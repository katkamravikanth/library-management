<?php

namespace App\Domain\Entity;

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

    const STATUS_AVAILABLE = 'Available';
    const STATUS_BORROWED = 'Borrowed';
    const STATUS_DELETED = 'Deleted';

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

    #[ORM\Column(type: "string", length: 15)]
    #[Groups(['book'])]
    private $status;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
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

    public function borrow(): void
    {
        if ($this->status !== self::STATUS_AVAILABLE) {
            throw new \Exception("Book not available");
        }

        $this->status = self::STATUS_BORROWED;
    }

    public function returnBook(): void
    {
        $this->status = self::STATUS_AVAILABLE;
    }
}