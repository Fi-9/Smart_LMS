<?php

namespace App\Services;

class EnvFileService
{
    /**
     * @param array<string, string|int|bool|null> $values
     * @param array<int, string> $removeKeys
     */
    public function sync(array $values, array $removeKeys = []): void
    {
        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        if (! is_string($content)) {
            return;
        }

        foreach ($removeKeys as $key) {
            $content = $this->removeKey($content, $key);
        }

        foreach ($values as $key => $value) {
            $content = $this->upsertKey($content, $key, $value);
        }

        file_put_contents($envPath, $content);
    }

    private function removeKey(string $content, string $key): string
    {
        return (string) preg_replace('/^' . preg_quote($key, '/') . '=.*\R?/m', '', $content);
    }

    private function upsertKey(string $content, string $key, string|int|bool|null $value): string
    {
        $encoded = $this->encodeValue($value);
        $line = $key . '=' . $encoded;
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $content) === 1) {
            return (string) preg_replace($pattern, $line, $content, 1);
        }

        $separator = str_ends_with($content, "\n") ? '' : PHP_EOL;

        return $content . $separator . $line . PHP_EOL;
    }

    private function encodeValue(string|int|bool|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $string = trim((string) $value);
        if ($string === '') {
            return '';
        }

        if (preg_match('/\s|#|"|=/', $string)) {
            return '"' . addcslashes($string, '"\\') . '"';
        }

        return $string;
    }
}
