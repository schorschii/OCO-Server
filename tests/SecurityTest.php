<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for security-critical logic across the application.
 */
class SecurityTest extends TestCase {

	// --- XSS protection in Html utility class ---

	public function testWrapInSpanEmpty(): void {
		$this->assertSame('', Html::wrapInSpanIfNotEmpty(''));
	}

	public function testWrapInSpanNull(): void {
		$this->assertSame('', Html::wrapInSpanIfNotEmpty(null));
	}

	public function testWrapInSpanNormalText(): void {
		$this->assertSame('<span>hello</span>', Html::wrapInSpanIfNotEmpty('hello'));
	}

	public function testWrapInSpanEscapesHtml(): void {
		$result = Html::wrapInSpanIfNotEmpty('<script>alert(1)</script>');
		$this->assertStringNotContainsString('<script>', $result);
		$this->assertStringContainsString('&lt;script&gt;', $result);
	}

	public function testWrapInSpanEscapesQuotes(): void {
		$result = Html::wrapInSpanIfNotEmpty('"quoted" & \'value\'');
		$this->assertStringContainsString('&amp;', $result);
		$this->assertStringNotContainsString('"quoted"', $result);
	}

}
