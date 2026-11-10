<?php

namespace App\Enums;

enum FreteTipo: string
{
    case Cif = 'cif';
    case Fob = 'fob';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}
