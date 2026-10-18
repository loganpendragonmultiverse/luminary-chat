<?php

namespace Tests\Unit;

use App\Support\MessageFormatter;
use PHPUnit\Framework\TestCase;

class MessageFormatterTest extends TestCase
{
    public function test_it_formats_paragraphs_quotes_and_code_blocks_safely(): void
    {
        $html = MessageFormatter::toHtml("Line one\n\n> quoted\n\n```php\nalert('<x>');\n```");

        $this->assertStringContainsString('<p>Line one</p>', $html);
        $this->assertStringContainsString('<blockquote class="message-quote">quoted</blockquote>', $html);
        $this->assertStringContainsString('&lt;x&gt;', $html);
        $this->assertStringContainsString('<pre class="message-code"><code>', $html);
    }
}
