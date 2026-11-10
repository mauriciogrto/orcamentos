<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'financeiro' => 'Financeiro',
            'fiscal_nfe' => 'Fiscal / NF-e',
        ] as $slug => $nome) {
            DB::table('modulos')->updateOrInsert(
                ['slug' => $slug],
                ['nome' => $nome, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        Schema::create('empresa_configuracoes_fiscais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->string('ambiente_nfe', 20)->default('homologacao');
            $table->string('certificado_a1_path')->nullable();
            $table->text('certificado_a1_senha')->nullable();
            $table->date('certificado_a1_validade')->nullable();
            $table->string('inscricao_estadual', 20)->nullable();
            $table->string('regime_tributario', 40)->nullable();
            $table->string('serie_nfe', 10)->default('1');
            $table->unsignedInteger('proximo_numero_nfe')->default(1);
            $table->string('natureza_operacao_padrao')->nullable();
            $table->string('cfop_padrao', 4)->nullable();
            $table->timestamps();
        });

        Schema::table('clientes', function (Blueprint $table): void {
            $table->string('inscricao_estadual', 20)->nullable()->after('cnpj');
            $table->string('indicador_ie', 30)->default('nao_contribuinte')->after('inscricao_estadual');
        });

        Schema::table('item_tributacoes', function (Blueprint $table): void {
            $table->string('cfop', 4)->nullable()->after('cest');
            $table->string('cst_csosn', 4)->nullable()->after('cfop');
            $table->string('unidade_comercial', 10)->default('UN')->after('cst_csosn');
            $table->string('ncm_status', 20)->nullable()->after('ncm');
            $table->text('ncm_descricao_oficial')->nullable()->after('ncm_status');
            $table->timestamp('ncm_validado_em')->nullable()->after('ncm_descricao_oficial');
        });

        Schema::create('notas_fiscais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('pedido_id')->unique()->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
            $table->string('ambiente', 20)->default('homologacao');
            $table->string('status', 30)->default('pendente');
            $table->string('serie', 10)->nullable();
            $table->unsignedInteger('numero')->nullable();
            $table->string('chave_acesso', 60)->nullable();
            $table->string('protocolo', 60)->nullable();
            $table->string('xml_path')->nullable();
            $table->string('danfe_path')->nullable();
            $table->json('pre_validacao')->nullable();
            $table->text('mensagem_retorno')->nullable();
            $table->timestamp('pre_validada_em')->nullable();
            $table->timestamp('emitida_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');

        Schema::table('item_tributacoes', function (Blueprint $table): void {
            $table->dropColumn([
                'cfop',
                'cst_csosn',
                'unidade_comercial',
                'ncm_status',
                'ncm_descricao_oficial',
                'ncm_validado_em',
            ]);
        });

        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropColumn(['inscricao_estadual', 'indicador_ie']);
        });

        Schema::dropIfExists('empresa_configuracoes_fiscais');
    }
};
