<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Email
{
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: "Email should not be blank.")]
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getValue(): string
    {
        return $this->email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}