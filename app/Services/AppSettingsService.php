<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class AppSettingsService
{
    private const CACHE_KEY = 'app_settings:all';

    /**
     * @return array<string, mixed>
     */
    public function aiSettings(): array
    {
        return [
            'google_books_api_key' => $this->get('google_books.api_key', config('services.google_books.api_key')),
            'ollama_base_url' => $this->get('ai.ollama.base_url', config('services.ollama.base_url')),
            'ollama_vision_model' => $this->get('ai.ollama.vision_model', config('services.ollama.vision_model')),
            'ollama_text_model' => $this->get('ai.ollama.text_model', config('services.ollama.text_model')),
            'ollama_web_model' => $this->get('ai.ollama.web_model', config('services.ollama.web_model')),
            'ollama_timeout' => $this->getInt('ai.ollama.timeout', (int) config('services.ollama.timeout', 240)),
            'ollama_connect_timeout' => $this->getInt('ai.ollama.connect_timeout', (int) config('services.ollama.connect_timeout', 10)),
            'websearch_enabled' => $this->getBool('ai.websearch.enabled', (bool) config('services.websearch.enabled', false)),
            'tavily_api_key' => $this->get('ai.websearch.tavily_api_key', config('services.tavily.api_key')),
            'tavily_base_url' => $this->get('ai.websearch.tavily_base_url', config('services.tavily.base_url')),
            'tavily_timeout' => $this->getInt('ai.websearch.tavily_timeout', (int) config('services.tavily.timeout', 15)),
            'websearch_max_results' => $this->getInt('ai.websearch.max_results', (int) config('services.websearch.max_results', 3)),
            'websearch_allowed_domains' => $this->get('ai.websearch.allowed_domains', implode(',', (array) config('services.websearch.allowed_domains', []))),
            'scan_default_mode' => $this->get('ai.scan.default_mode', config('services.ai_runtime.default_scan_mode', 'simple')),
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        $value = $settings[$key];
        if ($value === null || $value === '') {
            return $default;
        }

        if ($this->isSecretKey($key)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return $default;
            }
        }

        return $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $storedValue = $value;

            if (is_bool($value)) {
                $storedValue = $value ? '1' : '0';
            }

            if ($storedValue === null || $storedValue === '') {
                AppSetting::query()->where('key', $key)->delete();
                continue;
            }

            if ($this->isSecretKey($key)) {
                $storedValue = Crypt::encryptString((string) $storedValue);
            }

            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $storedValue]
            );
        }

        $this->flushCache();
    }

    /**
     * @return array<string, string|null>
     */
    public function maskedAiSettings(): array
    {
        $settings = $this->aiSettings();

        return [
            'google_books_api_key' => $this->maskSecret($settings['google_books_api_key']),
            'tavily_api_key' => $this->maskSecret($settings['tavily_api_key']),
        ];
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string|null>
     */
    private function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            try {
                return AppSetting::query()
                    ->pluck('value', 'key')
                    ->all();
            } catch (QueryException) {
                return [];
            }
        });
    }

    private function isSecretKey(string $key): bool
    {
        return in_array($key, [
            'google_books.api_key',
            'ai.websearch.tavily_api_key',
        ], true);
    }

    private function maskSecret(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);
        if (strlen($trimmed) <= 8) {
            return str_repeat('*', strlen($trimmed));
        }

        return substr($trimmed, 0, 4) . str_repeat('*', max(4, strlen($trimmed) - 8)) . substr($trimmed, -4);
    }
}
