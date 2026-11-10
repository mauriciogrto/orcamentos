<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\FreteTipo;
use App\Enums\OrcamentoFinalidade;
use App\Enums\OrcamentoStatus;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Orcamento extends Model
{
    use BelongsToEmpresa, LogsActivity, SoftDeletes;

    protected $fillable = [
        'empresa_id', 'user_id', 'cliente_id', 'ano', 'sequencial', 'numero', 'status',
        'data_emissao', 'hora_emissao', 'forma_pagamento', 'boleto_prazos',
        'finalidade', 'validade_dias', 'observacoes', 'frete_tipo', 'subtotal_sem_desconto',
        'desconto_efetivo_percentual', 'total_produtos', 'total_impostos', 'valor_total',
        'comissao_percentual', 'valor_comissao',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrcamentoStatus::class,
            'forma_pagamento' => FormaPagamento::class,
            'finalidade' => OrcamentoFinalidade::class,
            'frete_tipo' => FreteTipo::class,
            'data_emissao' => 'date',
            'subtotal_sem_desconto' => 'decimal:2',
            'desconto_efetivo_percentual' => 'decimal:2',
            'total_produtos' => 'decimal:2',
            'total_impostos' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'comissao_percentual' => 'decimal:2',
            'valor_comissao' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class);
    }

    public function pedido(): HasOne
    {
        return $this->hasOne(Pedido::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
