<?php

use PHPUnit\Framework\TestCase;

class MobileDeviceTest extends TestCase {

	// --- placeholders ---

	public function testReplacePlaceholders(): void {
		$payload = ['key1' => 'bla blubb', 'key2' => '$$bla$$ $$blubb$$', 'key3' => '$$blubb$$'];
		$ret = MobileDeviceCommandControllerBase::replacePlaceholders(
			$payload,
			['bla' => 'BLABLA', 'blubb' => 'BLUBBER', 'blib' => 'BLIB']
		);
		$this->assertTrue($ret);
		$this->assertSame($payload, ['key1' => 'bla blubb', 'key2' => 'BLABLA BLUBBER', 'key3' => 'BLUBBER']);
	}

	public function testReplacePlaceholdersInPlist(): void {
		$plist = new CFPropertyList\CFPropertyList();
		$plist->parse(file_get_contents(__DIR__.'/template.mobileconfig'));
		$payload = $plist->toArray();
		$ret = MobileDeviceCommandControllerBase::replacePlaceholders(
			$payload,
			['bla' => 'BLABLA', 'blubb' => '&"\'#*', 'blib' => '<format-specific reserved chars>']
		);
		$this->assertTrue($ret);
		$td = new \CFPropertyList\CFTypeDetector();
		$plist = new \CFPropertyList\CFPropertyList();
		$plist->add( $td->toCFType( $payload ) );
		$this->assertSame($plist->toXML(true), file_get_contents(__DIR__.'/final.mobileconfig'));
	}

	public function testReplacePlaceholdersInJson(): void {
		$payload = json_decode(file_get_contents(__DIR__.'/template.json'), true);
		$ret = MobileDeviceCommandControllerBase::replacePlaceholders(
			$payload,
			['bla' => 'BLABLA', 'blubb' => '&"\'#*', 'blib' => '<format-specific reserved chars>']
		);
		$this->assertTrue($ret);
		$this->assertSame(json_encode($payload), file_get_contents(__DIR__.'/final.json'));
	}

	public function testReplacePlaceholdersParameterMissing(): void {
		$payload = ['key1' => 'bla blubb', 'key2' => '$$bla$$ $$blubb$$', 'key3' => '$$blubb$$'];
		$ret = MobileDeviceCommandControllerBase::replacePlaceholders(
			$payload,
			['bla' => 'BLABLA', 'blib' => 'BLIB']
		);
		$this->assertFalse($ret);
	}

}
