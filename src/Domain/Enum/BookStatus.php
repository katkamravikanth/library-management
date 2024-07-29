<?php

namespace App\Domain\Enum;

enum BookStatus: string
{
    case AVAILABLE = 'Available';
    case BORROWED = 'Borrowed';
    case DELETED = 'Deleted';
}