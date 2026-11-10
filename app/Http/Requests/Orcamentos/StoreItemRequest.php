<?php

namespace App\Http\Requests\Orcamentos;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Item::class);
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:255'],
            'item_tabela_id' => [
                'required',
                'integer',
                Rule::exists('item_tabelas', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
            'numero_serie' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('itens', 'numero_serie')
                    ->where('empresa_id', $this->user()->empresa_id)
                    ->where('item_tabela_id', $this->input('item_tabela_id')),
            ],
            'tipo_venda' => ['required', Rule::in(['unitario', 'metro'])],
            'embalagem_quantidade' => ['required', 'decimal:0,3', 'min:1'],
            'preco_atual' => ['required', 'decimal:0,2', 'min:0'],
            'tributacao' => ['required', 'array'],
            'tributacao.ncm' => ['nullable', 'digits:8'],
            'tributacao.cest' => ['nullable', 'digits:7'],
            'tributacao.cfop' => ['nullable', 'digits:4'],
            'tributacao.cst_csosn' => ['nullable', 'string', 'max:4'],
            'tributacao.unidade_comercial' => ['required', 'string', 'max:10'],
            'tributacao.aliquota_icms' => ['required', 'decimal:0,4', 'min:0', 'max:100'],
            'tributacao.aliquota_ipi' => ['required', 'decimal:0,4', 'min:0', 'max:100'],
            'tributacao.aliquota_pis' => ['required', 'decimal:0,4', 'min:0', 'max:100'],
            'tributacao.aliquota_cofins' => ['required', 'decimal:0,4', 'min:0', 'max:100'],
            'tributacao.possui_st' => ['required', 'boolean'],
            'tributacao.mva' => ['nullable', 'required_if:tributacao.possui_st,1', 'decimal:0,4', 'min:0', 'max:999.9999'],
            'tributacao.observacoes_fiscais' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $numeroSerie = trim((string) $this->input('numero_serie'));
        $tipoVenda = $this->input('tipo_venda') ?: 'unitario';
        $embalagemQuantidade = $this->input('embalagem_quantidade') ?: 1;
        $tributacao = $this->input('tributacao', []);
        $possuiSt = filter_var($tributacao['possui_st'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $tributacao['ncm'] = filled($tributacao['ncm'] ?? null) ? preg_replace('/\D/', '', $tributacao['ncm']) : null;
        $tributacao['cest'] = filled($tributacao['cest'] ?? null) ? preg_replace('/\D/', '', $tributacao['cest']) : null;
        $tributacao['cfop'] = filled($tributacao['cfop'] ?? null) ? preg_replace('/\D/', '', $tributacao['cfop']) : null;
        $tributacao['cst_csosn'] = filled($tributacao['cst_csosn'] ?? null) ? mb_strtoupper(trim((string) $tributacao['cst_csosn'])) : null;
        $tributacao['unidade_comercial'] = mb_strtoupper(trim((string) ($tributacao['unidade_comercial'] ?? 'UN'))) ?: 'UN';
        $tributacao['possui_st'] = $possuiSt ? 1 : 0;
        if (! $possuiSt) {
            $tributacao['mva'] = null;
            $tributacao['observacoes_fiscais'] = null;
        }
        $this->merge([
            'numero_serie' => $numeroSerie === '' ? null : mb_strtoupper($numeroSerie),
            'tipo_venda' => $tipoVenda,
            'embalagem_quantidade' => $embalagemQuantidade,
            'tributacao' => $tributacao,
        ]);
    }

    public function messages(): array
    {
        return [
            'item_tabela_id.required' => 'Selecione a tabela deste item.',
            'numero_serie.unique' => 'Este código já está cadastrado em outro item desta tabela.',
        ];
    }
}
