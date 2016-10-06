<?php
/**
 * Tests the DrugController functions that store, edit and delete drugs 
 * @author  (c) @iLabAfrica, Emmanuel Kitsao, Brian Kiprop, Thomas Mapesa, Anthony Ereng
 */

use App\Models\Drug;
use App\Models\User;
use App\Http\Controllers\DrugController;
class DrugControllerTest extends TestCase 
{
	
    /**
     * Initial setup function for tests
     *
     * @return void
     */
    public function setUp(){
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->setVariables();
    }

	/**
	 * Contains the testing sample data for the DrugController.
	 *
	 * @return void
	 */
    public function setVariables()
    {
    	// Initial sample storage data
		$this->drugData = array(
			'name' => 'VANCOMYCININ',
			'description' => 'Lets see',
		);

		
		// Edition sample data
		$this->drugUpdate = array(
			'name' => 'VANCOMYCINININ',
			'description' => 'Staphylococci species',
		);
    }
	
	/**
	 * Tests the store function in the DrugController
	 * @param  void
	 * @return int $testDrugId ID of Drug stored;used in testUpdate() to identify test for update
	 */    
 	public function testStore() 
  	{
		echo "\n\nDRUG CONTROLLER TEST\n\n";
  		 // Store the Drug
		$this->withoutMiddleware();
      	$this->be(User::first());
		
		$response = $this->call('POST', '/drug', $this->drugData);
// dd($response);
		
		$drugStored = Drug::orderBy('id','desc')->take(1)->get()->first();
// dd($drugStored);
		// $drugSaved = Drug::find($drugStored->id);
		$this->assertEquals( $this->drugData['name'] ,$drugStored->name);
		$this->assertEquals($this->drugData['description'] ,$drugStored->description);
  	}

  	/**
  	 * Tests the update function in the DrugController
	 * @param  void
	 * @return void
     */
	public function testUpdate()
	{
		$this->withoutMiddleware();
		$this->call('POST', '/drug', $this->drugData);
		// Update the Drug
		$drugStored = Drug::orderBy('id','desc')->first();

		$this->withoutMiddleware();
		$this->call('PUT', '/drug/1', $this->drugUpdate);

		// $drugUpdated = Drug::find($drugStored->id);
		$drugUpdated = Drug::find('1');
		$this->assertEquals($drugUpdated->name , $this->drugUpdate['name']);
		$this->assertEquals($drugUpdated->description ,$this->drugUpdate['description']);
	}

	/**
  	 * Tests the update function in the DrugController
	 * @param  void
	 * @return void
     */
	public function testDelete()
	{	// todo: to be done later
		/*
		$this->withoutMiddleware();
		$this->call('POST', '/drug', $this->drugData);
		$drugStored = Drug::orderBy('id','desc')->first();

        $drug->delete($drugStored->id);

		$drugDeleted = Drug::withTrashed()->find($drugStored->id);
		$this->assertNotNull($drugDeleted->deleted_at);*/
	}
}