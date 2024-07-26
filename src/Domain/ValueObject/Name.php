<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Name
{
    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: "Name should not be blank.")]
    #[Assert\Length(max: 150, maxMessage: 'Your name cannot be longer than {{ limit }} characters')]
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}