<?php

use PHPUnit\Framework\TestCase;

class LicenseCheckTest extends TestCase {

	/** @dataProvider deviceSerials */
	public function testLicenseCount(array $computerSerials, array $mobileSerials, int $expected): void {
		$db = new class($computerSerials, $mobileSerials) {
			public $settings;
			private $computers;
			private $mobileDevices;

			public function __construct($computers, $mobileDevices) {
				$this->computers = array_map(function($serial) { return (object)['serial'=>$serial]; }, $computers);
				$this->mobileDevices = array_map(function($serial) { return (object)['serial'=>$serial]; }, $mobileDevices);
				$this->settings = new class {
					public function get($key) { return null; }
				};
			}

			public function selectAllComputer() { return $this->computers; }
			public function selectAllMobileDevice() { return $this->mobileDevices; }
		};

		$license = new LicenseCheck($db);
		$this->assertSame($expected, $license->getCurrentObjectCount());
		$this->assertSame($expected <= LicenseCheck::FREE_OBJECTS, $license->isValid());
	}

	public function deviceSerials(): array {
		return [
			'empty inventory' => [[], [], 0],
			'shared serial' => [['ABC'], ['ABC'], 1],
			'different serials' => [['ABC'], ['DEF'], 2],
			'blank serials' => [['', null, ' '], ['', null, ' '], 6],
			'surrounding whitespace' => [[' ABC '], ['ABC'], 1],
			'zero serial' => [['0'], ['0'], 1],
			'computer duplicates' => [['ABC', 'ABC'], [], 2],
			'mobile duplicates' => [[], ['ABC', 'ABC'], 2],
			'free limit with overlap' => [range(1, 20), ['1'], 20],
			'over free limit' => [range(1, 20), ['21'], 21],
		];
	}
}
