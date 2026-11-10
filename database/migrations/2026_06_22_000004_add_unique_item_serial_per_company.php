<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('itens')
            ->whereNotNull('numero_serie')
            ->update(['numero_serie' => DB::raw('UPPER(TRIM(numero_serie))')]);

        Schema::table('itens', function (Blueprint $table): void {
            $table->unique(['empresa_id', 'numero_serie']);
        });
    }

    public function down(): void
    {
        Schema::table('itens', function (Blueprint $table): void {
            $table->dropUnique(['empresa_id', 'numero_serie']);
        });
    }
};
