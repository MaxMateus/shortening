<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use App\Services\ShortUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShortUrlController extends Controller
{
    public function __construct(
        private ShortUrlService $shortUrlService
    ) {
        // Laravel injeta o service automaticamente
    }

    /**
     * POST /short
     * Cria uma nova URL curta.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'original_url' => ['required', 'url'],
            'expires_in'   => ['nullable', 'integer', 'min:1'], // segundos
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        [$shortUrl, $created] = $this->shortUrlService->createShortUrl(
            $data['original_url'],
            $data['expires_in'] ?? null
        );

        return response()->json([
            'message' => $created
                ? 'URL encurtada com sucesso.'
                : 'URL já encurtada e ainda válida.',
            'data'    => $this->formatShortUrlResponse($shortUrl),
        ], $created ? 201 : 200);
    }

    /**
     * GET /{code}
     * Redireciona para a URL original.
     */
    public function redirect(Request $request, string $code)
    {
        $shortUrl = $this->shortUrlService->getByCode($code);

        if (!$shortUrl) {
            return response()->json([
                'message' => 'URL não encontrada.',
            ], 404);
        }

        if ($shortUrl->isExpired()) {
            return response()->json([
                'message' => 'URL expirada.',
            ], 410); // HTTP 410 Gone
        }

        $this->shortUrlService->incrementVisits($shortUrl);

        return redirect()->away($shortUrl->original_url);
    }

    /**
     * GET /stats/{code}
     * Retorna estatísticas da URL.
     */
    public function stats(string $code)
    {
        $shortUrl = $this->shortUrlService->getByCode($code);

        if (!$shortUrl) {
            return response()->json([
                'message' => 'URL não encontrada.',
            ], 404);
        }

        $data = $this->formatShortUrlResponse($shortUrl);
        $data['visits'] = $shortUrl->visits;

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Monta o payload padrão da ShortUrl.
     */
    private function formatShortUrlResponse(ShortUrl $shortUrl): array
    {
        return [
            'code'         => $shortUrl->code,
            'short_url'    => route('short.redirect', ['code' => $shortUrl->code]),
            'original_url' => $shortUrl->original_url,
            'expires_at'   => optional($shortUrl->expires_at)->toDateTimeString(),
            'expired'      => $shortUrl->isExpired(),
        ];
    }
}
