<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class BookNotFoundException extends \Exception
{
    protected $message = 'Book not found.';

    public function __construct()
    {
        parent::__construct('Book not found.', Response::HTTP_NOT_FOUND);
    }
}
