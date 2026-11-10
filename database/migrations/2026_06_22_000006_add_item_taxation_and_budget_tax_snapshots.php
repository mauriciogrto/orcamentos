<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_tributacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('item_id')->unique()->constrained('itens')->cascadeOnDelete();
            $table->string('ncm', 8)->nullable();
            $table->string('cest', 7)->nullable();
            $table->decimal('aliquota_icms', 7, 4)->default(0);
            $table->decimal('aliquota_ipi', 7, 4)->default(0);
            $table->decimal('aliquota_pis', 7, 4)->default(0);
            $table->decimal('aliquota_cofins', 7, 4)->default(0);
            $table->boolean('possui_st')->default(false);
            $table->decimal('mva', 7, 4)->nullable();
            $table->text('observacoes_fiscais')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'ncm']);
        });

        Schema::table('orcamentos', function (Blueprint $table): void {
            $table->decimal('total_produtos', 15, 2)->default(0)->after('desconto_efetivo_percentual');
            $table->decimal('total_impostos', 15, 2)->default(0)->after('total_produtos');
        });

        DB::table('orcamentos')->update(['total_produtos' => DB::raw('valor_total')]);

        Schema::table('orcamento_itens', function (Blueprint $table): void {
            $table->string('ncm', 8)->nullable()->after('descricao');
            $table->string('cest', 7)->nullable()->after('ncm');
            $table->decimal('aliquota_icms', 7, 4)->default(0)->after('desconto_percentual');
            $table->decimal('aliquota_ipi', 7, 4)->default(0)->after('aliquota_icms');
            $table->decimal('aliquota_pis', 7, 4)->default(0)->after('aliquota_ipi');
            $table->decimal('aliquota_cofins', 7, 4)->default(0)->after('aliquota_pis');
            $table->boolean('possui_st')->default(false)->after('aliquota_cofins');
            $table->decimal('mva', 7, 4)->nullable()->after('possui_st');
            $table->text('observacoes_fiscais')->nullable()->after('mva');
            $table->decimal('valor_produtos', 15, 2)->default(0)->after('observacoes_fiscais');
            $table->decimal('valor_icms', 15, 2)->default(0)->after('valor_produtos');
            $table->decimal('valor_ipi', 15, 2)->default(0)->after('valor_icms');
            $table->decimal('valor_pis', 15, 2)->default(0)->after('valor_ipi');
            $table->decimal('valor_cofins', 15, 2)->default(0)->after('valor_pis');
            $table->decimal('base_calculo_st', 15, 2)->default(0)->after('valor_cofins');
            $table->decimal('valor_icms_st', 15, 2)->default(0)->after('base_calculo_st');
            $table->decimal('valor_impostos', 15, 2)->default(0)->after('valor_icms_st');
        });

        DB::table('orcamento_itens')->update(['valor_produtos' => DB::raw('valor_total')]);
    }

    public function down(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table): void {
            $table->dropColumn([
                'ncm', 'cest', 'aliquota_icms', 'aliquota_ipi', 'aliquota_pis', 'aliquota_cofins',
                'possui_st', 'mva', 'observacoes_fiscais', 'valor_produtos', 'valor_icms', 'valor_ipi',
                'valor_pis', 'valor_cofins', 'base_calculo_st', 'valor_icms_st', 'valor_impostos',
            ]);
        });
        Schema::table('orcamentos', fn (Blueprint $table) => $table->dropColumn(['total_produtos', 'total_impostos']));
        Schema::dropIfExists('item_tributacoes');
    }
};
