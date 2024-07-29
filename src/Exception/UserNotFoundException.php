<?php

namespace App\Exception;

class UserNotFoundException extends \Exception
{
    protected $message = 'User not found.';
}
