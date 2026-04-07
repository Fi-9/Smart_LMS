<?php

namespace Tests\Unit;

use App\Services\AiInfrastructureService;
use App\Services\AppSettingsService;
use App\Services\OllamaService;
use ReflectionClass;
use Tests\TestCase;

class OllamaServiceJsonDecodeTest extends TestCase
{
    public function test_decode_model_json_accepts_markdown_wrapped_json(): void
    {
        $service = $this->makeService();
        $decoded = $this->invokeDecode($service, <<<TEXT
```json
{"images":[],"best":{"isbn":null,"title":"Keep Going","author":"Austin Kleon","category":"Non-fiksi","description":null,"front_image_index":0}}
```
TEXT);

        $this->assertIsArray($decoded);
        $this->assertSame('Keep Going', $decoded['best']['title']);
    }

    public function test_decode_model_json_accepts_prefixed_text_with_json_object(): void
    {
        $service = $this->makeService();
        $decoded = $this->invokeDecode(
            $service,
            'Berikut hasil analisis: {"images":[],"best":{"isbn":null,"title":"Layang Putus","author":"Mommy Asf","category":"Novel","description":null,"front_image_index":0}}'
        );

        $this->assertIsArray($decoded);
        $this->assertSame('Layang Putus', $decoded['best']['title']);
    }

    public function test_decode_model_json_accepts_double_encoded_json(): void
    {
        $service = $this->makeService();
        $raw = "\"{\\\"images\\\":[],\\\"best\\\":{\\\"isbn\\\":null,\\\"title\\\":\\\"Keep Going\\\",\\\"author\\\":\\\"Austin Kleon\\\",\\\"category\\\":\\\"Non-fiksi\\\",\\\"description\\\":null,\\\"front_image_index\\\":0}}\"";
        $decoded = $this->invokeDecode($service, $raw);

        $this->assertIsArray($decoded);
        $this->assertSame('Keep Going', $decoded['best']['title']);
    }

    public function test_decode_model_json_throws_on_unrecoverable_payload(): void
    {
        $service = $this->makeService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON returned by Ollama model.');

        $this->invokeDecode($service, 'bukan json sama sekali');
    }

    private function makeService(): OllamaService
    {
        return new OllamaService(
            $this->createMock(AiInfrastructureService::class),
            $this->createMock(AppSettingsService::class),
        );
    }

    private function invokeDecode(OllamaService $service, string $raw): array
    {
        $ref = new ReflectionClass($service);
        $method = $ref->getMethod('decodeModelJson');
        $method->setAccessible(true);

        /** @var array $result */
        $result = $method->invoke($service, $raw);

        return $result;
    }
}

