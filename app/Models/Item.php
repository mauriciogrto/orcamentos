<?php

namespace App\Models;

use App\Enums\ItemTipoVenda;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Item extends Model
{
    use BelongsToEmpresa, HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'itens';

    protected $fillable = ['empresa_id', 'item_tabela_id', 'descricao', 'numero_serie', 'tipo_venda', 'embalagem_quantidade', 'preco_atual'];

    protected function casts(): array
    {
        return [
            'tipo_venda' => ItemTipoVenda::class,
            'embalagem_quantidade' => 'decimal:3',
            'preco_atual' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tabela(): BelongsTo
    {
        return $this->belongsTo(ItemTabela::class, 'item_tabela_id');
    }

    public function precosHistorico(): HasMany
    {
        return $this->hasMany(ItemPrecoHistorico::class)
            ->latest('registrado_em')
            ->latest('id');
    }

    public function tributacao(): HasOne
    {
        return $this->hasOne(ItemTributacao::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
