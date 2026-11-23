<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Signale immédiatement une URL nouvelle ou modifiée à Bing, Yandex, Seznam,
 * Naver (protocole IndexNow). Bing alimente aussi ChatGPT Search et Copilot.
 */
class PingIndexNow implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 2;

    /** @param string[] $urls */
    public function __construct(public array $urls) {}

    public static function urls(string ...$urls): void
    {
        if (config('troofe.indexnow_key') && app()->isProduction()) {
            $all = collect($urls)->flatMap(fn ($u) => [$u, $u.(str_contains($u, '?') ? '&' : '?').'lang=en'])->unique()->values()->all();
            static::dispatch($all);
        }
    }

    public function handle(): void
    {
        $response = Http::timeout(10)->acceptJson()->post('https://api.indexnow.org/indexnow', [
            'host' => parse_url(config('app.url'), PHP_URL_HOST),
            'key' => config('troofe.indexnow_key'),
            'keyLocation' => route('indexnow.key'),
            'urlList' => $this->urls,
        ]);
        if ($response->failed() && $response->status() !== 202) {
            Log::warning('IndexNow', ['status' => $response->status(), 'urls' => $this->urls]);
        }
    }
}
