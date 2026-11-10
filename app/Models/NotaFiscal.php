<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotaFiscal extends Model
{
    use BelongsToEmpresa, LogsActivity;

    protected $table = 'notas_fiscais';

    protected $fillable = [
        'empresa_id',
        'pedido_id',
        'orcamento_id',
        'ambiente',
        'status',
        'serie',
        'numero',
        'chave_acesso',
        'protocolo',
        'xml_path',
        'danfe_path',
        'pre_validacao',
        'mensagem_retorno',
        'pre_validada_em',
        'emitida_em',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'pre_validacao' => 'array',
            'pre_validada_em' => 'datetime',
            'emitida_em' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class)->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
