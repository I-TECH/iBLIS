<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Requests\TestRequest;

use App\Models\Test;
use App\Models\Measure;
use App\Models\TestStatus;
use App\Models\Visit;
use App\Models\Referral;
use App\Models\TestType;
use App\Models\Patient;
use App\Models\Specimen;
use App\Models\RejectionReason;
use App\Models\Facility;
use App\Models\Barcode;
use Input;
use Response;
use Auth;
use Session;
use Lang;
use Config;
use DNS1D;
/**
 * Contains test resources  
 * 
 */
class TestController extends Controller {

	/**
	 * Display a listing of Tests. Factors in filter parameters
	 * The search string may match: patient_number, patient name, test type name, specimen ID or visit ID
	 *
	 * @return Response
	 */
	public function index()
	{

		$fromRedirect = Session::pull('fromRedirect');

		if($fromRedirect){

			$input = Session::get('TESTS_FILTER_INPUT');
			
		}else{

			$input = Input::except('_token');
		}

		$searchString = isset($input['search'])?$input['search']:'';
		$testStatusId = isset($input['test_status'])?$input['test_status']:'';
		$dateFrom = isset($input['date_from'])?$input['date_from']:'';
		$dateTo = isset($input['date_to'])?$input['date_to']:'';

		// Search Conditions
		if($searchString||$testStatusId||$dateFrom||$dateTo){

			$testSet = Test::search($searchString, $testStatusId, $dateFrom, $dateTo);

			if (count($tests) == 0) {
			 	Session::flash('message', trans('messages.empty-search'));
			}
		}
		else
		{
		// List all the active tests
			$testSet = Test::orderBy('time_created', 'DESC');
		}

		// Create Test Statuses array. Include a first entry for ALL
		$testStatus = array('all')+TestStatus::all()->lists('name','id')->toArray();

		foreach ($testStatus as $key => $value) {
			$testStatus[$key] = trans("messages.$value");
		}


		// Pagination
		$testSet = $testSet->paginate(config('blis.page-items'))->appends($input);

		//	Barcode
		$barcode = Barcode::first();

		// Load the view and pass it the tests
		return view('test.index', compact('testSet', 'testStatus', 'barcode'))->withInput($input);
	}

	/**
	 * Recieve a Test from an external system
	 *
	 * @param
	 * @return Response
	 */
	public function receive($id)
	{
		$test = Test::find($id);
		$test->test_status_id = Test::PENDING;
		$test->time_created = date('Y-m-d H:i:s');
		$test->created_by = Auth::user()->id;
		$test->save();

		return $id;
	}

	/**
	 * Display a form for creating a new Test.
	 *
	 * @return Response
	 */
	public function create($patientID = 0)
	{
		if ($patientID == 0) {
			$patientID = Input::get('patient_id');
		}

		$testtypes = TestType::where('orderable_test')->orderBy('name', 'asc')->get();
		$patient = Patient::find($patientID);

		//Load Test Create View
		return view('test.create', compact('testtypes', 'patient'));
	}

	/**
	 * Save a new Test.
	 *
	 * @return Response
	 */
	public function saveNewTest(TestRequest $request)
	{
		//Create New Test
		$visitType = ['Out-patient','In-patient'];
		$activeTest = array();

		/*
		* - Create a visit
		* - Fields required: visit_type, patient_id
		*/
		$visit = new Visit;
		$visit->patient_id = $request->patient_id;
		$visit->visit_type = $visitType[$request->visit_type];
		$visit->save();

		/*
		* - Create tests requested
		* - Fields required: visit_id, test_type_id, specimen_id, test_status_id, created_by, requested_by
		*/
		$testTypes = $request->testtypes;
		if(is_array($testTypes)){
			foreach ($testTypes as $value)
			{
				$testTypeID = (int)$value;
				// Create Specimen - specimen_type_id, accepted_by, referred_from, referred_to
				$specimen = new Specimen;
				$specimen->specimen_type_id = TestType::find($testTypeID)->specimenTypes->lists('id')[0];
				$specimen->accepted_by = Auth::user()->id;
				$specimen->save();

				$test = new Test;
				$test->visit_id = $visit->id;
				$test->test_type_id = $testTypeID;
				$test->specimen_id = $specimen->id;
				$test->test_status_id = Test::PENDING;
				$test->created_by = Auth::user()->id;
				$test->requested_by = Input::get('physician');
				$test->save();

				$activeTest[] = $test->id;
			}
		}
		$url = session('SOURCE_URL');

        return redirect()->to($url)->with('message', trans('messages.record-successfully-saved'))->with('active_test', $test ->id);
	}

