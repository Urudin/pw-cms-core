<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransferImaImages extends Command
{
    protected $signature = 'db:import-imakademia-images {--table=blocks} {--dry-run}';
    protected $description = 'A content mezőben lévő imakademia src-eket letölti public storage-ba, majd átírja az img src-eket.';

    public function handle(): int
    {
        $table = $this->option('table');
        $dryRun = (bool) $this->option('dry-run');

        $rows = DB::table($table)
            ->select('id', 'content')
            ->whereNotNull('content')
            ->where('content', 'LIKE', '%imakademia%')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Nincs feldolgozható rekord.');
            return self::SUCCESS;
        }

        $this->info("Talált rekordok: {$rows->count()}");

        foreach ($rows as $row) {
            $originalContent = $row->content;

            if (blank($originalContent)) {
                continue;
            }

            $updatedContent = $this->processHtmlContent($originalContent, (int) $row->id, $dryRun);

            if ($updatedContent !== $originalContent) {
                if (! $dryRun) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'content' => $updatedContent,
                        ]);
                }

                $this->info("✔ Block #{$row->id} frissítve.");
            } else {
                $this->line("– Block #{$row->id}: nem volt módosítás.");
            }
        }

        $this->info($dryRun
            ? '✅ Dry run kész, adatbázis módosítás nélkül.'
            : '✅ Kész, az img src-ek át lettek írva.'
        );

        return self::SUCCESS;
    }

    protected function processHtmlContent(string $html, int $blockId, bool $dryRun = false): string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $wrappedHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
    <div id="__root__">{$html}</div>
</body>
</html>
HTML;

        $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        $images = $xpath->query('//img[@src]');

        if (! $images || $images->length === 0) {
            libxml_clear_errors();
            return $html;
        }

        $changed = false;

        /** @var \DOMElement $img */
        foreach ($images as $img) {
            $src = trim((string) $img->getAttribute('src'));

            if ($src === '' || ! Str::contains($src, 'imakademia')) {
                continue;
            }

            try {
                $newUrl = $this->downloadAndStoreImage($src, $blockId, $dryRun);

                if ($newUrl) {
                    $img->setAttribute('src', $newUrl);
                    $changed = true;

                    $this->line("   ↳ {$src} -> {$newUrl}");
                }
            } catch (\Throwable $e) {
                $this->error("   ✘ Block #{$blockId} - hiba a letöltésnél: {$src}");
                $this->error("     {$e->getMessage()}");
            }
        }

        if (! $changed) {
            libxml_clear_errors();
            return $html;
        }

        $root = $dom->getElementById('__root__');

        if (! $root) {
            libxml_clear_errors();
            return $html;
        }

        $newHtml = '';

        foreach ($root->childNodes as $child) {
            $newHtml .= $dom->saveHTML($child);
        }

        libxml_clear_errors();

        return $newHtml;
    }

    protected function downloadAndStoreImage(string $src, int $blockId, bool $dryRun = false): ?string
    {
        $response = Http::timeout(30)
            ->retry(2, 500)
            ->withHeaders([
                'User-Agent' => 'Laravel Image Importer',
            ])
            ->get($src);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP hiba: {$response->status()}");
        }

        $content = $response->body();

        if ($content === '') {
            throw new \RuntimeException('Üres válasz érkezett.');
        }

        $path = parse_url($src, PHP_URL_PATH) ?: '';
        $originalName = pathinfo($path, PATHINFO_FILENAME) ?: 'image';
        $originalExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $extension = $this->resolveExtension($originalExtension, $response->header('Content-Type'));
        $safeName = Str::slug($originalName) ?: 'image';
        $hash = substr(md5($src), 0, 10);

        $relativePath = "images/{$blockId}/{$safeName}-{$hash}.{$extension}";

        if (! $dryRun) {
            Storage::disk('public')->put($relativePath, $content);
        }

        return Storage::url($relativePath);
    }

    protected function resolveExtension(?string $extension, ?string $contentType): string
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if ($extension && in_array($extension, $allowed, true)) {
            return $extension;
        }

        return match (true) {
            str_contains((string) $contentType, 'image/jpeg') => 'jpg',
            str_contains((string) $contentType, 'image/png') => 'png',
            str_contains((string) $contentType, 'image/gif') => 'gif',
            str_contains((string) $contentType, 'image/webp') => 'webp',
            str_contains((string) $contentType, 'image/svg+xml') => 'svg',
            default => 'jpg',
        };
    }
}
