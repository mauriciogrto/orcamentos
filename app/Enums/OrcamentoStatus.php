<?php

namespace App\Enums;

enum OrcamentoStatus: string
{
    case Rascunho = 'rascunho';
    case Enviado = 'enviado';
    case Aprovado = 'aprovado';
    case Rejeitado = 'rejeitado';
    case Expirado = 'expirado';
    case Finalizado = 'finalizado';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Enviado => 'Enviado',
            self::Aprovado => 'Aprovado',
            self::Rejeitado => 'Rejeitado',
            self::Expirado => 'Expirado',
            self::Finalizado => 'Finalizado',
        };
    }
}
