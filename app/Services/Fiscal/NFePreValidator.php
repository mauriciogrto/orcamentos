<?php

namespace App\Services\Fiscal;

use App\Models\OrcamentoItem;
use App\Models\Pedido;

class NFePreValidator
{
    /**
     * @return array<string, mixed>
     */
    public function validar(Pedido $pedido): array
    {
        $pedido->loadMissing([
            'empresa.configuracaoFiscal',
            'cliente',
            'orcamento.itens.item.tributacao',
            'vendedor',
            'notaFiscal',
        ]);

        $erros = [];
        $avisos = [];

        $empresa = $pedido->empresa;
        $cliente = $pedido->cliente;
        $config = $empresa->configuracaoFiscal;

        $empresaChecks = [
            $this->campo('Razão social', $empresa->razao_social, true),
            $this->campo('CNPJ', $empresa->cnpj, $this->cnpjValido($empresa->cnpj)),
            $this->campo('CEP', $empresa->cep, filled($empresa->cep)),
            $this->campo('Logradouro', $empresa->logradouro, filled($empresa->logradouro)),
            $this->campo('Número', $empresa->numero, filled($empresa->numero)),
            $this->campo('Bairro', $empresa->bairro, filled($empresa->bairro)),
            $this->campo('Cidade/UF', trim(($empresa->cidade ?? '').'/'.($empresa->uf ?? ''), '/'), filled($empresa->cidade) && filled($empresa->uf)),
            $this->campo('Inscrição estadual', $config?->inscricao_estadual, filled($config?->inscricao_estadual)),
            $this->campo('Regime tributário', $this->regimeLabel($config?->regime_tributario), filled($config?->regime_tributario)),
            $this->campo('Certificado A1', $config?->certificado_a1_path ? 'Arquivo configurado' : null, filled($config?->certificado_a1_path)),
            $this->campo('Senha do certificado', filled($config?->certificado_a1_senha) ? 'Informada' : null, filled($config?->certificado_a1_senha)),
            $this->campo('Ambiente', $config?->ambiente_nfe === 'producao' ? 'Produção' : 'Homologação', filled($config?->ambiente_nfe)),
            $this->campo('Série / próximo número', ($config?->serie_nfe ?? '-').' / '.($config?->proximo_numero_nfe ?? '-'), filled($config?->serie_nfe) && filled($config?->proximo_numero_nfe)),
            $this->campo('Natureza da operação', $config?->natureza_operacao_padrao, filled($config?->natureza_operacao_padrao)),
        ];

        if ($config?->certificado_a1_validade && $config->certificado_a1_validade->isPast()) {
            $avisos[] = 'Certificado A1 vencido. Atualize antes de emitir em produção.';
        }

        $clienteChecks = [
            $this->campo('Nome/Razão social', $cliente?->nome_razao_social, filled($cliente?->nome_razao_social)),
            $this->campo('CNPJ/CPF', $cliente?->cnpj, $this->documentoClienteValido($cliente?->cnpj)),
            $this->campo('Indicador de IE', $this->indicadorIeLabel($cliente?->indicador_ie), filled($cliente?->indicador_ie)),
            $this->campo('Inscrição estadual', $cliente?->inscricao_estadual ?: 'Não informada', $cliente?->indicador_ie !== 'contribuinte' || filled($cliente?->inscricao_estadual)),
            $this->campo('CEP', $cliente?->cep, filled($cliente?->cep)),
            $this->campo('Logradouro', $cliente?->logradouro, filled($cliente?->logradouro)),
            $this->campo('Número', $cliente?->numero, filled($cliente?->numero)),
            $this->campo('Bairro', $cliente?->bairro, filled($cliente?->bairro)),
            $this->campo('Cidade/UF', trim(($cliente?->cidade ?? '').'/'.($cliente?->uf ?? ''), '/'), filled($cliente?->cidade) && filled($cliente?->uf)),
        ];

        $itens = $pedido->orcamento->itens->map(function (OrcamentoItem $linha) use ($config): array {
            $tributacaoAtual = $linha->item?->tributacao;
            $ncm = $linha->ncm ?: $tributacaoAtual?->ncm;
            $cfop = $tributacaoAtual?->cfop ?: $config?->cfop_padrao;

            return [
                'descricao' => $linha->descricao,
                'codigo' => $linha->item?->numero_serie,
                'quantidade' => $linha->quantidade,
                'valor_produtos' => $linha->valor_produtos,
                'valor_impostos' => $linha->valor_impostos,
                'valor_total' => $linha->valor_total,
                'ncm' => $ncm,
                'ncm_ok' => preg_match('/^\d{8}$/', (string) $ncm) === 1,
                'ncm_status' => $tributacaoAtual?->ncm_status,
                'ncm_descricao_oficial' => $tributacaoAtual?->ncm_descricao_oficial,
                'ncm_validado_em' => $tributacaoAtual?->ncm_validado_em?->format('d/m/Y H:i'),
                'cfop' => $cfop,
                'cst_csosn' => $tributacaoAtual?->cst_csosn,
                'unidade_comercial' => $tributacaoAtual?->unidade_comercial ?: 'UN',
                'cest' => $linha->cest ?: $tributacaoAtual?->cest,
                'icms' => $linha->aliquota_icms,
                'ipi' => $linha->aliquota_ipi,
                'pis' => $linha->aliquota_pis,
                'cofins' => $linha->aliquota_cofins,
                'possui_st' => (bool) $linha->possui_st,
                'mva' => $linha->mva,
                'pendencias' => array_values(array_filter([
                    preg_match('/^\d{8}$/', (string) $ncm) !== 1 ? 'NCM ausente ou inválido' : null,
                    $tributacaoAtual?->ncm_status === 'inativo' ? 'NCM inativo na consulta' : null,
                    ! $tributacaoAtual?->ncm_validado_em ? 'NCM ainda não confirmado pelo usuário' : null,
                    ! filled($cfop) ? 'CFOP ausente' : null,
                    ! filled($tributacaoAtual?->cst_csosn) ? 'CST/CSOSN ausente' : null,
                    (bool) $linha->possui_st && ! filled($linha->cest ?: $tributacaoAtual?->cest) ? 'CEST obrigatório quando há ST' : null,
                    (bool) $linha->possui_st && ! filled($linha->mva) ? 'MVA obrigatório quando há ST' : null,
                ])),
            ];
        })->values()->all();

        foreach (['empresa' => $empresaChecks, 'cliente' => $clienteChecks] as $grupo => $checks) {
            foreach ($checks as $check) {
                if (! $check['ok']) {
                    $erros[] = ucfirst($grupo).': '.$check['label'].' pendente.';
                }
            }
        }

        foreach ($itens as $item) {
            foreach ($item['pendencias'] as $pendencia) {
                $erros[] = 'Item '.$item['descricao'].': '.$pendencia.'.';
            }
        }

        return [
            'ok' => $erros === [],
            'erros' => array_values(array_unique($erros)),
            'avisos' => array_values(array_unique($avisos)),
            'empresa' => $empresaChecks,
            'cliente' => $clienteChecks,
            'itens' => $itens,
            'pedido' => [
                'numero' => $pedido->numero,
                'orcamento' => $pedido->orcamento?->numero,
                'finalidade' => $pedido->orcamento?->finalidade?->label() ?? 'Revenda',
                'forma_pagamento' => $pedido->orcamento?->forma_pagamento?->label() ?? '-',
                'frete' => $pedido->orcamento?->frete_tipo?->label() ?? '-',
                'total_produtos' => $pedido->total_produtos,
                'total_impostos' => $pedido->total_impostos,
                'valor_total' => $pedido->valor_total,
            ],
        ];
    }

    private function campo(string $label, mixed $valor, bool $ok): array
    {
        return [
            'label' => $label,
            'value' => filled($valor) ? (string) $valor : 'Não informado',
            'ok' => $ok,
        ];
    }

    private function cnpjValido(?string $cnpj): bool
    {
        return strlen((string) preg_replace('/\D/', '', (string) $cnpj)) === 14;
    }

    private function documentoClienteValido(?string $documento): bool
    {
        $digitos = preg_replace('/\D/', '', (string) $documento);

        return in_array(strlen((string) $digitos), [11, 14], true);
    }

    private function regimeLabel(?string $regime): ?string
    {
        return match ($regime) {
            'simples_nacional' => 'Simples Nacional',
            'lucro_presumido' => 'Lucro Presumido',
            'lucro_real' => 'Lucro Real',
            default => $regime,
        };
    }

    private function indicadorIeLabel(?string $indicador): ?string
    {
        return match ($indicador) {
            'contribuinte' => 'Contribuinte de ICMS',
            'isento' => 'Isento',
            'nao_contribuinte' => 'Não contribuinte',
            default => $indicador,
        };
    }
}
