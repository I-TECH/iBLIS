<?php namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use App\Http\Requests;
use App\Http\Requests\FacilityRequest;
use App\Models\Instrument;
use Response;
use Auth;
use Session;
use Lang;
/**
 *Contains functions for managing instruments
 *
 */
// todo: change Input to request form
class InstrumentController extends Controller {

	/**
	 * Display a listing of the instruments.
	 *
	 * @return Response
	 */
	public function index()
	{
		// List all the active instruments
			$instruments = Instrument::paginate(config('blis.page-items'));

		// Load the view and pass the instruments
		return view('instrument.index')->with('instruments', $instruments);
	}

	/**
	 * Show the form for creating a new instrument.
	 *
	 * @return Response
	 */
	public function create()
	{
		// Get a list of all installed plugins
		$plugins = [''=>''] + Instrument::getInstalledPlugins();

		//Create Instrument view
		return view('instrument.create')->with('instruments', $plugins);
	}

	/**
	 * Store a newly created instrument in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
		$rules = array(
			'name' => 'required',
			'ip' => 'sometimes|ip',
		);
		$validator = Validator::make(Input::all(), $rules);

		// Validate form input
		if ($validator->fails()) {
			return redirect()->to('instrument.create')->withErrors($validator);
		} else {
			// Save the instrument
			$newInstrument = new Instrument();
			$newInstrument->name = Input::get('name');
			$newInstrument->description = Input::get('description');
			$newInstrument->ip = Input::get('ip');
			$newInstrument->hostname = Input::get('hostname');

			$newInstrument->save();
			return redirect()->to('instrument.index')->with('message', trans('messages.success-creating-instrument'));
		}
	}

	/**
	 * Display the specified instrument.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//Show an instrument
		$instrument = Instrument::find($id);

		//Show the view and pass the $instrument to it
		return view('instrument.show')->with('instrument', $instrument);
	}

	/**
	 * Show the form for editing the specified instrument.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//Get the instrument
		$instrument = Instrument::find($id);

		//Open the Edit View and pass to it the $instrument
		return view('instrument.edit')->with('instrument', $instrument);
	}

	/**
	 * Update the specified instrument.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
		$rules = array(
			'name' => 'required',
			'ip' => 'required|ip'
		);
		$validator = Validator::make(Input::all(), $rules);

		// process the login
		if ($validator->fails()) {
			return Redirect::back()->withErrors($validator);
		} else {
			// Update
			$instrument = Instrument::find($id);
			$instrument->name = Input::get('name');
			$instrument->description = Input::get('description');
			$instrument->ip = Input::get('ip');
			$instrument->hostname = Input::get('hostname');

			try{
				$instrument->save();
				$message = trans('messages.success-updating-instrument');
			}catch(QueryException $e){
				$message = trans('messages.failure-updating-instrument');
				Log::error($e);
			}

			return redirect()->to('instrument.index')->with('message', $message);
		}
	}

	/**
	 * Remove the specified instrument from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

	/**
	 * Remove the specified instruments from storage (global UI implementation).
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function delete($id)
	{
		//Delete the instrument
		$instrument = Instrument::find($id);
 
		$instrument->testTypes()->detach();
		$instrument->delete();

		// redirect
		return redirect()->to('instrument.index')->with('message', trans('messages.success-deleting-instrument'));
	}

	/**
	 * Pull test results from an instrument as JSON.
	 *
	 * @return Response
	 */
	public function getTestResult()
	{
		//Get Instrument Interface Class file
		$testTypeID = Input::get("test_type_id");
		$testType = TestType::find($testTypeID);
		$instrument = $testType->instruments->first();

 		// Fetch the results
		return $instrument->fetchResult($testType);
	}

	/**
	 * Save an imported implemention of the Intrumentation class.
	 *
	 * @param String route
	 * @return Response
	 */
	public function importDriver()
	{
		$route = (Input::get('import_file') !== null)?Input::get('import_file'):"instrument.index";

        $rules = array(
            'import_file' => 'required|max:500'
        );

        $validator = Validator::make(Input::all(), $rules);
        $message = null;

        if ($validator->fails()) {
            return redirect()->to('instrument.index')->withErrors($validator);
        } else {
            if (Input::hasFile('import_file')) {
            	$message = Instrument::saveDriver(Input::file('import_file'));
            }
        }

		return redirect()->to($route)->with('message', $message);
	}
}
