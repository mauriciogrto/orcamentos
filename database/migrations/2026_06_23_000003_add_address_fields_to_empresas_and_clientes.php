<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            if (! Schema::hasColumn('empresas', 'cep')) {
                $table->string('cep', 8)->nullable()->after('email');
            }
            if (! Schema::hasColumn('empresas', 'logradouro')) {
                $table->string('logradouro')->nullable()->after('cep');
            }
            if (! Schema::hasColumn('empresas', 'numero')) {
                $table->string('numero', 30)->nullable()->after('logradouro');
            }
            if (! Schema::hasColumn('empresas', 'complemento')) {
                $table->string('complemento')->nullable()->after('numero');
            }
            if (! Schema::hasColumn('empresas', 'bairro')) {
                $table->string('bairro')->nullable()->after('complemento');
            }
            if (! Schema::hasColumn('empresas', 'cidade')) {
                $table->string('cidade')->nullable()->after('bairro');
            }
            if (! Schema::hasColumn('empresas', 'uf')) {
                $table->string('uf', 2)->nullable()->after('cidade');
            }
        });

        Schema::table('clientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('clientes', 'cep')) {
                $table->string('cep', 8)->nullable()->after('email');
            }
            if (! Schema::hasColumn('clientes', 'logradouro')) {
                $table->string('logradouro')->nullable()->after('cep');
            }
            if (! Schema::hasColumn('clientes', 'numero')) {
                $table->string('numero', 30)->nullable()->after('logradouro');
            }
            if (! Schema::hasColumn('clientes', 'complemento')) {
                $table->string('complemento')->nullable()->after('numero');
            }
            if (! Schema::hasColumn('clientes', 'bairro')) {
                $table->string('bairro')->nullable()->after('complemento');
            }
            if (! Schema::hasColumn('clientes', 'cidade')) {
                $table->string('cidade')->nullable()->after('bairro');
            }
            if (! Schema::hasColumn('clientes', 'uf')) {
                $table->string('uf', 2)->nullable()->after('cidade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $columns = collect(['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf'])
                ->filter(fn (string $column): bool => Schema::hasColumn('clientes', $column))
                ->all();

            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('empresas', function (Blueprint $table): void {
            $columns = collect(['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf'])
                ->filter(fn (string $column): bool => Schema::hasColumn('empresas', $column))
                ->all();

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
