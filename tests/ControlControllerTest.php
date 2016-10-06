<?php
/**
 * Tests the MeasureController functions that store, edit and delete measures 
 * @author  (c) @iLabAfrica, Emmanuel Kitsao, Brian Kiprop, Thomas Mapesa, Anthony Ereng
 */
use App\Models\User;
use App\Models\Control;
use App\Models\ControlTest;
use App\Http\Controllers\ControlController;

class ControlControllerTest extends TestCase {

	
	public function setup()
	{
		parent::setUp();
		Artisan::call('migrate');
		Artisan::call('db:seed');
		$this->setVariables();
	}

	/**
	* Testing Control Index page
	*/
	public function testIndex()
	{
		$this->be(User::find(1));
		$this->call('GET', '/control');
		$this->assertViewHas('controls');
	}

	/**
	* Testing Control create method
	*/
	public function testCreate()
	{
		$this->be(User::find(1));
		$this->call('GET', '/control/create');
		$this->assertViewHas('measureTypes');
		$this->assertViewHas('instruments');
	}

	/**
	* Testing Control store function
	*/
	public function testStore()
	{
		$this->withoutMiddleware();

		$response = $this->call('POST', '/control', $this->inputStoreControls);

		$this->assertTrue($response->isRedirection());
		$this->assertRedirectedToRoute('control.index');
		$testControl = Control::orderBy('id', 'desc')->first();
		$this->assertEquals($testControl->name, $this->inputStoreControls['name']);
		$this->assertEquals($testControl->description, $this->inputStoreControls['description']);
		$this->assertEquals($testControl->instrument_id, $this->inputStoreControls['instrument_id']);

		$testControlMeasures = $testControl->controlMeasures;

		foreach ($testControlMeasures as $key => $testControlMeasure) {
			$this->assertEquals($this->inputStoreControls['new_measures'][$key]['name'], $testControlMeasure->name);
			$this->assertEquals($this->inputStoreControls['new_measures'][$key]['unit'], $testControlMeasure->unit);
		}
		//TODO: Test for rangemax and ragnemin. Not working currently due to sqlite rounding of the ranges
	}

	/**
	* Testing Control Update function
	*/
	public function testUpdate()
	{
		$this->withoutMiddleware();
		$response = $this->call('POST', '/control', $this->inputUpdateControls);

		$this->assertTrue($response->isRedirection());
		$this->assertRedirectedToRoute('control.index');

		$testControl = Control::orderBy('id', 'desc')->first();
		$this->assertEquals($testControl->name, $this->inputUpdateControls['name']);
		$this->assertEquals($testControl->description, $this->inputUpdateControls['description']);
		$this->assertEquals($testControl->instrument_id, $this->inputUpdateControls['instrument_id']);

		$testControlMeasures = $testControl->controlMeasures;

		foreach ($testControlMeasures as $key => $testControlMeasure) {
			$this->assertEquals($this->inputUpdateControls['new_measures'][$key]['name'], $testControlMeasure->name);
			$this->assertEquals($this->inputUpdateControls['new_measures'][$key]['unit'], $testControlMeasure->unit);
		}
	}

	/**
	* Testing Control destroy funciton
	*/
	public function testDestroy()
	{
		$this->withoutMiddleware();
		$this->call('DELETE', '/control/1');
		$control = Control::withTrashed()->find(1);
		$this->assertNotNull($control->deleted_at);
	}

	/**
	* Testing Control saveResults function
	*/
	public function testSaveResults()
	{
		$this->withoutMiddleware();
		$this->call('POST', 'control/1/saveResults', $this->inputSaveResults);

		$results = ControlTest::orderBy('id', 'desc')->first()->controlResults;
		foreach ($results as $result) {
			$key = 'm_'.$result->control_measure_id;
			$this->assertEquals($this->inputSaveResults[$key], $result->results);
		}
	}


	private function setVariables(){
		//Setting the current user
		$this->be(User::find(4));

		$this->inputStoreControls = array(
			'name'=>'Lava hound',
			'description' => 'Terrible creature',
			'instrument_id' => 1,
			'new_measures' => array(
				array('name' => 'xx', 'unit' => 'mmol', 'measure_type_id' => 1, 'rangemin' => '2.63', 'rangemax' => '7.19'),
				array('name' => 'zz', 'unit' => 'mol', 'measure_type_id' => 1, 'rangemin' => '11.65', 'rangemax' => '15.43'),
				)
			);

		$this->inputUpdateControls = array(
			'name'=>'Minion',
			'description' => 'Spits black fire',
			'instrument_id' => 1,
			'new_measures' => array(
				array('name' => 'DD', 'unit' => 'mmol', 'measure_type_id' => 1, 'rangemin' => '2.63', 'rangemax' => '7.19'),
				array('name' => 'LYTHIUM', 'unit' => 'dol', 'measure_type_id' => 1, 'rangemin' => '15.73', 'rangemax' => '25.01'),
				array('name' => 'STYROPROPENE', 'unit' => 'tol', 'measure_type_id' => 1, 'rangemin' => '17.63', 'rangemax' => '20.12'),
				)
			);

		$this->inputSaveResults = array(
			'lot_id'=>1,
			'performed_by'=>'Jon Snow',
			'm_1' => '2.78',
			'm_2' => '13.56',
			'm_3' => '14.77',
			'm_4' => '25.92',
			'm_5' => '18.87',
			);
	}
}