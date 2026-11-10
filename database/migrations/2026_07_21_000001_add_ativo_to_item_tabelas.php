<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_tabelas', function (Blueprint $table): void {
            $table->boolean('ativo')
                ->default(true)
                ->after('itens_importados')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('item_tabelas', function (Blueprint $table): void {
            $table->dropColumn('ativo');
        });
    }
};
