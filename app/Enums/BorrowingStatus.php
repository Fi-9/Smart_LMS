<?php

namespace App\Enums;

enum BorrowingStatus: string
{
    case BORROWED = 'borrowed';
    case RETURNED = 'returned';
    case LATE = 'late';
}
