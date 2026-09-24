<?php
namespace App\Libraries;

use InvalidArgumentException;
use RuntimeException;

class RorLookup
{
    private function request(string $suffix, array $query = []): array
    {
        $caBundle = trim((string) env('ror.caBundle', ''));
        if ($caBundle !== '' && (! is_file($caBundle) || ! is_readable($caBundle))) {
            throw new RuntimeException('O arquivo de certificados configurado em ror.caBundle não está disponível.');
        }
        $response = service('curlrequest')->get('https://api.ror.org/v2/organizations' . $suffix, [
            'query' => $query, 'timeout' => 15, 'connect_timeout' => 5,
            'http_errors' => false, 'allow_redirects' => false,
            'verify' => $caBundle !== '' ? $caBundle : true,
            'headers' => ['Accept' => 'application/json'],
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('ROR indisponível (HTTP ' . $response->getStatusCode() . ').');
        }
        $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            throw new RuntimeException('Resposta ROR inválida.');
        }
        return $data;
    }

    public function search(string $query, int $page = 1): array
    {
        // Treat user input as literal text, not Elasticsearch operators.
        $literal = '';
        foreach (mb_str_split($query) as $character) {
            $literal .= str_contains('+-=&|><!(){}[]^"~*?:\\/', $character) ? '\\' . $character : $character;
        }
        $data = $this->request('', ['query' => $literal, 'page' => max(1, min(500, $page))]);
        if (! isset($data['items']) || ! is_array($data['items'])) {
            throw new RuntimeException('Resposta ROR inválida.');
        }
        return ['total' => (int) ($data['number_of_results'] ?? 0),
            'items' => array_map([self::class, 'map'], $data['items'])];
    }

    public function find(string $id): array
    {
        if (! preg_match('/^0[0-9a-hj-km-np-tv-z]{6}[0-9]{2}$/', $id)) {
            throw new InvalidArgumentException('Identificador ROR inválido.');
        }
        return self::map($this->request('/' . $id));
    }

    public static function map(array $record): array
    {
        $name = '';
        $acronym = '';
        foreach ($record['names'] ?? [] as $entry) {
            if (in_array('ror_display', $entry['types'] ?? [], true)) {
                $name = $entry['value'];
            }
            if ($acronym === '' && in_array('acronym', $entry['types'] ?? [], true)) {
                $acronym = $entry['value'];
            }
        }
        $location = $record['locations'][0]['geonames_details'] ?? [];
        return [
            'ror_id' => $record['id'] ?? '', 'name' => $name ?: ($record['names'][0]['value'] ?? ''),
            'acronym' => $acronym, 'city' => $location['name'] ?? '',
            'state' => $location['country_subdivision_name'] ?? '',
            'country' => $location['country_name'] ?? '', 'country_code' => $location['country_code'] ?? '',
            'latitude' => $location['lat'] ?? null, 'longitude' => $location['lng'] ?? null,
            'established_year' => $record['established'] ?? null,
        ];
    }
}