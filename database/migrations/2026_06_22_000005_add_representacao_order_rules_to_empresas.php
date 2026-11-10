<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->decimal('pedido_minimo', 15, 2)->default(1000);
            $table->decimal('limite_frete_fob', 15, 2)->default(2000);
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->dropColumn(['pedido_minimo', 'limite_frete_fob']);
        });
    }
};
