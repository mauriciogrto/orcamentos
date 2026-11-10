<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FaixaComissao extends Model
{
    use BelongsToEmpresa, LogsActivity;

    protected $table = 'faixas_comissao';

    protected $fillable = [
        'empresa_id',
        'desconto_inicial_percentual',
        'desconto_final_percentual',
        'desconto_percentual',
        'comissao_percentual',
    ];

    protected function casts(): array
    {
        return [
            'desconto_inicial_percentual' => 'decimal:2',
            'desconto_final_percentual' => 'decimal:2',
            'desconto_percentual' => 'decimal:2',
            'comissao_percentual' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FaixaComissao $faixa): void {
            if ($faixa->desconto_inicial_percentual === null && $faixa->desconto_percentual !== null) {
                $faixa->desconto_inicial_percentual = $faixa->desconto_percentual;
            }

            if ($faixa->desconto_final_percentual === null && $faixa->desconto_percentual !== null) {
                $faixa->desconto_final_percentual = $faixa->desconto_percentual;
            }

            if ($faixa->desconto_percentual === null && $faixa->desconto_inicial_percentual !== null) {
                $faixa->desconto_percentual = $faixa->desconto_inicial_percentual;
            }
        });
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
