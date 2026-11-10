<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrcamentoItem extends Model
{
    use BelongsToEmpresa, LogsActivity;

    protected $table = 'orcamento_itens';

    protected $fillable = [
        'empresa_id', 'orcamento_id', 'item_id', 'descricao', 'tipo_venda',
        'embalagem_quantidade', 'quantidade', 'valor_unitario', 'desconto_percentual',
        'ncm', 'cest', 'aliquota_icms', 'aliquota_ipi', 'aliquota_pis', 'aliquota_cofins',
        'possui_st', 'mva', 'observacoes_fiscais', 'valor_produtos', 'valor_icms',
        'valor_ipi', 'valor_pis', 'valor_cofins', 'base_calculo_st', 'valor_icms_st',
        'valor_impostos', 'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'embalagem_quantidade' => 'decimal:3',
            'valor_unitario' => 'decimal:2',
            'desconto_percentual' => 'decimal:2',
            'aliquota_icms' => 'decimal:4', 'aliquota_ipi' => 'decimal:4',
            'aliquota_pis' => 'decimal:4', 'aliquota_cofins' => 'decimal:4',
            'possui_st' => 'boolean', 'mva' => 'decimal:4',
            'valor_produtos' => 'decimal:2', 'valor_icms' => 'decimal:2',
            'valor_ipi' => 'decimal:2', 'valor_pis' => 'decimal:2',
            'valor_cofins' => 'decimal:2', 'base_calculo_st' => 'decimal:2',
            'valor_icms_st' => 'decimal:2', 'valor_impostos' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
