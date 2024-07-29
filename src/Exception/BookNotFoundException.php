<?php

namespace App\Exception;

class BookNotFoundException extends \Exception
{
    protected $message = 'Book not found.';
}
