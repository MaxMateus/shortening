<?php

namespace App\Services;

use App\Models\ShortUrl;
use Illuminate\Support\Str;

class ShortUrlService
{
    /**
     * Cria (ou reaproveita) uma URL curta.
     *
     * @return array [ShortUrl $shortUrl, bool $created]
     */
    public function createShortUrl(string $originalUrl, ?int $expiresInSeconds = null): array
    {
        // Se já existir URL igual ainda válida, apenas retorna
        $existing = ShortUrl::where('original_url', $originalUrl)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        $code = $this->generateUniqueCode();

        $expiresAt = null;
        if ($expiresInSeconds) {
            $expiresAt = now()->addSeconds($expiresInSeconds);
        }

        $shortUrl = ShortUrl::create([
            'original_url' => $originalUrl,
            'code'         => $code,
            'expires_at'   => $expiresAt,
        ]);

        return [$shortUrl, true];
    }

    /**
     * Busca uma ShortUrl pelo código.
     */
    public function getByCode(string $code): ?ShortUrl
    {
        return ShortUrl::where('code', $code)->first();
    }

    /**
     * Incrementa o contador de visitas.
     */
    public function incrementVisits(ShortUrl $shortUrl): void
    {
        $shortUrl->increment('visits');
    }

    /**
     * Gera um código curto único.
     */
    protected function generateUniqueCode(int $length = 6): string
    {
        do {
            $code = Str::random($length);
        } while (ShortUrl::where('code', $code)->exists());

        return $code;
    }
}
