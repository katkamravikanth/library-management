<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Password
{
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Password should not be blank.")]
    #[Assert\Length(min: 8, minMessage: 'Your password must be at least {{ limit }} characters long')]
    private string $password;

    public function __construct(string $password)
    {
        $this->password = $password;
    }

    public function __toString(): string
    {
        return $this->password;
    }
}