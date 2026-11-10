<?php

namespace App\Services\Fiscal;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class BrasilApiNcmService
{
    /**
     * @return array{codigo: string, descricao: ?string, status: string, data_inicio: ?string, data_fim: ?string, mensagem: ?string}
     */
    public function consultar(string $ncm): array
    {
        $codigo = preg_replace('/\D/', '', $ncm) ?? '';

        if (strlen($codigo) !== 8) {
            return [
                'codigo' => $codigo,
                'descricao' => null,
                'status' => 'invalido',
                'data_inicio' => null,
                'data_fim' => null,
                'mensagem' => 'NCM precisa conter 8 dígitos.',
            ];
        }

        try {
            $dados = $this->consultarPorCodigo($codigo) ?? $this->pesquisarNaLista($codigo);

            if (! $dados) {
                return [
                    'codigo' => $codigo,
                    'descricao' => null,
                    'status' => 'nao_encontrado',
                    'data_inicio' => null,
                    'data_fim' => null,
                    'mensagem' => 'NCM não encontrado na consulta pública.',
                ];
            }

            $dataFim = $dados['data_fim'] ?? null;
            $ativo = blank($dataFim) || Carbon::parse($dataFim)->endOfDay()->isFuture();

            return [
                'codigo' => (string) ($dados['codigo'] ?? $codigo),
                'descricao' => $dados['descricao'] ?? null,
                'status' => $ativo ? 'ativo' : 'inativo',
                'data_inicio' => $dados['data_inicio'] ?? null,
                'data_fim' => $dataFim,
                'mensagem' => null,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'codigo' => $codigo,
                'descricao' => null,
                'status' => 'consulta_indisponivel',
                'data_inicio' => null,
                'data_fim' => null,
                'mensagem' => 'Não foi possível consultar o NCM agora. Confira manualmente ou tente novamente.',
            ];
        }
    }

    private function consultarPorCodigo(string $codigo): ?array
    {
        $response = Http::timeout(8)->get("https://brasilapi.com.br/api/ncm/v1/{$codigo}");

        if (! $response->ok()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) && isset($json['codigo']) ? $json : null;
    }

    private function pesquisarNaLista(string $codigo): ?array
    {
        $response = Http::timeout(12)->get('https://brasilapi.com.br/api/ncm/v1');

        if (! $response->ok()) {
            return null;
        }

        $lista = $response->json();

        if (! is_array($lista)) {
            return null;
        }

        foreach ($lista as $registro) {
            if (is_array($registro) && (string) ($registro['codigo'] ?? '') === $codigo) {
                return $registro;
            }
        }

        return null;
    }
}
