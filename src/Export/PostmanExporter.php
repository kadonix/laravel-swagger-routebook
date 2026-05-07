<?php

namespace Kadonix\Routebook\Export;

final class PostmanExporter
{
    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    public function collection(array $document): array
    {
        return [
            'info' => [
                'name' => $document['info']['title'] ?? 'Routebook API',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $this->items($document),
        ];
    }

    /**
     * @param array<string, mixed> $document
     * @return array<int, array<string, mixed>>
     */
    private function items(array $document): array
    {
        $items = [];

        foreach ($document['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $items[] = [
                    'name' => $operation['summary'] ?? strtoupper($method) . ' ' . $path,
                    'request' => [
                        'method' => strtoupper($method),
                        'header' => $this->headers($operation),
                        'url' => [
                            'raw' => '{{base_url}}' . $path,
                            'host' => ['{{base_url}}'],
                            'path' => array_values(array_filter(explode('/', trim($path, '/')))),
                        ],
                    ],
                ];
            }
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<int, array<string, string>>
     */
    private function headers(array $operation): array
    {
        if (($operation['security'] ?? []) === []) {
            return [];
        }

        return [
            [
                'key' => 'Authorization',
                'value' => 'Bearer {{token}}',
                'type' => 'text',
            ],
        ];
    }
}
