<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmpresaConfiguracaoFiscal extends Model
{
    use BelongsToEmpresa, LogsActivity;

    protected $table = 'empresa_configuracoes_fiscais';

    protected $fillable = [
        'empresa_id',
        'ambiente_nfe',
        'certificado_a1_path',
        'certificado_a1_senha',
        'certificado_a1_validade',
        'inscricao_estadual',
        'regime_tributario',
        'serie_nfe',
        'proximo_numero_nfe',
        'natureza_operacao_padrao',
        'cfop_padrao',
    ];

    protected $hidden = [
        'certificado_a1_senha',
    ];

    protected function casts(): array
    {
        return [
            'certificado_a1_senha' => 'encrypted',
            'certificado_a1_validade' => 'date',
            'proximo_numero_nfe' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ambiente_nfe',
                'certificado_a1_path',
                'certificado_a1_validade',
                'inscricao_estadual',
                'regime_tributario',
                'serie_nfe',
                'proximo_numero_nfe',
                'natureza_operacao_padrao',
                'cfop_padrao',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
