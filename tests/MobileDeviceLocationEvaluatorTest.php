<?php

use PHPUnit\Framework\TestCase;

class MobileDeviceLocationEvaluatorTest extends TestCase {

	private function command(string $name, $state, array $message=[], string $finished='2026-08-12 15:20:00'): object {
		return (object)['name'=>$name, 'state'=>$state, 'message'=>$message, 'finished'=>$finished];
	}

	private function evaluate(array $commands=[], array $info=['IsSupervised'=>true, 'IsMDMLostModeEnabled'=>true]): array {
		return MobileDeviceLocationEvaluator::evaluate($info, $commands, '2026-08-12 15:00:00', function($message) {
			return $message;
		});
	}

	private function validLocation(): array {
		return ['Latitude'=>51.050409, 'Longitude'=>13.737262, 'HorizontalAccuracy'=>6.4, 'Timestamp'=>'2026-08-12T15:20:00Z', 'Status'=>'Acknowledged'];
	}

	public function testValidDeviceLocationResponse(): void {
		$result = $this->evaluate([$this->command('DeviceLocation', Models\MobileDeviceCommand::STATE_SUCCESS, $this->validLocation())]);
		$this->assertSame(51.050409, $result['deviceLocation']['Latitude']);
		$this->assertSame(13.737262, $result['deviceLocation']['Longitude']);
		$this->assertSame(6.4, MobileDeviceLocationEvaluator::getHorizontalAccuracy($result['deviceLocation']));
	}

	/** @dataProvider invalidCoordinates */
	public function testInvalidCoordinatesAreRejected(float $latitude, float $longitude): void {
		$location = $this->validLocation();
		$location['Latitude'] = $latitude;
		$location['Longitude'] = $longitude;
		$this->assertNull($this->evaluate([$this->command('DeviceLocation', Models\MobileDeviceCommand::STATE_SUCCESS, $location)])['deviceLocation']);
	}

	public function invalidCoordinates(): array {
		return [[90.1, 13.7], [-90.1, 13.7], [51.0, 180.1], [51.0, -180.1]];
	}

	public function testMissingAndNegativeHorizontalAccuracyAreNotDisplayed(): void {
		$location = $this->validLocation();
		unset($location['HorizontalAccuracy']);
		$this->assertNull(MobileDeviceLocationEvaluator::getHorizontalAccuracy($location));
		$location['HorizontalAccuracy'] = -0.1;
		$this->assertNull(MobileDeviceLocationEvaluator::getHorizontalAccuracy($location));
	}

	public function testLatestDeviceLocationPending(): void {
		$result = $this->evaluate([$this->command('DeviceLocation', Models\MobileDeviceCommand::STATE_SENT)]);
		$this->assertTrue($result['locationRequestPending']);
	}

	/** @dataProvider numericStringPendingStates */
	public function testNumericStringDeviceLocationStateIsPending(string $state): void {
		$result = $this->evaluate([$this->command('DeviceLocation', $state)]);
		$this->assertTrue($result['locationRequestPending']);
	}

	public function numericStringPendingStates(): array {
		return [['0'], ['2']];
	}

	public function testFailedLatestRequestRetainsOlderSuccessfulLocation(): void {
		$result = $this->evaluate([
			$this->command('DeviceLocation', Models\MobileDeviceCommand::STATE_FAILED),
			$this->command('DeviceLocation', Models\MobileDeviceCommand::STATE_SUCCESS, $this->validLocation()),
		]);
		$this->assertTrue($result['locationRequestFailed']);
		$this->assertNotNull($result['deviceLocation']);
	}

	public function testLostModeDisabled(): void {
		$this->assertFalse($this->evaluate([], ['IsSupervised'=>true, 'IsMDMLostModeEnabled'=>false])['isLostModeEnabled']);
	}

	public function testLostModeCommandPending(): void {
		$result = $this->evaluate([$this->command('EnableLostMode', Models\MobileDeviceCommand::STATE_QUEUED)]);
		$this->assertTrue($result['lostModeCommandPending']);
	}

	public function testMissingSupervisedStatus(): void {
		$this->assertNull($this->evaluate([], ['IsMDMLostModeEnabled'=>true])['isSupervised']);
	}

	public function testSupervisedFalse(): void {
		$this->assertFalse($this->evaluate([], ['IsSupervised'=>false, 'IsMDMLostModeEnabled'=>true])['isSupervised']);
	}

	public function testNewSuccessfulLostModeCommandOverridesStaleInventory(): void {
		$result = $this->evaluate([$this->command('DisableLostMode', Models\MobileDeviceCommand::STATE_SUCCESS)]);
		$this->assertFalse($result['isLostModeEnabled']);
	}
}
