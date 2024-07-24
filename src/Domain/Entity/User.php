<?php

namespace App\Domain\Entity;

use App\Domain\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user', 'borrowing', 'book'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user', 'borrowing', 'book'])]
    private $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user'])]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private $password;

    #[ORM\Column(type: 'json')]
    #[Groups(['user'])]
    private $roles = [];

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Borrowing::class, cascade: ['persist', 'remove'])]
    #[Groups(['user'])]
    private $borrowings;

    public function __construct()
    {
        $this->borrowings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|Borrowing[]
     */
    public function getBorrowings(): Collection
    {
        return $this->borrowings;
    }

    public function borrows(Book $book): Borrowing
    {
        if (count($this->activeBorrowedBook()) >= 5) {
            throw new \Exception("User has reached the maximum number of borrowed books");
        }

        if (!$book->getIsAvailable()) {
            throw new \Exception("Book is not available");
        }

        $borrowing = new Borrowing($this, $book);
        $this->borrowings[] = $borrowing;
        $book->borrow();

        return $borrowing;
    }

    public function returnBook(Borrowing $borrowing): void
    {
        if (!$this->activeBorrowedBook()->contains($borrowing)) {
            throw new \Exception("This borrowing record does not belong to the user");
        }

        $borrowing->return();
    }

    public function hasBorrowedBook(Book $book): bool
    {
        $borrowing = $this->activeBorrowedBook()->filter(
            function (Borrowing $borrowing) use ($book) {
                return $borrowing->getBook() === $book;
            }
        );

        return $borrowing->count() > 0;
    }

    /**
     * @return Collection|Borrowing[]
     */
    public function activeBorrowedBook(): Collection
    {
        return $this->borrowings->filter(
            function (Borrowing $borrowing) {
                return $borrowing->getCheckinDate() === null;
            }
        );
    }
}