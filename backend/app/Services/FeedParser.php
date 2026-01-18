<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use SimpleXMLElement;

class FeedParser
{
    /**
     * @return array{items: array<int, array{title: string, url: string, published_at: Carbon, summary: ?string, content: ?string}>, missing_published_at_count: int}
     */
    public function parse(string $xml): array
    {
        $document = $this->loadXml($xml);

        $items = [];
        $missingPublishedAtCount = 0;

        if (isset($document->channel->item)) {
            foreach ($document->channel->item as $item) {
                $normalized = $this->normalizeRssItem($item);
                if ($normalized === null) {
                    continue;
                }

                if ($normalized['missing_published_at']) {
                    $missingPublishedAtCount++;
                }

                $items[] = $normalized['data'];
            }
        } else {
            $normalizedEntries = $this->normalizeAtomEntries($document);
            $missingPublishedAtCount += $normalizedEntries['missing_published_at_count'];
            $items = $normalizedEntries['items'];
        }

        return [
            'items' => $items,
            'missing_published_at_count' => $missingPublishedAtCount,
        ];
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA);

        if ($document === false) {
            libxml_clear_errors();
            throw new InvalidArgumentException('Feed XML 无法解析');
        }

        libxml_clear_errors();

        return $document;
    }

    /**
     * @return array{data: array{title: string, url: string, published_at: Carbon, summary: ?string, content: ?string}, missing_published_at: bool}|null
     */
    private function normalizeRssItem(SimpleXMLElement $item): ?array
    {
        $title = trim((string) $item->title);
        $url = trim((string) $item->link);

        // 标题或链接缺失会导致无法落库，直接跳过该条目。
        if ($title === '' || $url === '') {
            return null;
        }

        $publishedAt = $this->parseDate((string) $item->pubDate);
        if ($publishedAt === null) {
            $namespaces = $item->getNamespaces(true);
            $dcNamespace = $namespaces['dc'] ?? null;
            if ($dcNamespace !== null) {
                $publishedAt = $this->parseDate((string) $item->children($dcNamespace)->date);
            }
        }
        $missingPublishedAt = false;
        if ($publishedAt === null) {
            $publishedAt = Carbon::now();
            $missingPublishedAt = true;
        }

        return [
            'data' => [
                'title' => $title,
                'url' => $url,
                'published_at' => $publishedAt,
                'summary' => $this->stringOrNull($item->description),
                'content' => $this->stringOrNull($item->children('content', true)->encoded ?? null),
            ],
            'missing_published_at' => $missingPublishedAt,
        ];
    }

    /**
     * @return array{items: array<int, array{title: string, url: string, published_at: Carbon, summary: ?string, content: ?string}>, missing_published_at_count: int}
     */
    private function normalizeAtomEntries(SimpleXMLElement $document): array
    {
        $namespaces = $document->getNamespaces(true);
        $defaultNamespace = $namespaces[''] ?? null;
        $feed = $defaultNamespace ? $document->children($defaultNamespace) : $document;

        if (! isset($feed->entry)) {
            throw new InvalidArgumentException('不支持的 Feed 格式');
        }

        $items = [];
        $missingPublishedAtCount = 0;

        foreach ($feed->entry as $entry) {
            $entryNode = $defaultNamespace ? $entry->children($defaultNamespace) : $entry;
            $title = trim((string) $entryNode->title);
            $url = $this->extractAtomLink($entry, $defaultNamespace);

            // 标题或链接缺失会导致无法落库，直接跳过该条目。
            if ($title === '' || $url === '') {
                continue;
            }

            $publishedAt = $this->parseDate((string) $entryNode->published);
            if ($publishedAt === null) {
                $publishedAt = $this->parseDate((string) $entryNode->updated);
            }

            if ($publishedAt === null) {
                $publishedAt = Carbon::now();
                $missingPublishedAtCount++;
            }

            $items[] = [
                'title' => $title,
                'url' => $url,
                'published_at' => $publishedAt,
                'summary' => $this->stringOrNull($entryNode->summary ?? null),
                'content' => $this->stringOrNull($entryNode->content ?? null),
            ];
        }

        return [
            'items' => $items,
            'missing_published_at_count' => $missingPublishedAtCount,
        ];
    }

    private function extractAtomLink(SimpleXMLElement $entry, ?string $defaultNamespace): string
    {
        $entryNode = $defaultNamespace ? $entry->children($defaultNamespace) : $entry;

        foreach ($entryNode->link as $link) {
            $attributes = $link->attributes();
            $href = trim((string) ($attributes['href'] ?? ''));
            $rel = trim((string) ($attributes['rel'] ?? ''));

            if ($href === '') {
                continue;
            }

            if ($rel === '' || $rel === 'alternate') {
                return $href;
            }
        }

        return trim((string) $entryNode->link);
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
