<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ItemTributacao extends Model
{
    use BelongsToEmpresa, LogsActivity;

    protected $table = 'item_tributacoes';

    protected $fillable = [
        'empresa_id', 'item_id', 'ncm', 'ncm_status', 'ncm_descricao_oficial',
        'ncm_validado_em', 'cest', 'cfop', 'cst_csosn', 'unidade_comercial', 'aliquota_icms', 'aliquota_ipi',
        'aliquota_pis', 'aliquota_cofins', 'possui_st', 'mva', 'observacoes_fiscais',
    ];

    protected function casts(): array
    {
        return [
            'aliquota_icms' => 'decimal:4', 'aliquota_ipi' => 'decimal:4',
            'aliquota_pis' => 'decimal:4', 'aliquota_cofins' => 'decimal:4',
            'possui_st' => 'boolean', 'mva' => 'decimal:4',
            'ncm_validado_em' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
