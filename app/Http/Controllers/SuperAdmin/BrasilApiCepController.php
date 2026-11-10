<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exceptions\BrasilApiException;
use App\Http\Controllers\Controller;
use App\Services\BrasilApiCepService;
use Illuminate\Http\JsonResponse;

class BrasilApiCepController extends Controller
{
    public function __invoke(string $cep, BrasilApiCepService $brasilApi): JsonResponse
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return response()->json(['message' => 'Informe um CEP com 8 dígitos.'], 422);
        }

        try {
            return response()->json(['data' => $brasilApi->buscar($cep)]);
        } catch (BrasilApiException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
