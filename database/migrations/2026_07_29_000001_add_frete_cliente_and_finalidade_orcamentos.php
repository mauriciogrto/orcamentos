<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('clientes', 'frete_personalizado')) {
                $table->boolean('frete_personalizado')->default(false)->after('uf');
            }

            if (! Schema::hasColumn('clientes', 'limite_frete_fob')) {
                $table->decimal('limite_frete_fob', 15, 2)->nullable()->after('frete_personalizado');
            }
        });

        Schema::table('orcamentos', function (Blueprint $table): void {
            if (! Schema::hasColumn('orcamentos', 'finalidade')) {
                $table->string('finalidade', 30)->default('revenda')->after('boleto_prazos');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table): void {
            if (Schema::hasColumn('orcamentos', 'finalidade')) {
                $table->dropColumn('finalidade');
            }
        });

        Schema::table('clientes', function (Blueprint $table): void {
            $columns = collect(['frete_personalizado', 'limite_frete_fob'])
                ->filter(fn (string $column): bool => Schema::hasColumn('clientes', $column))
                ->all();

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
