<?php

namespace App\Enums;

enum FormaPagamento: string
{
    case Pix = 'pix';
    case Boleto = 'boleto';
    case Cartao = 'cartao';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'PIX',
            self::Boleto => 'Boleto',
            self::Cartao => 'Cartão',
        };
    }
}
