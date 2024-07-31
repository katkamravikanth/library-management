<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class UserNotFoundException extends \Exception
{
    protected $message = 'User not found.';

    public function __construct()
    {
        parent::__construct('User not found.', Response::HTTP_NOT_FOUND);
    }
}
