<?php

namespace App\Domain\Entity;

use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Password;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user', 'borrowing', 'book'])]
    private $id;

    #[ORM\Embedded(class: Name::class, columnPrefix: false)]
    #[Groups(['user', 'borrowing', 'book'])]
    private Name $name;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    #[Groups(['user'])]
    private Email $email;

    #[ORM\Embedded(class: Password::class, columnPrefix: false)]
    private Password $password;

    #[ORM\Column(type: 'json')]
    #[Groups(['user'])]
    private $roles = [];

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Borrowing::class, cascade: ['persist', 'remove'])]
    #[Groups(['user'])]
    private $borrowings;

    public function __construct(Name $name, Email $email, Password $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->roles = ['ROLE_USER'];
        $this->borrowings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(Password $password): self
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

    public function borrowBook(Book $book): Borrowing
    {
        if (count($this->activeBorrowedBooks()) >= 5) {
            throw new \Exception("User has reached the maximum number of borrowed books");
        }

        if ($book->getStatus() !== Book::STATUS_AVAILABLE) {
            throw new \Exception("Book is not available");
        }

        $borrowing = new Borrowing($this, $book);
        $this->borrowings[] = $borrowing;
        $book->borrow();

        return $borrowing;
    }

    public function returnBook(Book $book): void
    {
        $borrowing = $this->activeBorrowedBooks()->filter(
            fn (Borrowing $borrowing) => $borrowing->getBook() === $book
        )->first();

        if (!$borrowing) {
            throw new \Exception("This borrowing record does not exist or book is already returned");
        }

        $borrowing->return();
    }

    public function hasBorrowedBook(Book $book): bool
    {
        $borrowing = $this->activeBorrowedBooks()->filter(
            fn (Borrowing $borrowing) => $borrowing->getBook() === $book
        );

        return $borrowing->count() > 0;
    }

    public function activeBorrowedBooks(): Collection
    {
        return $this->borrowings->filter(
            fn (Borrowing $borrowing) => $borrowing->getCheckinDate() === null
        );
    }
}