<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class UserNotFoundException extends \Exception
{
    protected $message = 'User not found.';

    public function __construct()
    {
        parent::__construct($this->message, Response::HTTP_NOT_FOUND);
    }
}
