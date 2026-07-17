<?php

namespace App\Enums;

enum ResultType: string
{
    case Numeric = 'numeric';
    case Text = 'text';
    case Select = 'select';
}
