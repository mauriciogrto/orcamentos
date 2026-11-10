<?php

namespace App\Services;

use App\Exceptions\BrasilApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BrasilApiCnpjService
{
    /**
     * @return array{razao_social: string|null, nome_fantasia: string|null, telefone: string|null, email: string|null, cep: string|null, logradouro: string|null, numero: string|null, complemento: string|null, bairro: string|null, cidade: string|null, uf: string|null}
     */
    public function buscar(string $cnpj): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(6)
                ->retry(2, 250, throw: false)
                ->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");
        } catch (ConnectionException) {
            throw new BrasilApiException('A BrasilAPI está indisponível no momento. Preencha os dados manualmente.');
        }

        if ($response->notFound()) {
            throw new BrasilApiException('CNPJ não encontrado na BrasilAPI. Confira o número ou preencha os dados manualmente.');
        }

        if ($response->failed()) {
            throw new BrasilApiException('Não foi possível consultar a BrasilAPI. Preencha os dados manualmente.');
        }

        return [
            'razao_social' => $this->nullableString($response->json('razao_social')),
            'nome_fantasia' => $this->nullableString($response->json('nome_fantasia')),
            'telefone' => $this->nullableString($response->json('ddd_telefone_1')),
            'email' => $this->nullableString($response->json('email')),
            'cep' => $this->onlyDigits($response->json('cep')),
            'logradouro' => $this->nullableString($response->json('logradouro')),
            'numero' => $this->nullableString($response->json('numero')),
            'complemento' => $this->nullableString($response->json('complemento')),
            'bairro' => $this->nullableString($response->json('bairro')),
            'cidade' => $this->nullableString($response->json('municipio')),
            'uf' => $this->nullableString($response->json('uf')),
        ];
    }

    private function onlyDigits(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
