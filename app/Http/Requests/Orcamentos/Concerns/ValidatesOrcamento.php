<?php

namespace App\Http\Requests\Orcamentos\Concerns;

use App\Enums\BoletoPrazo;
use App\Enums\FormaPagamento;
use App\Enums\FreteTipo;
use App\Enums\OrcamentoFinalidade;
use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Item;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesOrcamento
{
    public function rules(): array
    {
        $podeEscolherResponsavel = $this->user()->hasAnyRole([
            RoleName::AdminEmpresa->value,
            RoleName::Supervisor->value,
        ]);

        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'forma_pagamento' => ['required', Rule::enum(FormaPagamento::class)],
            'boleto_prazos' => [
                'nullable',
                'required_if:forma_pagamento,'.FormaPagamento::Boleto->value,
                'prohibited_unless:forma_pagamento,'.FormaPagamento::Boleto->value,
                Rule::enum(BoletoPrazo::class),
            ],
            'finalidade' => ['required', Rule::enum(OrcamentoFinalidade::class)],
            'validade_dias' => ['required', 'integer', 'min:1', 'max:365'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
            'frete_tipo' => ['required', Rule::enum(FreteTipo::class)],
            'itens' => ['required', 'array', 'min:1', 'max:100'],
            'itens.*.item_id' => ['required', 'integer', 'distinct', 'exists:itens,id'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1', 'max:999999'],
            'itens.*.desconto_percentual' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
            'acao_apos_salvar' => ['nullable', Rule::in(['sistema', 'pdf'])],
            'responsavel_id' => [
                Rule::requiredIf($podeEscolherResponsavel),
                Rule::prohibitedIf(! $podeEscolherResponsavel),
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value && ! User::query()
                        ->role([
                            RoleName::AdminEmpresa->value,
                            RoleName::Supervisor->value,
                            RoleName::Vendedor->value,
                        ])
                        ->whereKey($value)
                        ->exists()) {
                        $fail('Selecione um responsável válido desta empresa.');
                    }
                },
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('cliente_id')) {
                return;
            }

            $cliente = Cliente::query()->find($this->integer('cliente_id'));
            $podeUsarClientesDaEmpresa = $this->user()?->hasAnyRole([
                RoleName::AdminEmpresa->value,
                RoleName::Supervisor->value,
            ]) === true;
            $orcamentoAtual = $this->route('orcamento');
            $podeManterClienteDoOrcamento = $orcamentoAtual
                && (int) $orcamentoAtual->user_id === (int) $this->user()->id
                && (int) $orcamentoAtual->cliente_id === (int) $cliente?->id;

            if (! $cliente || (! $podeUsarClientesDaEmpresa && ! $podeManterClienteDoOrcamento && Gate::denies('view', $cliente))) {
                $validator->errors()->add('cliente_id', 'O cliente selecionado não está disponível para este usuário.');
            }

            $linhas = collect($this->input('itens', []));
            $ids = $linhas->pluck('item_id')->filter()->unique();
            $itens = Item::query()
                ->with('tabela')
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            foreach ($linhas as $index => $linha) {
                $itemId = $linha['item_id'] ?? null;

                if (! $itemId) {
                    continue;
                }

                $item = $itens->get((int) $itemId);
                $numeroLinha = ((int) $index) + 1;

                if (! $item) {
                    $validator->errors()->add("itens.{$index}.item_id", "Item {$numeroLinha}: este item não está disponível para esta empresa.");
                    continue;
                }

                if (! $item->tabela?->ativo) {
                    $codigo = $item->numero_serie ? "código {$item->numero_serie}" : "ID {$item->id}";
                    $tabela = $item->tabela?->nome ?: 'sem tabela';

                    $validator->errors()->add(
                        "itens.{$index}.item_id",
                        "Item {$numeroLinha} - {$item->descricao} ({$codigo}) está na tabela \"{$tabela}\", que está desativada para orçamentos."
                    );
                }
            }
        }];
    }
}
