<?php
/**
 * Tests the PatientController functions that store, edit and delete patient infomation 
 * @author  (c) @iLabAfrica, Emmanuel Kitsao, Brian Kiprop, Thomas Mapesa, Anthony Ereng
 */
use App\Models\User;
use App\Models\Patient;
use App\Http\Controllers\PatientController;

class PatientControllerTest extends TestCase 
{
	
	
	    public function setUp()
	    {
	    	parent::setUp();
	    	Artisan::call('migrate');
      		Artisan::call('db:seed');
			$this->setVariables();
	    }
	
	/**
	 * Contains the testing sample data for the PatientController.
	 *
	 * @return void
	 */
		public function setVariables(){
		// Initial sample storage data
		$this->input = array(
			'patient_number' => '6666',//Must be unique!
			'name' => 'Bob Tzhebuilder',
			'dob' => '1930-07-05',
			'gender' => '0',//male
			'email' => 'builderone@concretejungle.com',
			'address' => '788347 W3-x2 Down.croncrete',
			'phone_number' => '+189012402938',
		);

		// Edition sample data
		$this->inputUpdate = array(
			'patient_number' => '5555',
			'name' => 'Bob Thebuilder',
			'dob' => '1900-07-05',
			'gender' => '0',//male
			'email' => 'buildandt@concretejungle.com',
			'address' => '788357 W3-x2 Down.croncrete',
			'phone_number' => '+18966602938',
		);
	}
	/**
	 * Tests the store function in the PatientController
	 * @param  void
	 * @return int $testPatientId ID of Patient stored; used in testUpdate() to identify test for update
	 */    
 	public function testStore() 
  	{
		echo "\n\nPATIENT CONTROLLER TEST\n\n";

		$this->be(User::first());

  		 // Store the Patient Types
		$this->runStore($this->input);

		$patientSaved = Patient::orderBy('id','desc')->first();
		
		$this->assertEquals($patientSaved->patient_number, $this->input['patient_number']);
		$this->assertEquals($patientSaved->name, $this->input['name']);
		$this->assertEquals($patientSaved->dob, $this->input['dob']);
		$this->assertEquals($patientSaved->gender, $this->input['gender']);
		$this->assertEquals($patientSaved->email, $this->input['email']);
		$this->assertEquals($patientSaved->address, $this->input['address']);
		$this->assertEquals($patientSaved->phone_number, $this->input['phone_number']);
  	}

  	/**
  	* Tests the update function in the PatientController
     	* @depends testStore
	* @param  void
	* @return void
     */
	public function testUpdate()
	{
		$this->be(User::first());
		
		$this->runStore($this->input);
		$patientSaved = Patient::orderBy('id','desc')->first();
		// Update the Patient Types
		$this->runUpdate($this->inputUpdate, $patientSaved->id);

		$patientUpdated = Patient::orderBy('id','desc')->first();

		$this->assertEquals($patientUpdated->patient_number, $this->inputUpdate['patient_number']);
		$this->assertEquals($patientUpdated->name, $this->inputUpdate['name']);
		$this->assertEquals($patientUpdated->dob, $this->inputUpdate['dob']);
		$this->assertEquals($patientUpdated->gender, $this->inputUpdate['gender']);
		$this->assertEquals($patientUpdated->email, $this->inputUpdate['email']);
		$this->assertEquals($patientUpdated->address, $this->inputUpdate['address']);
		$this->assertEquals($patientUpdated->phone_number, $this->inputUpdate['phone_number']);
	}

	
	
	/**
  	 * Tests the update function in the PatientController
     * @depends testStore
	 * @param void
	 * @return void
     */
	public function testDelete()
	{
		$this->be(User::first());
		
		$this->runStore($this->input);
		$patientSaved = Patient::orderBy('id','desc')->first();

		$patient = new PatientController;
    	$patient->delete($patientSaved->id);
		$patientsDeleted = Patient::withTrashed()->find($patientSaved->id);
		$this->assertNotNull($patientsDeleted->deleted_at);
	}
	
	
  	/**
  	 *Executes the store function in the PatientController
  	 * @param  array $input Patient details
	 * @return void
  	 */
	public function runStore($input)
	{
		$this->withoutMiddleware();
		$this->call('POST', '/patient', $input);
	}

  	/**
  	 * Executes the update function in the PatientController
  	 * @param  array $input Patient details, int $id ID of the Patient stored
	 * @return void
  	 */
	public function runUpdate($input, $id)
	{
		$this->withoutMiddleware();
		$this->call('PUT', '/patient/'.$id, $input);
	}

}