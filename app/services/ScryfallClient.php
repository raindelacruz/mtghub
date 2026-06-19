<?php

class ScryfallClient
{
    private string $baseUrl = 'https://api.scryfall.com';
    private ?string $lastError = null;

    public function search(string $query): array
    {
        $response = $this->get('/cards/search', [
            'q' => $query,
            'unique' => 'prints',
            'order' => 'name',
        ]);

        if ($response === null) {
            return [];
        }

        return $response['data'] ?? [];
    }

    public function find(string $scryfallId): ?array
    {
        return $this->get('/cards/' . rawurlencode($scryfallId));
    }

    public function bulkDownloadUri(string $type = 'default_cards'): ?string
    {
        $response = $this->get('/bulk-data/' . rawurlencode($type));

        if ($response === null) {
            return null;
        }

        return $response['download_uri'] ?? null;
    }

    public function downloadToFile(string $url, string $path): bool
    {
        $this->lastError = null;
        $headers = [
            'Accept: application/json',
            'User-Agent: MTGHubPH/1.0',
        ];

        if (function_exists('curl_init')) {
            return $this->downloadWithCurl($url, $path, $headers);
        }

        return $this->downloadWithStreams($url, $path, $headers);
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function toCardData(array $card): array
    {
        $colors = $card['colors'] ?? $card['color_identity'] ?? [];

        return [
            'card_name' => $this->fit($card['name'] ?? '', 150),
            'set_name' => $this->fit($card['set_name'] ?? '', 150),
            'collector_number' => $this->fit((string) ($card['collector_number'] ?? ''), 30),
            'rarity' => $this->normalizeRarity((string) ($card['rarity'] ?? 'rare')),
            'color' => $this->fit($this->colorNames($colors), 50),
            'type_line' => $this->fit($card['type_line'] ?? $this->faceTypeLine($card), 150),
            'image_url' => $this->imageUrl($card),
            'scryfall_id' => $this->fit($card['id'] ?? '', 80),
        ];
    }

    private function get(string $path, array $query = []): ?array
    {
        $this->lastError = null;
        $url = $this->baseUrl . $path;

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: MTGHubPH/1.0',
        ];

        if (function_exists('curl_init')) {
            $response = $this->getWithCurl($url, $headers);
        } else {
            $response = $this->getWithStreams($url, $headers);
        }

        if ($response === null) {
            return null;
        }

        [$status, $body] = $response;
        $decoded = json_decode($body, true);

        if ($status >= 400) {
            $this->lastError = $decoded['details'] ?? 'Scryfall returned an error.';
            return null;
        }

        if (!is_array($decoded)) {
            $this->lastError = 'Scryfall returned an unreadable response.';
            return null;
        }

        return $decoded;
    }

    private function getWithCurl(string $url, array $headers): ?array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 12,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            $this->lastError = curl_error($ch) ?: 'Unable to connect to Scryfall.';
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        return [$status, $body];
    }

    private function getWithStreams(string $url, array $headers): ?array
    {
        $context = stream_context_create([
            'http' => [
                'header' => implode("\r\n", $headers),
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            $this->lastError = 'Unable to connect to Scryfall.';
            return null;
        }

        $status = 200;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }

        return [$status, $body];
    }

    private function downloadWithCurl(string $url, string $path, array $headers): bool
    {
        $file = fopen($path, 'wb');
        if ($file === false) {
            $this->lastError = 'Unable to write the Scryfall bulk data file.';
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $file,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 0,
        ]);

        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($ok === false || $status >= 400) {
            $this->lastError = curl_error($ch) ?: 'Unable to download Scryfall bulk data.';
            curl_close($ch);
            fclose($file);
            return false;
        }

        curl_close($ch);
        fclose($file);

        return true;
    }

    private function downloadWithStreams(string $url, string $path, array $headers): bool
    {
        $context = stream_context_create([
            'http' => [
                'header' => implode("\r\n", $headers),
                'timeout' => 0,
                'ignore_errors' => true,
            ],
        ]);

        $source = @fopen($url, 'rb', false, $context);
        if ($source === false) {
            $this->lastError = 'Unable to connect to Scryfall.';
            return false;
        }

        $target = fopen($path, 'wb');
        if ($target === false) {
            fclose($source);
            $this->lastError = 'Unable to write the Scryfall bulk data file.';
            return false;
        }

        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        return true;
    }

    private function imageUrl(array $card): ?string
    {
        if (!empty($card['image_uris']['normal'])) {
            return $card['image_uris']['normal'];
        }

        foreach ($card['card_faces'] ?? [] as $face) {
            if (!empty($face['image_uris']['normal'])) {
                return $face['image_uris']['normal'];
            }
        }

        return null;
    }

    private function faceTypeLine(array $card): string
    {
        $typeLines = [];

        foreach ($card['card_faces'] ?? [] as $face) {
            if (!empty($face['type_line'])) {
                $typeLines[] = $face['type_line'];
            }
        }

        return implode(' // ', array_unique($typeLines));
    }

    private function colorNames(array $colors): string
    {
        if ($colors === []) {
            return 'Colorless';
        }

        $names = [
            'W' => 'White',
            'U' => 'Blue',
            'B' => 'Black',
            'R' => 'Red',
            'G' => 'Green',
        ];

        return implode(', ', array_map(static fn (string $color): string => $names[$color] ?? $color, $colors));
    }

    private function normalizeRarity(string $rarity): string
    {
        return in_array($rarity, ['common', 'uncommon', 'rare', 'mythic'], true) ? $rarity : 'rare';
    }

    private function fit(string $value, int $length): string
    {
        return mb_substr($value, 0, $length);
    }
}
