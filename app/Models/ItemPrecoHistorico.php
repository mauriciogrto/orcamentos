<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPrecoHistorico extends Model
{
    use BelongsToEmpresa;

    protected $table = 'item_precos_historico';

    public $timestamps = false;

    protected $fillable = ['empresa_id', 'item_id', 'preco', 'registrado_em'];

    protected function casts(): array
    {
        return ['preco' => 'decimal:2', 'registrado_em' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
