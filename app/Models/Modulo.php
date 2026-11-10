<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modulo extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'slug'];

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class)
            ->withPivot('ativo');
    }
}
