<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pedido extends Model
{
    use BelongsToEmpresa, LogsActivity, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'orcamento_id',
        'user_id',
        'cliente_id',
        'ano',
        'sequencial',
        'numero',
        'status',
        'data_pedido',
        'hora_pedido',
        'subtotal_sem_desconto',
        'desconto_efetivo_percentual',
        'total_produtos',
        'total_impostos',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'data_pedido' => 'date',
            'subtotal_sem_desconto' => 'decimal:2',
            'desconto_efetivo_percentual' => 'decimal:2',
            'total_produtos' => 'decimal:2',
            'total_impostos' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class)->withTrashed();
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function notaFiscal(): HasOne
    {
        return $this->hasOne(NotaFiscal::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
