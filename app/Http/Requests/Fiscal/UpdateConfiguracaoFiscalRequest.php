<?php

namespace App\Http\Requests\Fiscal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfiguracaoFiscalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_empresa']) ?? false;
    }

    public function rules(): array
    {
        return [
            'ambiente_nfe' => ['required', Rule::in(['homologacao', 'producao'])],
            'certificado_a1' => ['nullable', 'file', 'max:4096', 'mimes:pfx,p12'],
            'certificado_a1_senha' => ['nullable', 'string', 'max:255'],
            'certificado_a1_validade' => ['nullable', 'date'],
            'inscricao_estadual' => ['nullable', 'string', 'max:20'],
            'regime_tributario' => ['nullable', Rule::in(['simples_nacional', 'lucro_presumido', 'lucro_real'])],
            'serie_nfe' => ['required', 'string', 'max:10'],
            'proximo_numero_nfe' => ['required', 'integer', 'min:1'],
            'natureza_operacao_padrao' => ['nullable', 'string', 'max:255'],
            'cfop_padrao' => ['nullable', 'digits:4'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inscricao_estadual' => trim((string) $this->input('inscricao_estadual')) ?: null,
            'serie_nfe' => trim((string) ($this->input('serie_nfe') ?: '1')),
            'cfop_padrao' => filled($this->input('cfop_padrao')) ? preg_replace('/\D/', '', (string) $this->input('cfop_padrao')) : null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'ambiente_nfe' => 'ambiente da NF-e',
            'certificado_a1' => 'certificado A1',
            'certificado_a1_senha' => 'senha do certificado',
            'certificado_a1_validade' => 'validade do certificado',
            'inscricao_estadual' => 'inscrição estadual',
            'regime_tributario' => 'regime tributário',
            'serie_nfe' => 'série da NF-e',
            'proximo_numero_nfe' => 'próximo número da NF-e',
            'natureza_operacao_padrao' => 'natureza da operação padrão',
            'cfop_padrao' => 'CFOP padrão',
        ];
    }
}
