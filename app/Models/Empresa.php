<?php

namespace App\Models;

use App\Enums\EmpresaStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome_fantasia',
        'razao_social',
        'cnpj',
        'telefone',
        'email',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'logo_path',
        'status',
        'pedido_minimo',
        'limite_frete_fob',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmpresaStatus::class,
            'pedido_minimo' => 'decimal:2',
            'limite_frete_fob' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function faixasComissao(): HasMany
    {
        return $this->hasMany(FaixaComissao::class);
    }

    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class);
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class)
            ->withPivot('ativo');
    }

    public function configuracaoFiscal()
    {
        return $this->hasOne(EmpresaConfiguracaoFiscal::class);
    }

    public function notasFiscais(): HasMany
    {
        return $this->hasMany(NotaFiscal::class);
    }

    public function moduloAtivo(string $slug): bool
    {
        if ($this->relationLoaded('modulos')) {
            return $this->modulos->contains(
                fn (Modulo $modulo) => $modulo->slug === $slug && (bool) $modulo->pivot->ativo,
            );
        }

        return $this->modulos()
            ->where('modulos.slug', $slug)
            ->wherePivot('ativo', true)
            ->exists();
    }
}
