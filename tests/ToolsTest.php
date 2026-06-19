<?php

use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase {

	// --- startsWith ---

	public function testStartsWithMatch(): void {
		$this->assertTrue(startsWith('hello world', 'hello'));
	}

	public function testStartsWithNoMatch(): void {
		$this->assertFalse(startsWith('hello world', 'world'));
	}

	public function testStartsWithEmptyNeedle(): void {
		$this->assertTrue(startsWith('hello', ''));
	}

	public function testStartsWithNeedleLongerThanHaystack(): void {
		$this->assertFalse(startsWith('hi', 'hello'));
	}

	// --- niceSize (binary) ---

	public function testNiceSizeZero(): void {
		$this->assertSame('0 B', niceSize(0));
	}

	public function testNiceSizeBytes(): void {
		$this->assertSame('500 B', niceSize(500));
	}

	public function testNiceSizeKiB(): void {
		$this->assertSame('1.5 KiB', niceSize(1536));
	}

	public function testNiceSizeMiB(): void {
		$this->assertSame('2 MiB', niceSize(2 * 1024 * 1024));
	}

	public function testNiceSizeGiB(): void {
		$this->assertSame('3 GiB', niceSize(3 * 1024 * 1024 * 1024));
	}

	public function testNiceSizeDecimalKB(): void {
		$this->assertSame('1.5 KB', niceSize(1500, false));
	}

	public function testNiceSizeNullEmpty(): void {
		$this->assertSame('', niceSize(null));
	}

	// --- shorter ---

	public function testShorterShortString(): void {
		$this->assertSame('hello', shorter('hello', 40));
	}

	public function testShorterTruncatesWithDots(): void {
		$result = shorter('abcdefghijklmnopqrstuvwxyz', 10);
		$this->assertSame('abcdefghij…', $result);
	}

	public function testShorterTruncatesNoDots(): void {
		$result = shorter('abcdefghijklmnopqrstuvwxyz', 10, false);
		$this->assertSame('abcdefghij', $result);
	}

	public function testShorterExactLength(): void {
		$this->assertSame('hello', shorter('hello', 5));
	}

	// --- prettyJson ---

	public function testPrettyJsonValidJson(): void {
		$result = prettyJson('{"a":1}');
		$this->assertStringContainsString('"a"', $result);
		$this->assertStringContainsString("\n", $result);
	}

	public function testPrettyJsonInvalidReturnsOriginal(): void {
		$this->assertSame('not json', prettyJson('not json'));
	}

	// --- randomString ---

	public function testRandomStringLength(): void {
		$this->assertSame(30, strlen(randomString()));
		$this->assertSame(10, strlen(randomString(10)));
	}

	public function testRandomStringCharacters(): void {
		$s = randomString(100);
		$this->assertMatchesRegularExpression('/^[0-9a-z]+$/', $s);
	}

	// --- ipAddressToBits ---

	public function testIpv4ToBits(): void {
		$bits = ipAddressToBits('192.168.1.1');
		$this->assertIsString($bits);
		$this->assertSame(32, strlen($bits));
		$this->assertMatchesRegularExpression('/^[01]+$/', $bits);
	}

	public function testIpv6ToBits(): void {
		$bits = ipAddressToBits('::1');
		$this->assertIsString($bits);
		$this->assertSame(128, strlen($bits));
	}

	public function testInvalidIpReturnsFalse(): void {
		$this->assertFalse(ipAddressToBits('not-an-ip'));
	}

	// --- isIpInRange ---

	public function testIpInRange(): void {
		$this->assertTrue(isIpInRange('192.168.1.5', '192.168.1.0/24'));
	}

	public function testIpOutOfRange(): void {
		$this->assertFalse(isIpInRange('192.168.2.5', '192.168.1.0/24'));
	}

	public function testIpv6InRange(): void {
		$this->assertTrue(isIpInRange('2001:db8::1', '2001:db8::/32'));
	}

	public function testIpv6OutOfRange(): void {
		$this->assertFalse(isIpInRange('2001:db9::1', '2001:db8::/32'));
	}

	public function testIpExactMatch(): void {
		$this->assertTrue(isIpInRange('10.0.0.1', '10.0.0.1/32'));
	}

	// --- isTimeInRange ---

	public function testTimeInRange(): void {
		// 12:30 is between 12:00 and 13:00
		$time = mktime(12, 30, 0);
		$this->assertTrue(isTimeInRange('12:00-13:00', $time));
	}

	public function testTimeOutOfRange(): void {
		$time = mktime(14, 0, 0);
		$this->assertFalse(isTimeInRange('12:00-13:00', $time));
	}

	public function testTimeRangeInvalidThrows(): void {
		$this->expectException(Exception::class);
		isTimeInRange('not-a-range', time());
	}

	public function testTimeRangeWithDayMatch(): void {
		// use a known timestamp: Monday 2026-06-15 12:00
		$time = mktime(12, 0, 0, 6, 15, 2026); // 2026-06-15 is a Monday
		$this->assertTrue(isTimeInRange('MON 11:00-13:00', $time));
	}

	public function testTimeRangeWithDayMismatch(): void {
		$time = mktime(12, 0, 0, 6, 15, 2026); // Monday
		$this->assertFalse(isTimeInRange('TUE 11:00-13:00', $time));
	}

	// --- localTimeToUtc / utcTimeToLocal ---

	public function testTimezoneRoundtrip(): void {
		$original = '2026-01-15 10:00:00';
		$utc = localTimeToUtc($original);
		$back = utcTimeToLocal($utc);
		$this->assertSame($original, $back);
	}

}
