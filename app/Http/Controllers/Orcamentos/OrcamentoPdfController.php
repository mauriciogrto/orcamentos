<?php

namespace App\Http\Controllers\Orcamentos;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class OrcamentoPdfController extends Controller
{
    public function __invoke(Orcamento $orcamento): Response
    {
        Gate::authorize('view', $orcamento);
        $orcamento->load(['empresa', 'cliente', 'vendedor', 'itens.item']);
        $logo = $this->logoDataUri($orcamento->empresa->logo_path);
        $tributos = [
            'icms' => (float) $orcamento->itens->sum('valor_icms'),
            'ipi' => (float) $orcamento->itens->sum('valor_ipi'),
            'pis' => (float) $orcamento->itens->sum('valor_pis'),
            'cofins' => (float) $orcamento->itens->sum('valor_cofins'),
            'icms_st' => (float) $orcamento->itens->sum('valor_icms_st'),
        ];

        return Pdf::loadView('orcamentos.orcamentos.pdf', compact('orcamento', 'logo', 'tributos'))
            ->setPaper('a4')
            ->download("orcamento-{$orcamento->numero}.pdf");
    }

    private function logoDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $content = $this->logoContents($path);
        if (! $content) {
            return null;
        }

        [$mime, $content] = $this->normalizeLogoForPdf($content);

        if (! $mime) {
            return null;
        }

        return "data:{$mime};base64,".base64_encode($content);
    }

    private function logoContents(string $path): ?string
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }

        $candidates = [
            $path,
            storage_path('app/public/'.$path),
            public_path('storage/'.$path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                $content = file_get_contents($candidate);

                return $content === false ? null : $content;
            }
        }

        return null;
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function normalizeLogoForPdf(string $content): array
    {
        $mime = $this->detectImageMime($content);

        if (in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            return [$mime, $content];
        }

        $converted = $this->convertImageToPng($content);

        if ($converted) {
            return ['image/png', $converted];
        }

        return [null, $content];
    }

    private function detectImageMime(string $content): ?string
    {
        $mime = null;

        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: null;
        }

        if (! $mime) {
            $imageInfo = @getimagesizefromstring($content);
            $mime = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;
        }

        return $mime ? strtolower($mime) : null;
    }

    private function convertImageToPng(string $content): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return null;
        }

        $image = @imagecreatefromstring($content);

        if (! $image) {
            return null;
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        $converted = ob_get_clean();

        return $converted === false ? null : $converted;
    }
}
