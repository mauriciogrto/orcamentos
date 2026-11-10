<?php

namespace App\Services;

use App\Exceptions\BrasilApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BrasilApiCepService
{
    /**
     * @return array{cep: string|null, logradouro: string|null, bairro: string|null, cidade: string|null, uf: string|null}
     */
    public function buscar(string $cep): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(6)
                ->retry(2, 250, throw: false)
                ->get("https://brasilapi.com.br/api/cep/v2/{$cep}");
        } catch (ConnectionException) {
            throw new BrasilApiException('A BrasilAPI de CEP está indisponível no momento. Preencha o endereço manualmente.');
        }

        if ($response->notFound()) {
            throw new BrasilApiException('CEP não encontrado. Confira o número ou preencha o endereço manualmente.');
        }

        if ($response->failed()) {
            throw new BrasilApiException('Não foi possível consultar o CEP. Preencha o endereço manualmente.');
        }

        return [
            'cep' => $this->nullableString($response->json('cep')),
            'logradouro' => $this->nullableString($response->json('street')),
            'bairro' => $this->nullableString($response->json('neighborhood')),
            'cidade' => $this->nullableString($response->json('city')),
            'uf' => $this->nullableString($response->json('state')),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
