<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('clientes', 'nome_fantasia')) {
                $table->string('nome_fantasia')->nullable()->after('nome_razao_social');
            }
        });

        DB::table('clientes')
            ->whereNull('nome_fantasia')
            ->update(['nome_fantasia' => DB::raw('nome_razao_social')]);
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            if (Schema::hasColumn('clientes', 'nome_fantasia')) {
                $table->dropColumn('nome_fantasia');
            }
        });
    }
};
