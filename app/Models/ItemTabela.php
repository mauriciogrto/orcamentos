<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ItemTabela extends Model
{
    use BelongsToEmpresa, LogsActivity, SoftDeletes;

    protected $table = 'item_tabelas';

    protected $fillable = [
        'empresa_id',
        'nome',
        'arquivo_original',
        'itens_importados',
        'ativo',
        'ultima_atualizacao_em',
        'atualizado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'ultima_atualizacao_em' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(Item::class, 'item_tabela_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
