<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faixas_comissao', function (Blueprint $table): void {
            $table->decimal('desconto_inicial_percentual', 5, 2)->nullable()->after('empresa_id');
            $table->decimal('desconto_final_percentual', 5, 2)->nullable()->after('desconto_inicial_percentual');
        });

        DB::table('faixas_comissao')
            ->whereNull('desconto_inicial_percentual')
            ->update([
                'desconto_inicial_percentual' => DB::raw('desconto_percentual'),
                'desconto_final_percentual' => DB::raw('desconto_percentual'),
            ]);
    }

    public function down(): void
    {
        Schema::table('faixas_comissao', function (Blueprint $table): void {
            $table->dropColumn(['desconto_inicial_percentual', 'desconto_final_percentual']);
        });
    }
};
