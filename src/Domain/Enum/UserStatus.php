<?php

namespace App\Domain\Enum;

enum UserStatus: string
{
    case ACTIVE = 'Active';
    case DELETED = 'Deleted';
}