	/**
	 * Display Rejection page 
	 *
	 * @param
	 * @return
	 */
	public function reject($specimenID)
	{
		$specimen = Specimen::find($specimenID);
		$rejectionReasons = RejectionReason::lists('reason', 'id')->all();
		return view('test.reject', compact('specimen', 'rejectionReasons'));
	}

	/**
	 * Executes Rejection
	 *
	 * @param
	 * @return
	 */
	public function rejectAction(Request $request)
	{
		//Reject justifying why.
		$rules = array(
			'rejectionReason' => 'required|non_zero_key',
			'reject_explained_to' => 'required',
		);
		$this->validate($request, $rules);

		$specimen = Specimen::find(Input::get('specimen_id'));
		$specimen->rejection_reason_id = Input::get('rejectionReason');
		$specimen->specimen_status_id = Specimen::REJECTED;
		$specimen->rejected_by = Auth::user()->id;
		$specimen->time_rejected = date('Y-m-d H:i:s');
		$specimen->reject_explained_to = Input::get('reject_explained_to');
		$specimen->save();
		
		$url = session('SOURCE_URL');
		
		return redirect()->to($url)->with('message', 'terms.success-rejecting-specimen')->with('activeTest', array($specimen->test->id));
	}

	/**
	 * Accept a Test's Specimen
	 *
	 * @param
	 * @return
	 */
	public function accept()
	{
		$specimen = Specimen::find(Input::get('id'));
		$specimen->specimen_status_id = Specimen::ACCEPTED;
		$specimen->accepted_by = Auth::user()->id;
		$specimen->time_accepted = date('Y-m-d H:i:s');
		$specimen->save();

		return $specimen->specimen_status_id;
	}

	/**
	 * Display Change specimenType form fragment to be loaded in a modal via AJAX
	 *
	 * @param
	 * @return
	 */
	public function changeSpecimenType()
	{
		$test = Test::find(Input::get('id'));
		$specimentypes = SpecimenType::lists('name', 'id');
		return view('test.changeSpecimenType', compact('test'));
	}

	/**
	 * Update a Test's SpecimenType
	 *
	 * @param
	 * @return
	 */
	public function updateSpecimenType()
	{
		$specimen = Specimen::find(Input::get('specimen_id'));
		$specimen->specimen_type_id = Input::get('specimen_type');
		$specimen->save();

		return view('test.viewDetails', array($specimen->test->id));
	}

	/**
	 * Starts Test
	 *
	 * @param
	 * @return
	 */
	public function start()
	{
		$test = Test::find(Input::get('id'));
		$test->test_status_id = Test::STARTED;
		$test->time_started = date('Y-m-d H:i:s');
		$test->save();

		return $test->test_status_id;
	}

	/**
	 * Display Result Entry page
	 *
	 * @param
	 * @return
	 */
	public function enterResults($testID)
	{
		$test = Test::find($testID);
		if($test->testType->instruments->count() > 0){
			//Delete the celtac dump file
			//TO DO: Clean up and use configs + Handle failure
			$EMPTY_FILE_URL = "http://192.168.1.88/celtac/emptyfile.php";
			@file_get_contents($EMPTY_FILE_URL);
		}
		return view('test.enterResults', compact('test'));
	}

	/**
	 * Returns test result intepretation
	 * @param
	 * @return
	 */
	public function getResultInterpretation()
	{
		$result = array();
		//save if it is available
		
		if (Input::get('age')) {
			$result['birthdate'] = Input::get('age');
			$result['gender'] = Input::get('gender');
		}
		$result['measureid'] = Input::get('measureid');
		$result['measurevalue'] = Input::get('measurevalue');

		$measure = new Measure;
		return $measure->getResultInterpretation($result);
	}

