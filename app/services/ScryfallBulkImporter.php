<?php

class ScryfallBulkImporter
{
    private Card $cards;
    private ScryfallClient $scryfall;

    public function __construct(Card $cards, ScryfallClient $scryfall)
    {
        $this->cards = $cards;
        $this->scryfall = $scryfall;
    }

    public function syncDefaultCards(?int $limit = null): array
    {
        $downloadUri = $this->scryfall->bulkDownloadUri('default_cards');

        if ($downloadUri === null) {
            throw new RuntimeException($this->scryfall->lastError() ?? 'Unable to find the Scryfall bulk data download.');
        }

        $directory = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'scryfall';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . 'default_cards.json';
        if (!$this->scryfall->downloadToFile($downloadUri, $path)) {
            throw new RuntimeException($this->scryfall->lastError() ?? 'Unable to download Scryfall bulk data.');
        }

        $stats = [
            'processed' => 0,
            'saved' => 0,
            'skipped' => 0,
            'failed' => 0,
            'file' => $path,
        ];

        foreach ($this->readCardObjects($path) as $card) {
            if ($limit !== null && $stats['processed'] >= $limit) {
                break;
            }

            $stats['processed']++;
            $data = $this->scryfall->toCardData($card);

            if (!$this->isImportable($data)) {
                $stats['skipped']++;
                continue;
            }

            try {
                $this->cards->upsertFromScryfall($data);
                $stats['saved']++;
            } catch (Throwable $exception) {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    private function isImportable(array $data): bool
    {
        return $data['card_name'] !== ''
            && $data['set_name'] !== ''
            && $data['type_line'] !== ''
            && $data['scryfall_id'] !== '';
    }

    private function readCardObjects(string $path): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read the downloaded Scryfall bulk data file.');
        }

        $buffer = '';
        $depth = 0;
        $capturing = false;
        $inString = false;
        $escaped = false;

        while (!feof($handle)) {
            $chunk = fread($handle, 1048576);
            if ($chunk === false) {
                fclose($handle);
                throw new RuntimeException('Unable to read the downloaded Scryfall bulk data file.');
            }

            $length = strlen($chunk);
            for ($i = 0; $i < $length; $i++) {
                $char = $chunk[$i];

                if (!$capturing) {
                    if ($char === '{') {
                        $capturing = true;
                        $depth = 1;
                        $buffer = '{';
                    }
                    continue;
                }

                $buffer .= $char;

                if ($inString) {
                    if ($escaped) {
                        $escaped = false;
                    } elseif ($char === '\\') {
                        $escaped = true;
                    } elseif ($char === '"') {
                        $inString = false;
                    }
                    continue;
                }

                if ($char === '"') {
                    $inString = true;
                } elseif ($char === '{') {
                    $depth++;
                } elseif ($char === '}') {
                    $depth--;

                    if ($depth === 0) {
                        $decoded = json_decode($buffer, true);
                        if (is_array($decoded)) {
                            yield $decoded;
                        }

                        $buffer = '';
                        $capturing = false;
                    }
                }
            }
        }

        fclose($handle);
    }
}
