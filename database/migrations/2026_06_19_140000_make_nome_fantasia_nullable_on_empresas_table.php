<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('nome_fantasia')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('empresas')
            ->whereNull('nome_fantasia')
            ->update(['nome_fantasia' => DB::raw('razao_social')]);

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('nome_fantasia')->nullable(false)->change();
        });
    }
};