	/**
	 * Saves Test Results
	 *
	 * @param $testID to save
	 * @return view
	 */
	public function saveResults($testID)
	{
		$test = Test::find($testID);
		$test->test_status_id = Test::COMPLETED;
		$test->interpretation = Input::get('interpretation');
		$test->tested_by = Auth::user()->id;
		$test->time_completed = date('Y-m-d H:i:s');
		$test->save();
		
		foreach ($test->testType->measures as $measure) {
			$testResult = TestResult::firstOrCreate(array('test_id' => $testID, 'measure_id' => $measure->id));
			$testResult->result = Input::get('m_'.$measure->id);
			$testResult->save();
		}

		//Fire of entry saved/edited event
		Event::fire('test.saved', array($testID));

		$input = Session::get('TESTS_FILTER_INPUT');
		Session::put('fromRedirect', 'true');

		// Get page
		$url = session('SOURCE_URL');
		$urlParts = explode('&', $url);
		if(isset($urlParts['page'])){
			$pageParts = explode('=', $urlParts['page']);
			$input['page'] = $pageParts[1];
		}

		// redirect
		return Redirect::action('TestController@index')
					->with('message', trans('terms.record-successfully-saved'))
					->with('activeTest', array($test->id))
					->withInput($input);
	}

	/**
	 * Display Edit page
	 *
	 * @param
	 * @return
	 */
	public function edit($testID)
	{
		$test = Test::find($testID);

		return view('test.edit', compact('test'));
	}

	/**
	 * Display Test Details
	 *
	 * @param
	 * @return
	 */
	public function viewDetails($testID)
	{
		$test = Test::find($testID);
		return view('test.viewDetails', compact('test'));
	}

	/**
	 * Verify Test
	 *
	 * @param
	 * @return
	 */
	public function verify($testID)
	{
		$test = Test::find($testID);
		$test->test_status_id = Test::VERIFIED;
		$test->time_verified = date('Y-m-d H:i:s');
		$test->verified_by = Auth::user()->id;
		$test->save();

		//Fire of entry verified event
		Event::fire('test.verified', array($testID));

		return view('test.viewDetails', compact('test'));
	}

	/**
	 * Refer the test
	 *
	 * @param specimenId
	 * @return View
	 */
	public function showRefer($specimenId)
	{
		$specimen = Specimen::find($specimenId);
		$facilities = Facility::lists('name', 'id');
		//Referral facilities
		return view('test.refer', compact('specimen', 'facilities'));
	}

	/**
	 * Refer action
	 *
	 * @return View
	 */
	public function referAction()
	{
		//Validate
		$rules = array(
			'referral-status' => 'required',
			'facility_id' => 'required|non_zero_key',
			'person',
			'contacts'
			);
		$validator = Validator::make(Input::all(), $rules);
		$specimenId = Input::get('specimen_id');

		if ($validator->fails())
		{
			return view('test.refer', array($specimenId))-> withInput()->withErrors($validator);
		}

		//Insert into referral table
		$referral = new Referral();
		$referral->status = Input::get('referral-status');
		$referral->facility_id = Input::get('facility_id');
		$referral->person = Input::get('person');
		$referral->contacts = Input::get('contacts');
		$referral->user_id = Auth::user()->id;

		//Update specimen referral status
		$specimen = Specimen::find($specimenId);

		DB::transaction(function() use ($referral, $specimen) {
			$referral->save();
			$specimen->referral_id = $referral->id;
			$specimen->save();
		});

		//Start test
		Input::merge(array('id' => $specimen->test->id)); //Add the testID to the Input
		$this->start();

		//Return view
		$url = session('SOURCE_URL');
		
		return redirect()->to($url)->with('message', trans('messages.specimen-successful-refer'))->with('activeTest', array($specimen->test->id));
	}
	/**
	 * Culture worksheet for Test
	 *
	 * @param
	 * @return
	 */
	public function culture()
	{
		$test = Test::find(Input::get('testID'));
		$test->test_status_id = Test::VERIFIED;
		$test->time_verified = date('Y-m-d H:i:s');
		$test->verified_by = Auth::user()->id;
		$test->save();

		//Fire of entry verified event
		Event::fire('test.verified', array($testID));

		return view('test.viewDetails', compact('test'));
	}
	/**
	 * generate barcode
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function barcode()
	{
		$id = Input::get('sId');
		$spec = Specimen::find($id);
		return $spec->barcode();
	}
}