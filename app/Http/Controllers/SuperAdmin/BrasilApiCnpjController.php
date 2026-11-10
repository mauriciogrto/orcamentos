<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exceptions\BrasilApiException;
use App\Http\Controllers\Controller;
use App\Services\BrasilApiCnpjService;
use Illuminate\Http\JsonResponse;

class BrasilApiCnpjController extends Controller
{
    public function __invoke(string $cnpj, BrasilApiCnpjService $brasilApi): JsonResponse
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return response()->json(['message' => 'Informe um CNPJ com 14 dígitos.'], 422);
        }

        try {
            return response()->json(['data' => $brasilApi->buscar($cnpj)]);
        } catch (BrasilApiException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
