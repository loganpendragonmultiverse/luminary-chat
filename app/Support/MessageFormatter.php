<?php

namespace App\Support;

class MessageFormatter
{
    public static function toHtml(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '<p></p>';
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $parts = preg_split('/(```[\s\S]*?```)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $html = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, '```') && str_ends_with($part, '```')) {
                $html[] = self::renderCodeBlock($part);
                continue;
            }

            $html[] = self::renderTextBlocks($part);
        }

        return implode('', array_filter($html));
    }

    protected static function renderCodeBlock(string $block): string
    {
        $code = preg_replace('/^```[^\n]*\n?|\n?```$/', '', $block) ?? '';

        return '<pre class="message-code"><code>'.e($code).'</code></pre>';
    }

    protected static function renderTextBlocks(string $text): string
    {
        $paragraphs = preg_split("/\n{2,}/", trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if (! $paragraphs) {
            return '';
        }

        $html = [];

        foreach ($paragraphs as $paragraph) {
            $trimmed = trim($paragraph);

            if ($trimmed === '') {
                continue;
            }

            if (str_starts_with($trimmed, '>')) {
                $quoteLines = array_map(
                    fn (string $line) => ltrim(preg_replace('/^>\s?/', '', $line) ?? '', ' '),
                    preg_split("/\n/", $trimmed) ?: []
                );

                $html[] = '<blockquote class="message-quote">'.nl2br(e(implode("\n", $quoteLines))).'</blockquote>';
                continue;
            }

            $html[] = '<p>'.nl2br(e($trimmed)).'</p>';
        }

        return implode('', $html);
    }
}
