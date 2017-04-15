<?php
set_time_limit(0); //60 seconds = 1 minute
class ReportController extends \BaseController {
	//	Begin patient report functions
	/**
	 * Display a listing of the resource.
	 * Called loadPatients because the same controller shall be used for all other reports
	 * @return Response
	 */
	public function loadPatients()
	{
		$search = Input::get('search');

		$patients = Patient::search($search)->orderBy('id','DESC')->paginate(Config::get('kblis.page-items'));

		if (count($patients) == 0) {
		 	Session::flash('message', trans('messages.no-match'));
		}

		// Load the view and pass the patients
		return View::make('reports.patient.index')->with('patients', $patients)->withInput(Input::all());
	}

	/**
	 * Display data after applying the filters on the report uses patient ID
	 *
	 * @return Response
	 */
	public function viewPatientReport($id, $visit = null, $testId = null){
		$from = Input::get('start');
		$to = Input::get('end');
		$pending = Input::get('pending');
		$date = date('Y-m-d');
		$error = '';
		$visitId = Input::get('visit_id');
		//	Check checkbox if checked and assign the 'checked' value
		if (Input::get('tests') === '1') {
		    $pending='checked';
		}
		//	Query to get tests of a particular patient
		if (($visit || $visitId) && $id && $testId){
			$tests = Test::where('id', '=', $testId);
		}
		else if(($visit || $visitId) && $id){
			$tests = Test::where('visit_id', '=', $visit?$visit:$visitId);
		}
		else{
			$tests = Test::join('visits', 'visits.id', '=', 'tests.visit_id')
							->where('patient_id', '=', $id);
		}
		//	Begin filters - include/exclude pending tests
		if($pending){
			$tests=$tests->where('tests.test_status_id', '!=', Test::NOT_RECEIVED);
		}
		else{
			$tests = $tests->whereIn('tests.test_status_id', [Test::COMPLETED, Test::VERIFIED]);
		}
		//	Date filters
		if($from||$to){

			if(!$to) $to = $date;

			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
					$error = trans('messages.check-date-range');
			}
			else
			{
				$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
				$tests=$tests->whereBetween('time_created', array($from, $toPlusOne->format('Y-m-d H:i:s')));
			}
		}
		//	Get tests collection
		$tests = $tests->get(array('tests.*'));
		//	Get patient details
		$patient = Patient::find($id);
		//	Check if tests are accredited
		$accredited = $this->accredited($tests);
		$verified = array();
		foreach ($tests as $test) {
			if($test->isVerified())
				array_push($verified, $test->id);
			else
				continue;
		}
		if(Input::has('word')){
			$date = date("Ymdhi");
			$fileName = "blispatient_".$id."_".$date.".doc";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.patient.export')
							->with('patient', $patient)
							->with('tests', $tests)
							->with('from', $from)
							->with('to', $to)
							->with('visit', $visit)
							->with('accredited', $accredited);
	    	return Response::make($content,200, $headers);
		}
		else if(Input::has('excel')){
			$date = date("Ymdhi");
			$fileName = "blispatient_".$id."_".$date.".xls";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.patient.export')
							->with('patient', $patient)
							->with('tests', $tests)
							->with('from', $from)
							->with('to', $to)
							->with('visit', $visit)
							->with('accredited', $accredited);
	    	return Response::make($content,200, $headers);
		}
		else {
			return View::make('reports.patient.report')
						->with('patient', $patient)
						->with('tests', $tests)
						->with('pending', $pending)
						->with('error', $error)
						->with('visit', $visit)
						->with('accredited', $accredited)
						->with('verified', $verified)
						->withInput(Input::all());
		}
	}
	//	End patient report functions

	/**
	*	Function to return test types of a particular test category to fill test types dropdown
	*/
	public function reportsDropdown(){
        $input = Input::get('option');
        $testCategory = TestCategory::find($input);
        $testTypes = $testCategory->testTypes();
        return Response::make($testTypes->get(['id','name']));
    }

	//	Begin Daily Log-Patient report functions
	/**
	 * Display a view of the daily patient records.
	 *
	 */
	public function dailyLog()
	{
		$from = Input::get('start');
		$to = Input::get('end');
		$pendingOrAll = Input::get('pending_or_all');
		$error = '';
		$accredited = array();
		//	Check radiobutton for pending/all tests is checked and assign the 'true' value
		if (Input::get('tests') === '1') {
		    $pending='true';
		}
		$date = date('Y-m-d');
		if(!$to){
			$to=$date;
		}
		$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
		$records = Input::get('records');
		$testCategory = Input::get('section_id');
		$testType = Input::get('test_type');
		$labSections = TestCategory::lists('name', 'id');
		if($testCategory)
			$testTypes = TestCategory::find($testCategory)->testTypes->lists('name', 'id');
		else
			$testTypes = array(""=>"");
		
		if($records=='patients'){
			if($from||$to){
				if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
						$error = trans('messages.check-date-range');
				}
				else{
					$visits = Visit::whereBetween('created_at', array($from, $toPlusOne))->get();
				}
				if (count($visits) == 0) {
				 	Session::flash('message', trans('messages.no-match'));
				}
			}
			else{

				$visits = Visit::where('created_at', 'LIKE', $date.'%')->orderBy('patient_id')->get();
			}
			if(Input::has('word')){
				$date = date("Ymdhi");
				$fileName = "daily_visits_log_".$date.".doc";
				$headers = array(
				    "Content-type"=>"text/html",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);
				$content = View::make('reports.daily.exportPatientLog')
								->with('visits', $visits)
								->with('accredited', $accredited)
								->withInput(Input::all());
		    	return Response::make($content,200, $headers);
			}
			else if(Input::has('excel')){
				$date = date("Ymdhi");
				$fileName = "daily_visits_log_".$date.".xls";
				$headers = array(
				    "Content-type"=>"text/html",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);
				$content = View::make('reports.daily.exportPatientLog')
								->with('visits', $visits)
								->with('accredited', $accredited)
								->withInput(Input::all());
		    	return Response::make($content,200, $headers);
			}
			else{
				return View::make('reports.daily.patient')
								->with('visits', $visits)
								->with('error', $error)
								->with('accredited', $accredited)
								->withInput(Input::all());
			}
		}
		//Begin specimen rejections
		else if($records=='rejections')
		{
			$specimens = Specimen::where('specimen_status_id', '=', Specimen::REJECTED);
			/*Filter by test category*/
			if($testCategory&&!$testType){
				$specimens = $specimens->join('tests', 'specimens.id', '=', 'tests.specimen_id')
									   ->join('test_types', 'tests.test_type_id', '=', 'test_types.id')
									   ->where('test_types.test_category_id', '=', $testCategory);
			}
			/*Filter by test type*/
			if($testCategory&&$testType){
				$specimens = $specimens->join('tests', 'specimens.id', '=', 'tests.specimen_id')
				   					   ->where('tests.test_type_id', '=', $testType);
			}

			/*Filter by date*/
			if($from||$to){
				if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
						$error = trans('messages.check-date-range');
				}
				else
				{
					$specimens = $specimens->whereBetween('time_rejected', 
						array($from, $toPlusOne))->get(array('specimens.*'));
				}
			}
			else
			{
				$specimens = $specimens->where('time_rejected', 'LIKE', $date.'%')->orderBy('id')
										->get(array('specimens.*'));
			}
			if(Input::has('word')){
				$date = date("Ymdhi");
				$fileName = "daily_rejected_specimen_".$date.".doc";
				$headers = array(
				    "Content-type"=>"text/html",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);
				$content = View::make('reports.daily.exportSpecimenLog')
								->with('specimens', $specimens)
								->with('testCategory', $testCategory)
								->with('testType', $testType)
								->with('accredited', $accredited)
								->withInput(Input::all());
		    	return Response::make($content,200, $headers);
			}
			else if(Input::has('excel')){
				$date = date("Ymdhi");
				$fileName = "daily_rejected_specimen_".$date.".xls";
				$headers = array(
				    "Content-type"=>"text/html",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);
				$content = View::make('reports.daily.exportSpecimenLog')
								->with('specimens', $specimens)
								->with('testCategory', $testCategory)
								->with('testType', $testType)
								->with('accredited', $accredited)
								->withInput(Input::all());
		    	return Response::make($content,200, $headers);
			}
			else
			{
				return View::make('reports.daily.specimen')
							->with('labSections', $labSections)
							->with('testTypes', $testTypes)
							->with('specimens', $specimens)
							->with('testCategory', $testCategory)
							->with('testType', $testType)
							->with('error', $error)
							->with('accredited', $accredited)
							->withInput(Input::all());
			}
		}
		//Begin test records
		else
		{
			$tests = Test::whereNotIn('test_status_id', [Test::NOT_RECEIVED]);
			
			/*Filter by test category*/
			if($testCategory&&!$testType){
				$tests = $tests->join('test_types', 'tests.test_type_id', '=', 'test_types.id')
							   ->where('test_types.test_category_id', '=', $testCategory);
			}
			/*Filter by test type*/
			if($testType){
				$tests = $tests->where('test_type_id', '=', $testType);
			}
			/*Filter by all tests*/
			if($pendingOrAll=='pending'){
				$tests = $tests->whereIn('test_status_id', [Test::PENDING, Test::STARTED]);
			}
			else if($pendingOrAll=='all'){
				$tests = $tests->whereIn('test_status_id', 
					[Test::PENDING, Test::STARTED, Test::COMPLETED, Test::VERIFIED]);
			}
			//For Complete tests and the default.
			else{
				$tests = $tests->whereIn('test_status_id', [Test::COMPLETED, Test::VERIFIED]);
			}
			/*Get collection of tests*/
			/*Filter by date*/
			if($from||$to){
				if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
						$error = trans('messages.check-date-range');
				}
				else
				{
					$tests = $tests->whereBetween('time_created', array($from, $toPlusOne))->get(array('tests.*'));
				}
			}
			else
			{
				$tests = $tests->where('time_created', 'LIKE', $date.'%')->get(array('tests.*'));
			}
			if(Input::has('word')){
				$date = date("Ymdhi");
				$fileName = "daily_test_records_".$date.".doc";
				$headers = array(
				    "Content-type"=>"text/html",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);
				$content = View::make('reports.daily.exportTestLog')
								->with('tests', $tests)
								->with('testCategory', $testCategory)
								->with('testType', $testType)
								->with('pendingOrAll', $pendingOrAll)
								->with('accredited', $accredited)
								->withInput(Input::all());
		    	return Response::make($content,200, $headers);
			}
			else if(Input::has('excel')){
				$date = date("Ymdhi");
				$fileName = "daily_test_records_".$date.".xls";
				$headers = array(
				    "Content-type"=>"text/html",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);
				$content = View::make('reports.daily.exportTestLog')
								->with('tests', $tests)
								->with('testCategory', $testCategory)
								->with('testType', $testType)
								->with('pendingOrAll', $pendingOrAll)
								->with('accredited', $accredited)
								->withInput(Input::all());
		    	return Response::make($content,200, $headers);
			}
			else
			{
				return View::make('reports.daily.test')
							->with('labSections', $labSections)
							->with('testTypes', $testTypes)
							->with('tests', $tests)
							->with('accredited', $this->accredited($tests))
							->with('counts', $tests->count())
							->with('testCategory', $testCategory)
							->with('testType', $testType)
							->with('pendingOrAll', $pendingOrAll)
							->with('accredited', $accredited)
							->with('error', $error)
							->withInput(Input::all());
			}
		}
	}
	//	End Daily Log-Patient report functions

	/*	Begin Aggregate reports functions	*/
	//	Begin prevalence rates reports functions
	/**
	 * Display a both chart and table on load.
	 *
	 * @return Response
	 */
	public function prevalenceRates()
	{
		$from = Input::get('start');
		$to = Input::get('end');
		$today = date('Y-m-d');
		$year = date('Y');
		$testTypeID = Input::get('test_type');

		//	Apply filters if any
		if(Input::has('filter')){

			if(!$to) $to=$today;

			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($today)||strtotime($to)>strtotime($today)){
				Session::flash('message', trans('messages.check-date-range'));
			}

			$months = json_decode(self::getMonths($from, $to));
			$data = TestType::getPrevalenceCounts($from, $to, $testTypeID);
			$chart = self::getPrevalenceRatesChart($testTypeID);
		}
		else
		{
			// Get all tests for the current year
			$test = Test::where('time_created', 'LIKE', date('Y').'%');
			$periodStart = $test->min('time_created'); //Get the minimum date
			$periodEnd = $test->max('time_created'); //Get the maximum date
			$data = TestType::getPrevalenceCounts($periodStart, $periodEnd);
			$chart = self::getPrevalenceRatesChart();
		}

		return View::make('reports.prevalence.index')
						->with('data', $data)
						->with('chart', $chart)
						->withInput(Input::all());
	}

	/**
	* Get months: return months for time_created column when filter dates are set
	*/	
	public static function getMonths($from, $to){
		$today = "'".date("Y-m-d")."'";
		$year = date('Y');
		$tests = Test::select('time_created')->distinct();

		if(strtotime($from)===strtotime($today)){
			$tests = $tests->where('time_created', 'LIKE', $year.'%');
		}
		else
		{
			$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
			$tests = $tests->whereBetween('time_created', array($from, $toPlusOne));
		}

		$allDates = $tests->lists('time_created');
		asort($allDates);
		$yearMonth = function($value){return strtotime(substr($value, 0, 7));};
		$allDates = array_map($yearMonth, $allDates);
		$allMonths = array_unique($allDates);
		$dates = array();

		foreach ($allMonths as $date) {
			$dateInfo = getdate($date);
			$dates[] = array('months' => $dateInfo['mon'], 'label' => substr($dateInfo['month'], 0, 3),
				'annum' => $dateInfo['year']);
		}

		return json_encode($dates);
	}
	/**
	 * Display prevalence rates chart
	 *
	 * @return Response
	 */
	public static function getPrevalenceRatesChart($testTypeID = 0){
		$from = Input::get('start');
		$to = Input::get('end');
		$months = json_decode(self::getMonths($from, $to));
		$testTypes = new Illuminate\Database\Eloquent\Collection();

		if($testTypeID == 0){
			
			$testTypes = TestType::supportPrevalenceCounts();
		}else{
			$testTypes->add(TestType::find($testTypeID));
		}

		$options = '{
		    "chart": {
		        "type": "spline"
		    },
		    "title": {
		        "text":"'.trans('messages.prevalence-rates').'"
		    },
		    "subtitle": {
		        "text":'; 
		        if($from==$to)
		        	$options.='"'.trans('messages.for-the-year').' '.date('Y').'"';
		        else
		        	$options.='"'.trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to.'"';
		    $options.='},
		    "credits": {
		        "enabled": false
		    },
		    "navigation": {
		        "buttonOptions": {
		            "align": "right"
		        }
		    },
		    "series": [';
		    	$counts = count($testTypes);

			    	foreach ($testTypes as $testType) {
		        		$options.= '{
		        			"name": "'.$testType->name.'","data": [';
		        				$counter = count($months);
		            			foreach ($months as $month) {
		            			$data = $testType->getPrevalenceCount($month->annum, $month->months);
		            				if($data->isEmpty()){
		            					$options.= '0.00';
		            					if($counter==1)
			            					$options.='';
			            				else
			            					$options.=',';
		            				}
		            				else{
		            					foreach ($data as $datum) {
				            				$options.= $datum->rate;

				            				if($counter==1)
				            					$options.='';
				            				else
				            					$options.=',';
					            		}
		            				}
		            			$counter--;
				    		}
				    		$options.=']';
				    	if($counts==1)
							$options.='}';
						else
							$options.='},';
						$counts--;
					}
			$options.='],
		    "xAxis": {
		        "categories": [';
		        $count = count($months);
	            	foreach ($months as $month) {
	    				$options.= '"'.$month->label." ".$month->annum;
	    				if($count==1)
	    					$options.='" ';
	    				else
	    					$options.='" ,';
	    				$count--;
	    			}
	            $options.=']
		    },
		    "yAxis": {
		        "title": {
		            "text": "'.trans('messages.prevalence-rates-label').'"
		        },
	            "min": "0",
	            "max": "100"
		    }
		}';
	return $options;
	}
	//	Begin count reports functions
	/**
	 * Display a test((un)grouped) and specimen((un)grouped) counts.
	 *
	 */
	public function countReports(){
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');
		$to = Input::get('end');
		if(!$to) $to = $date;
		$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
		$counts = Input::get('counts');
		$accredited = array();
		//	Begin grouped test counts
		if($counts==trans('messages.grouped-test-counts'))
		{
			$testCategories = TestCategory::all();
			$testTypes = TestType::all();
			$ageRanges = array('0-5', '5-15', '15-120');	//	Age ranges - will definitely change in configurations
			$gender = array(Patient::MALE, Patient::FEMALE); 	//	Array for gender - male/female

			$perAgeRange = array();	// array for counts data for each test type and age range
			$perTestType = array();	//	array for counts data per testype
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}
			foreach ($testTypes as $testType) {
				$countAll = $this->getGroupedTestCounts($testType, null, null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countMale = $this->getGroupedTestCounts($testType, [Patient::MALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countFemale = $this->getGroupedTestCounts($testType, [Patient::FEMALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$perTestType[$testType->id] = ['countAll'=>$countAll, 'countMale'=>$countMale, 'countFemale'=>$countFemale];
				foreach ($ageRanges as $ageRange) {
					$maleCount = $this->getGroupedTestCounts($testType, [Patient::MALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$femaleCount = $this->getGroupedTestCounts($testType, [Patient::FEMALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$perAgeRange[$testType->id][$ageRange] = ['male'=>$maleCount, 'female'=>$femaleCount];
				}
			}
			return View::make('reports.counts.groupedTestCount')
						->with('testCategories', $testCategories)
						->with('ageRanges', $ageRanges)
						->with('gender', $gender)
						->with('perTestType', $perTestType)
						->with('perAgeRange', $perAgeRange)
						->with('accredited', $accredited)
						->withInput(Input::all());
		}
		else if($counts==trans('messages.ungrouped-specimen-counts')){
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}

			$ungroupedSpecimen = array();
			foreach (SpecimenType::all() as $specimenType) {
				$rejected = $specimenType->countPerStatus([Specimen::REJECTED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$accepted = $specimenType->countPerStatus([Specimen::ACCEPTED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$total = $rejected+$accepted;
				$ungroupedSpecimen[$specimenType->id] = ["total"=>$total, "rejected"=>$rejected, "accepted"=>$accepted];
			}

			// $data = $data->groupBy('test_type_id')->paginate(Config::get('kblis.page-items'));
			return View::make('reports.counts.ungroupedSpecimenCount')
							->with('ungroupedSpecimen', $ungroupedSpecimen)
							->with('accredited', $accredited)
							->withInput(Input::all());

		}
		else if($counts==trans('messages.grouped-specimen-counts')){
			$ageRanges = array('0-5', '5-15', '15-120');	//	Age ranges - will definitely change in configurations
			$gender = array(Patient::MALE, Patient::FEMALE); 	//	Array for gender - male/female

			$perAgeRange = array();	// array for counts data for each test type and age range
			$perSpecimenType = array();	//	array for counts data per testype
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}
			$specimenTypes = SpecimenType::all();
			foreach ($specimenTypes as $specimenType) {
				$countAll = $specimenType->groupedSpecimenCount([Patient::MALE, Patient::FEMALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countMale = $specimenType->groupedSpecimenCount([Patient::MALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countFemale = $specimenType->groupedSpecimenCount([Patient::FEMALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$perSpecimenType[$specimenType->id] = ['countAll'=>$countAll, 'countMale'=>$countMale, 'countFemale'=>$countFemale];
				foreach ($ageRanges as $ageRange) {
					$maleCount = $specimenType->groupedSpecimenCount([Patient::MALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$femaleCount = $specimenType->groupedSpecimenCount([Patient::FEMALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$perAgeRange[$specimenType->id][$ageRange] = ['male'=>$maleCount, 'female'=>$femaleCount];
				}
			}
			return View::make('reports.counts.groupedSpecimenCount')
						->with('specimenTypes', $specimenTypes)
						->with('ageRanges', $ageRanges)
						->with('gender', $gender)
						->with('perSpecimenType', $perSpecimenType)
						->with('perAgeRange', $perAgeRange)
						->with('accredited', $accredited)
						->withInput(Input::all());
		}
		else{
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}

			$ungroupedTests = array();
			foreach (TestType::all() as $testType) {
				$pending = $testType->countPerStatus([Test::PENDING, Test::STARTED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$complete = $testType->countPerStatus([Test::COMPLETED, Test::VERIFIED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$ungroupedTests[$testType->id] = ["complete"=>$complete, "pending"=>$pending];
			}

			// $data = $data->groupBy('test_type_id')->paginate(Config::get('kblis.page-items'));
			return View::make('reports.counts.ungroupedTestCount')
							->with('ungroupedTests', $ungroupedTests)
							->with('accredited', $accredited)
							->withInput(Input::all());
		}
	}

	/*
	*	Begin turnaround time functions - functions related to the turnaround time report
	*	Most have been borrowed from the original BLIS by C4G
	*/
	/*
	* 	getPercentile() returns the percentile value from the given list
	*/
	public static function getPercentile($list, $ile_value)
	{
		$num_values = count($list);
		sort($list);
		$mark = ceil(round($ile_value/100, 2) * $num_values);
		return $list[$mark-1];
	}
	/*
	* 	week_to_date() returns timestamp for the first day of the week (Monday)
	*	@var $week_num and $year
	*/
	public static function week_to_date($week_num, $year)
	{
		# Returns timestamp for the first day of the week (Monday)
		$week = $week_num;
		$Jan1 = mktime (0, 0, 0, 1, 1, $year); //Midnight
		$iYearFirstWeekNum = (int) strftime("%W", $Jan1);
		if ($iYearFirstWeekNum == 1)
		{
			$week = $week - 1;
		}
		$weekdayJan1 = date ('w', $Jan1);
		$FirstMonday = strtotime(((4-$weekdayJan1)%7-3) . ' days', $Jan1);
		$CurrentMondayTS = strtotime(($week) . ' weeks', $FirstMonday);
		return ($CurrentMondayTS);
	}
	/*
	* 	rawTaT() returns list of timestamps for tests that were registered and handled between date_from and date_to
	*	optional @var $from, $to, $labSection, $testType
	*/
	public static function rawTaT($from, $to, $labSection, $testType){
		$rawTat = DB::table('tests')->select(DB::raw('UNIX_TIMESTAMP(time_created) as timeCreated, UNIX_TIMESTAMP(time_started) as timeStarted, UNIX_TIMESTAMP(time_entered) as timeCompleted, targetTAT'))->groupBy('tests.id')
						->join('test_types', 'test_types.id', '=', 'tests.test_type_id')
						->join('test_results', 'tests.id', '=', 'test_results.test_id')
						->whereIn('test_status_id', [Test::COMPLETED, Test::VERIFIED]);
						if($from && $to){
							$rawTat = $rawTat->whereBetween('time_created', [$from, $to]);
						}
						else{
							$rawTat = $rawTat->where('time_created', 'LIKE', '%'.date("Y").'%');
						}
						if($labSection){
							$rawTat = $rawTat->where('test_category_id', $labSection);
						}
						if($testType){
							$rawTat = $rawTat->where('test_type_id', $testType);
						}
		return $rawTat->get();
	}
	/*
	* 	getTatStats() calculates Weekly progression of TAT values for a given test type and time period
	*	optional @var $from, $to, $labSection, $testType, $interval
	*/
	public static function getTatStats($from, $to, $labSection, $testType, $interval){
		# Calculates Weekly progression of TAT values for a given test type and time period

		$resultset = self::rawTaT($from, $to, $labSection, $testType);
		# {resultentry_ts, specimen_id, date_collected_ts, ...}

		$progression_val = array();
		$progression_count = array();
		$percentile_tofind = 90;
		$percentile_count = array();
		$goal_val = array();
		# Return {month=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}

		if($interval == 'M'){
			foreach($resultset as $record)
			{
				$timeCreated = $record->timeCreated;
				$timeCreated_parsed = date("Y-m-d", $timeCreated);
				$timeCreated_parts = explode("-", $timeCreated_parsed);
				$month_ts = mktime(0, 0, 0, $timeCreated_parts[1], 0, $timeCreated_parts[0]);
				$month_ts_datetime = date("Y-m-d H:i:s", $month_ts);
				$wait_diff = ($record->timeStarted - $record->timeCreated); //Waiting time
				$date_diff = ($record->timeCompleted - $record->timeStarted); //Turnaround time

				if(!isset($progression_val[$month_ts]))
				{
					$progression_val[$month_ts] = array();
					$progression_val[$month_ts][0] = $date_diff;
					$progression_val[$month_ts][1] = $wait_diff;
					$progression_val[$month_ts][4] = array();
					$progression_val[$month_ts][4][] = $record;

					$percentile_count[$month_ts] = array();
					$percentile_count[$month_ts][] = $date_diff;

					$progression_count[$month_ts] = 1;

					if(!$record->targetTAT==null)
						$goal_tat[$month_ts] = $record->targetTAT; //Hours
					else
						$goal_tat[$month_ts] = 0.00; //Hours			
				}
				else
				{
					$progression_val[$month_ts][0] += $date_diff;
					$progression_val[$month_ts][1] += $wait_diff;
					$progression_val[$month_ts][4][] = $record;

					$percentile_count[$month_ts][] = $date_diff;

					$progression_count[$month_ts] += 1;
				}
			}

			foreach($progression_val as $key=>$value)
			{
				# Find average TAT
				$progression_val[$key][0] = $value[0]/$progression_count[$key];

				# Determine percentile value
				$progression_val[$key][3] = self::getPercentile($percentile_count[$key], $percentile_tofind);

				# Convert from sec timestamp to Hours
				$progression_val[$key][0] = ($value[0]/$progression_count[$key])/(60*60);//average TAT
				$progression_val[$key][1] = ($value[1]/$progression_count[$key])/(60*60);//average WT
				$progression_val[$key][3] = $progression_val[$key][3]/(60*60);// Percentile ???

				$progression_val[$key][2] = $goal_tat[$key];

			}
		}
		else if($interval == 'D'){
			foreach($resultset as $record)
			{
				$date_collected = $record->timeCreated;
				$day_ts = $date_collected; 
				$wait_diff = ($record->timeStarted - $record->timeCreated); //Waiting time
				$date_diff = ($record->timeCompleted - $record->timeStarted); //Turnaround time
				if(!isset($progression_val[$day_ts]))
				{
					$progression_val[$day_ts] = array();
					$progression_val[$day_ts][0] = $date_diff;
					$progression_val[$day_ts][1] = $wait_diff;
					$progression_val[$day_ts][4] = array();
					$progression_val[$day_ts][4][] = $record;

					$percentile_count[$day_ts] = array();
					$percentile_count[$day_ts][] = $date_diff;

					$progression_count[$day_ts] = 1;

					$goal_tat[$day_ts] = $record->targetTAT; //Hours
				}
				else
				{
					$progression_val[$day_ts][0] += $date_diff;
					$progression_val[$day_ts][1] += $wait_diff;
					$progression_val[$day_ts][4][] = $record;

					$percentile_count[$day_ts][] = $date_diff;

					$progression_count[$day_ts] += 1;
				}
			}

			foreach($progression_val as $key=>$value)
			{
				# Find average TAT
				$progression_val[$key][0] = $value[0]/$progression_count[$key];

				# Determine percentile value
				$progression_val[$key][3] = self::getPercentile($percentile_count[$key], $percentile_tofind);

				# Convert from sec timestamp to Hours
				$progression_val[$key][0] = ($value[0]/$progression_count[$key])/(60*60);//average TAT
				$progression_val[$key][1] = ($value[1]/$progression_count[$key])/(60*60);//average WT
				$progression_val[$key][3] = $progression_val[$key][3]/(60*60);// Percentile ???

				$progression_val[$key][2] = $goal_tat[$key];

			}
		}
		else{
			foreach($resultset as $record)
			{
				$date_collected = $record->timeCreated;
				$week_collected = date("W", $date_collected);
				$year_collected = date("Y", $date_collected);
				$week_ts = self::week_to_date($week_collected, $year_collected);
				$wait_diff = ($record->timeStarted - $record->timeCreated); //Waiting time
				$date_diff = ($record->timeCompleted - $record->timeStarted); //Turnaround time

				if(!isset($progression_val[$week_ts]))
				{
					$progression_val[$week_ts] = array();
					$progression_val[$week_ts][0] = $date_diff;
					$progression_val[$week_ts][1] = $wait_diff;
					$progression_val[$week_ts][4] = array();
					$progression_val[$week_ts][4][] = $record;

					$percentile_count[$week_ts] = array();
					$percentile_count[$week_ts][] = $date_diff;

					$progression_count[$week_ts] = 1;

					if(!$record->targetTAT==null)
						$goal_tat[$week_ts] = $record->targetTAT; //Hours
					else
						$goal_tat[$week_ts] = 0.00; //Hours				
				}
				else
				{
					$progression_val[$week_ts][0] += $date_diff;
					$progression_val[$week_ts][1] += $wait_diff;
					$progression_val[$week_ts][4][] = $record;

					$percentile_count[$week_ts][] = $date_diff;

					$progression_count[$week_ts] += 1;
				}
			}

			foreach($progression_val as $key=>$value)
			{
				# Find average TAT
				$progression_val[$key][0] = $value[0]/$progression_count[$key];

				# Determine percentile value
				$progression_val[$key][3] = self::getPercentile($percentile_count[$key], $percentile_tofind);

				# Convert from sec timestamp to Hours
				$progression_val[$key][0] = ($value[0]/$progression_count[$key])/(60*60);//average TAT
				$progression_val[$key][1] = ($value[1]/$progression_count[$key])/(60*60);//average WT
				$progression_val[$key][3] = $progression_val[$key][3]/(60*60);// Percentile ???

				$progression_val[$key][2] = $goal_tat[$key];

			}
		}
		# Return {month=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids], avg wait time]}
		return $progression_val;
	}

	/**
	 * turnaroundTime() function returns the turnaround time blade with necessary contents
	 *
	 * @return Response
	 */
	public function turnaroundTime()
	{
		$today = date('Y-m-d');
		$from = Input::get('start');
		$to = Input::get('end');
		if(!$to){
			$to=$today;
		}
		$testCategory = Input::get('section_id');
		$testType = Input::get('test_type');
		$labSections = TestCategory::lists('name', 'id');
		$interval = Input::get('period');
		$error = null;
		$accredited = array();
		if(!$testType)
			$error = trans('messages.select-test-type');
		if($testCategory)
			$testTypes = TestCategory::find($testCategory)->testTypes->lists('name', 'id');
		else
			$testTypes = array(""=>"");

		if($from||$to){
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($today)||strtotime($to)>strtotime($today)){
					$error = trans('messages.check-date-range');
			}
			else
			{
				$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
				Session::flash('fine', '');
			}
		}
		$resultset = self::getTatStats($from, $to, $testCategory, $testType, $interval);
		return View::make('reports.tat.index')
					->with('labSections', $labSections)
					->with('testTypes', $testTypes)
					->with('resultset', $resultset)
					->with('testCategory', $testCategory)
					->with('testType', $testType)
					->with('interval', $interval)
					->with('error', $error)
					->with('accredited', $accredited)
					->withInput(Input::all());
	}

	//	Begin infection reports functions
	/**
	 * Display a table containing all infection statistics.
	 *
	 */
	public function infectionReport(){

	 	$ageRanges = array('0-5'=>'Under 5 years', 
	 					'5-14'=>'5 years and over but under 14 years', 
	 					'14-120'=>'14 years and above');	//	Age ranges - will definitely change in configurations
		$gender = array(Patient::MALE, Patient::FEMALE); 	//	Array for gender - male/female
		$ranges = array('Low', 'Normal', 'High');
		$accredited = array();

		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');

		$to = Input::get('end');
		if(!$to) $to = $date;
		
		$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));

		$testCategory = Input::get('test_category');

		$infectionData = Test::getInfectionData($from, $toPlusOne, $testCategory);	// array for counts data for each test type and age range
		
		return View::make('reports.infection.index')
					->with('gender', $gender)
					->with('ageRanges', $ageRanges)
					->with('ranges', $ranges)
					->with('infectionData', $infectionData)
					->with('accredited', $accredited)
					->withInput(Input::all());
	}

	/**
	 * Displays summary statistics on users application usage.
	 *
	 */
	public function userStatistics(){

		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');

		$to = Input::get('end');
		if(!$to) $to = $date;
		
		$selectedUser = Input::get('user');
		if(!$selectedUser)$selectedUser = "";
		else $selectedUser = " USER: ".User::find($selectedUser)->name;

		$reportTypes = array('Summary', 'Patient Registry', 'Specimen Registry', 'Tests Registry', 'Tests Performed');

		$selectedReport = Input::get('report_type');
		if(!$selectedReport)$selectedReport = 0;

		switch ($selectedReport) {
			case '1':
				$reportData = User::getPatientsRegistered($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-patients-register-report-title',1);
				break;
			case '2':
				$reportData = User::getSpecimensRegistered($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-specimens-register-report-title',1);
				break;
			case '3':
				$reportData = User::getTestsRegistered($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-tests-register-report-title',1);
				break;
			case '4':
				$reportData = User::getTestsPerformed($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-tests-performed-report-title',1);
				break;
			default:
				$reportData = User::getSummaryUserStatistics($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-summary-report-title',1);
				break;
		}

		$reportTitle = str_replace("[FROM]", $from, $reportTitle);
		$reportTitle = str_replace("[TO]", $to, $reportTitle);
		$reportTitle = str_replace("[USER]", $selectedUser, $reportTitle);
		
		return View::make('reports.userstatistics.index')
					->with('reportTypes', $reportTypes)
					->with('reportData', $reportData)
					->with('reportTitle', $reportTitle)
					->with('selectedReport', $selectedReport)
					->withInput(Input::all());
	}

	/**
	* Returns qc index page
	*
	* @return view
	*/
	public function qualityControl()
	{
		$accredited = array();
		$controls = Control::all()->lists('name', 'id');
		$accredited = array();
		$tests = array();
		return View::make('reports.qualitycontrol.index')
			->with('accredited', $accredited)
			->with('tests', $tests)
			->with('controls', $controls);
	}

	/**
	* Returns qc results for a specific control page
	*
	* @param Input - controlId, date range
	* @return view
	*/
	public function qualityControlResults()
	{
		$rules = array('start_date' => 'date|required',
					'end_date' => 'date|required',
					'control' => 'required');
		$validator = Validator::make(Input::all(), $rules);
		$accredited = array();
		if($validator->fails()){
			return Redirect::back()->withErrors($validator)->withInput();
		}
		else {
			$controlId = Input::get('control');
			$endDatePlusOne = date_add(new DateTime(Input::get('end_date')), date_interval_create_from_date_string('1 day'));
			$dates= array(Input::get('start_date'), $endDatePlusOne);
			$control = Control::find($controlId);
			$controlTests = ControlTest::where('control_id', '=', $controlId)
										->whereBetween('created_at', $dates)->get();
			$leveyJennings = $this->leveyJennings($control, $dates);
			return View::make('reports.qualitycontrol.results')
				->with('control', $control)
				->with('controlTests', $controlTests)
				->with('leveyJennings', $leveyJennings)
				->with('accredited', $accredited)
				->withInput(Input::all());
		}
	}

	/**
	 * Displays Surveillance
	 * @param string $from, string $to, array() $testTypeIds
	 * As of now surveillance works only with alphanumeric measures
	 */
	public function surveillance(){
		/*surveillance diseases*/
		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');
		$to = Input::get('end');
		if(!$to) $to = $date;
		$accredited = array();

		$surveillance = Test::getSurveillanceData($from, $to.' 23:59:59');
		$accredited = array();
		$tests = array();

		if(Input::has('word')){
			$fileName = "surveillance_".$date.".doc";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.surveillance.exportSurveillance')
							->with('surveillance', $surveillance)
							->with('tests', $tests)
							->with('accredited', $accredited)
							->withInput(Input::all());
			return Response::make($content,200, $headers);
		}else if(Input::has('excel')){
			$fileName = "surveillance_".$date.".xls";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.surveillance.exportSurveillance')
							->with('surveillance', $surveillance)
							->with('tests', $tests)
							->with('accredited', $accredited)
							->withInput(Input::all());
			return Response::make($content,200, $headers);
		}else{
			return View::make('reports.surveillance.index')
					->with('accredited', $accredited)
					->with('tests', $tests)
					->with('surveillance', $surveillance)
					->with('accredited', $accredited)
					->withInput(Input::all());
		}
	}

	/**
	 * Manage Surveillance Configurations
	 * @param
	 */
	public function surveillanceConfig(){
		
        $allSurveillanceIds = array();
		
		//edit or leave surveillance entries as is
		if (Input::get('surveillance')) {
			$diseases = Input::get('surveillance');

			foreach ($diseases as $id => $disease) {
                $allSurveillanceIds[] = $id;
				$surveillance = ReportDisease::find($id);
				$surveillance->test_type_id = $disease['test-type'];
				$surveillance->disease_id = $disease['disease'];
				$surveillance->save();
			}
		}
		
		//save new surveillance entries
		if (Input::get('new-surveillance')) {
			$diseases = Input::get('new-surveillance');

			foreach ($diseases as $id => $disease) {
				$surveillance = new ReportDisease;
				$surveillance->test_type_id = $disease['test-type'];
				$surveillance->disease_id = $disease['disease'];
				$surveillance->save();
                $allSurveillanceIds[] = $surveillance->id;
				
			}
		}

        //check if action is from a form submission
        if (Input::get('from-form')) {
	     	// Delete any pre-existing surveillance entries
	     	//that were not captured in any of the above save loops
	        $allSurveillances = ReportDisease::all(array('id'));

	        $deleteSurveillances = array();

	        //Identify survillance entries to be deleted by Ids
	        foreach ($allSurveillances as $key => $value) {
	            if (!in_array($value->id, $allSurveillanceIds)) {
	                $deleteSurveillances[] = $value->id;
	            }
	        }
	        //Delete Surveillance entry if any
	        if(count($deleteSurveillances)>0)ReportDisease::destroy($deleteSurveillances);
        }

		$diseaseTests = ReportDisease::all();

		return View::make('reportconfig.surveillance')
					->with('diseaseTests', $diseaseTests);
	}

	/**
	* Function to check object state before groupedTestCount
	**/
	public function getGroupedTestCounts($ttypeob, $gender=null, $ageRange=null, $from=null, $to=null)
	{
            if($ttypeob == null){
                    return 0;
            }
            return $ttypeob->groupedTestCount($gender, $ageRange, $from, $to);
	}
        /** ZEEK
	* Function to check object state before getReferredSpecimenCount
	**/
	public function getReferredSpecimenCount($ttypeob, $id, $from=null, $to=null)
	{
            if($ttypeob == null){
                    return 0;
            }
            return $ttypeob->getRefferedCount($ttypeob->id, $from, $to);
	}
        /** ZEEK
	* Function to check object state before reffered results received count
	**/
	public function getReferredSpecimenReceivedResult($ttypeob, $id, $from=null, $to=null)
	{
            if($ttypeob == null){
                    return 0;
            }
            return $ttypeob->getRefferedResultRecievedCount($ttypeob->id, $from, $to);
	}
	/**
	* Function to check object state before totalTestResults
	**/
	public function getTotalTestResults($measureobj, $gender=null, $ageRange=null, $from=null, $to=null, $range=null, $positive=null){
		if($measureobj == null){
			return 0;
		}
		return $measureobj->totalTestResults($gender, $ageRange, $from, $to, $range, $positive);
	}
        public function histologyCytologySerology($testArr,$from, $toPlusOne)//sub routine to process histology and cytology. The test sub sections repeat similarly
            {
                    /* Fine Needles Aspirates */
                $testsList = array();
                foreach($testArr as $ts)
                {
                    $positive = 0;
                    $testsId = TestType::getTestTypeIdByTestName($ts);                   
                    $aspirates = TestType::find($testsId);
                    $measures = TestTypeMeasure::where('test_type_id', $testsId)->orderBy('measure_id', 'DESC')->get();
                    $arr['name'] = $ts;
                    $arr['total'] = $this->getGroupedTestCounts($aspirates, null, null, $from, $toPlusOne);
                    /* get measures that were positive at a given age range */
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        //$arr['name'] = $tMeasure->name;
                        //$arr['total'] = $this->getGroupedTestCounts($aspirates, null, null, $from, $toPlusOne);
                        //$arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                        $positive += $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                    }
                    $arr['positive'] = $positive;
                    array_push($testsList, $arr);
                }
                return $testsList;
            }
        /**
	 * MOH 706
	 *
	 */
	public function moh706(){
		//	Variables definition
                $moh706List = array();
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');
		$end = Input::get('end');
		if(!$end) $end = $date;
		$toPlusOne = date_add(new DateTime($end), date_interval_create_from_date_string('1 day'));
		$to = date_add(new DateTime($end), date_interval_create_from_date_string('1 day'))->format('Y-m-d');
		//$ageRanges = array('0-5', '5-14', '14-120');
                $ageRanges = array('0-5', '5-120');
		$sex = array(Patient::MALE, Patient::FEMALE);
		$ranges = array('Low', 'Normal', 'High');
		$specimen_types = array('Urine', 'Pus', 'HVS', 'Throat', 'Stool', 'Blood', 'CSF', 'Water', 'Food', 'Other fluids');
		$isolates = array('Naisseria', 'Klebsiella', 'Staphylococci', 'Streptoccoci'. 'Proteus', 'Shigella', 'Salmonella', 'V. cholera', 
						  'E. coli', 'C. neoformans', 'Cardinella vaginalis', 'Haemophilus', 'Bordotella pertusis', 'Pseudomonas', 
						  'Coliforms', 'Faecal coliforms', 'Enterococcus faecalis', 'Total viable counts-22C', 'Total viable counts-37C', 
						  'Clostridium', 'Others');
                
		//	Get specimen_types for microbiology
		$labSecId = TestCategory::getTestCatIdByName('Microbiology');
		$specTypeIds = DB::select(DB::raw("select distinct(specimen_types.id) as spec_id from testtype_specimentypes".
										  " join test_types on test_types.id=testtype_specimentypes.test_type_id".
										  " join specimen_types on testtype_specimentypes.specimen_type_id=specimen_types.id".
										  "  where test_types.test_category_id=?"), array($labSecId));

		//	Referred out specimen
		$referredSpecimens = DB::select(DB::raw("SELECT specimen_type_id, specimen_types.name as spec, count(specimens.id) as tot,".
												" facility_id, facilities.name as facility FROM iblis.specimens".
												" join referrals on specimens.referral_id=referrals.id".
												" join specimen_types on specimen_type_id=specimen_types.id".
												" join facilities on referrals.facility_id=facilities.id".
												" where referral_id is not null and status=1".
												" and time_accepted between ? and ?".
												" group by facility_id;"), array($from, $toPlusOne));
                /*--URINALYSIS--*/
                
                $urinaId = TestType::getTestTypeIdByTestName('Urinalysis');
                $urinalysis = TestType::find($urinaId);
                $table = '';//XXX zeek delete
                
                
                /* Urine Chemistry*/
                $measures = TestTypeMeasure::where('test_type_id', $urinaId)->orderBy('measure_id', 'DESC')->get();
                $urineChemistryList = array();
                $urineChemestryTotalExam = $this->getGroupedTestCounts($urinalysis, null, null, $from, $toPlusOne);
                /* get measures that were positive */
                foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        if(!in_array($tMeasure->name, ['Glucose', 'Ketones', 'Proteins'])){continue;}//add measures to be listed the report in the array
                        $arr['name'] = $tMeasure->name;
                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, 'Positive');
                        array_push($urineChemistryList, $arr);
                }
                $moh706List['urineChemistryList'] = $urineChemistryList;
                $moh706List['urineChemestryTotalExam'] = $urineChemestryTotalExam;
				
                /*Urine Microscopy*/
                $measures = TestTypeMeasure::where('test_type_id', $urinaId)->orderBy('measure_id', 'DESC')->get();
                $urineMicroscopyList = array();
                $urineMicroscopyTotalExam = $this->getGroupedTestCounts($urinalysis, null, null, $from, $toPlusOne);
                /* get measures that were positive */
                foreach ($measures as $measure) {
                $tMeasure = Measure::find($measure->measure_id);
                if(!in_array($tMeasure->name, ['Pus cells', 'Schistosoma haematobium', 'Trichomona Vaginalis', 'Yeast cells', 'Bacteria'])){continue;}//add measures to be listed the report in the array
                $arr['name'] = $tMeasure->name;
                $arr['positive'] =$tMeasure->name =='Pus cells' ? $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null) : $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                array_push($urineMicroscopyList, $arr);
                }
                $moh706List['urineMicroscopyList'] = $urineMicroscopyList;
                $moh706List['urineMicroscopyTotalExam'] = $urineMicroscopyTotalExam;
                
                /*===============*/
                /*BLOOD CHEMISTRY*/
                /*===============*/
                
                /* blood sugar test */
                $bloodSugarTestArr = array ('Random Blood sugar', 'Fasting Blood sugar', 'OGTT'); 
                $bloodSugarTestList = array();
                foreach($bloodSugarTestArr as $bst) {
                    $bloodSugar = TestType::getTestTypeIdByTestName($bst);
                    //$bloodSugarObj = TestType::find($bloodChem);//get the testtype object
                    $measures = TestTypeMeasure::where('test_type_id', $bloodSugar)->orderBy('measure_id', 'DESC')->get();  
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name;
                        $arr['total'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                        $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low'], null);
                        $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
                        array_push($bloodSugarTestList, $arr);
                    }
                }
                $moh706List['bloodSugarTestList'] = $bloodSugarTestList;
                
		/*renal function test*/
                $renalFunctionTestArr = array ('Creatinine', 'Urea', 'Sodium', 'Potassium', 'Chloride'); //renal function test
                $renalFunctionTestList = array();
                $renalFunctionTestTotalExam = 0;
                foreach($renalFunctionTestArr as $rft) {
                    $renalFunction = TestType::getTestTypeIdByTestName($rft);
                    $renalFunctionObj = TestType::find($renalFunction);//get the testtype object
                    $renalFunctionTestTotalExam +=  $this->getGroupedTestCounts($renalFunctionObj, null, null, $from, $toPlusOne); //sum total test count
                    $measures = TestTypeMeasure::where('test_type_id', $renalFunction)->orderBy('measure_id', 'DESC')->get();  
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name;
                        //$arr['total'] = $this->getGroupedTestCounts($renalFunctionObj, null, null, $from, $toPlusOne);
                        $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low'], null);
                        $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
                        array_push($renalFunctionTestList, $arr);
                    }
                }
                $moh706List['renalFunctionTestList'] = $renalFunctionTestList;
                $moh706List['renalFunctionTestTotalExam'] = $renalFunctionTestTotalExam;
                
                /*Liver function test*/
                $liverFunctionTestArr = array ('Direct Bilirubin', 'Total Bilirubin', 'SGOT/AST', 'SGPT/ALT', 'Total Protein', 'Albumin', 'Alkaline Phosphatase'); //liver function test
                $liverFunctionTestList = array();
                $liverFunctionTestTotalExam = 0;
                foreach($liverFunctionTestArr as $rft) {
                    $liverFunction = TestType::getTestTypeIdByTestName($rft);
                    $liverFunctionObj = TestType::find($liverFunction);//get the testtype object
                    $liverFunctionTestTotalExam +=  $this->getGroupedTestCounts($liverFunctionObj, null, null, $from, $toPlusOne); //sum total test count
                    $measures = TestTypeMeasure::where('test_type_id', $liverFunction)->orderBy('measure_id', 'DESC')->get();  
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name;
                        //$arr['total'] = $this->getGroupedTestCounts($liverFunctionObj, null, null, $from, $toPlusOne);
                        $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low'], null);
                        $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
                        array_push($liverFunctionTestList, $arr);
                    }
                }
		$moh706List['liverFunctionTestList'] = $liverFunctionTestList;
                $moh706List['liverFunctionTestTotalExam'] = $liverFunctionTestTotalExam;
                
                /*Lipid profile*/
                $lipidProfileArr = array ('Total cholestrol', 'Triglycerides', 'LDL'); //lipid profile
                $lipidProfileList = array();
                $lipidProfileTotalExam = 0;
                foreach($lipidProfileArr as $rft) {
                    $lipidProfile = TestType::getTestTypeIdByTestName($rft);
                    $lipidProfileObj = TestType::find($lipidProfile);//get the testtype object
                    $lipidProfileTotalExam +=  $this->getGroupedTestCounts($lipidProfileObj, null, null, $from, $toPlusOne); //sum total test count
                    $measures = TestTypeMeasure::where('test_type_id', $lipidProfile)->orderBy('measure_id', 'DESC')->get();  
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name;
                        $arr['total'] = $this->getGroupedTestCounts($lipidProfileObj, null, null, $from, $toPlusOne);
                        $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low'], null);
                        $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
                        array_push($lipidProfileList, $arr);
                    }
                }
                $moh706List['lipidProfileList'] = $lipidProfileList;
                $moh706List['lipidProfileTotalExam'] = $lipidProfileTotalExam;
                
                /* Hormonal test */
                $hormonalTestArr = array ('Thyroid function tests', 'PSA screening');
                $hormonalTestList = array();
                foreach ($hormonalTestArr as $hrmnl ) {
                	$hormonal = TestType::getTestTypeIdByTestName($hrmnl);
	                $measures = TestTypeMeasure::where('test_type_id', $hormonal)->orderBy('measure_id', 'DESC')->get();  
	                foreach ($measures as $measure) {
	                    $tMeasure = Measure::find($measure->measure_id);//get the testtype object
	                    if(!in_array($tMeasure->name, ['Thyroid stimulating hormone (TSH)', 'Thyroxine (T4)', 'Triiodothyromine (T3)', 'PSA screening' ])){continue;}//add measures to be listed the report in the array
	                    $arr['name'] = $tMeasure->name;
	                    $arr['total'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
	                    $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low'], null);
	                    $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
	                    array_push($hormonalTestList, $arr);
	                }
                }
                
                $moh706List['hormonalTestList'] = $hormonalTestList;
                
                /* Tumor Markers*/
                
                $tumorMarkersArr = array ('Carcinoembryonic antigen test (CEA)', 'Cancer antigen 15-3 (C15-3)');
                $tumorMarkersList = array();
                foreach($tumorMarkersArr as $tm) { 
                    $tumorMarkersId = TestType::getTestTypeIdByTestName($tm);
                    $tumorMarkers = TestType::find($tumorMarkersId);
                    $measures = TestTypeMeasure::where('test_type_id', $tumorMarkersId)->orderBy('measure_id', 'DESC')->get();
                    /* get measures that were positive */
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name;
                        $arr['total'] = $this->getGroupedTestCounts($tumorMarkers, null, null, $from, $toPlusOne);
                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                        array_push($tumorMarkersList, $arr);
                    }
                }
                $moh706List['tumorMarkersList'] = $tumorMarkersList;
                
                /*CSF Chemistry*/
                $csfChemistryArr = array ('CSF glucose analysis', 'CSF protein analysis'); //lipid profile
                $csfChemistryList = array();
                foreach($csfChemistryArr as $csfc) {
                    $csfChemistry = TestType::getTestTypeIdByTestName($csfc);
                    $csfChemistryObj = TestType::find($csfChemistry);//get the testtype object
                    $measures = TestTypeMeasure::where('test_type_id', $csfChemistry)->orderBy('measure_id', 'DESC')->get();  
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name=='CSF glucose analysis' ? 'Glucose' : 'Proteins';
                        $arr['total'] = $this->getGroupedTestCounts($csfChemistryObj, null, null, $from, $toPlusOne);
                        $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low'], null);
                        $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
                        array_push($csfChemistryList, $arr);
                    }
                }
                $moh706List['csfChemistryList'] = $csfChemistryList;
                
                /*===============*/
                /* PARASITOLOGY */
                /*===============*/
                
                /* Malaria Test*/
                $malariaTestArr = array ('Blood slide for Malaria', 'Malaria Rapid Diagnostic Test'); 
                $malariaTestList = array();
                foreach($malariaTestArr as $mlra){
                	$malariaTestId = TestType::getTestTypeIdByTestName($mlra);
                	$malariaTest = TestType::find($malariaTestId);
                	$measures = TestTypeMeasure::where('test_type_id', $malariaTestId)->orderBy('measure_id', 'DESC')->get();
                	if ($mlra=='Malaria Rapid Diagnostic Test') {
	                		$arr['name'] = "Malaria Rapid Diagnostic Test";
	                		foreach ($measures as $measure) {
	                        $tMeasure = Measure::find($measure->measure_id);
	                        $arr['total'] = $this->getGroupedTestCounts($malariaTest, null, null, $from, $toPlusOne);
	                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, "Positive");
	                        array_push($malariaTestList, $arr);
	                    	}
	                	}
                	 /* get measures that were positive at a given age range for Blood slide for Malaria */
	                else {
	                	foreach ($ageRanges as $ageRange) {
    	                	if ($mlra=='Blood slide for Malaria') {
    	                		if($ageRange == '0-5'){ $arr['name'] = "Malaria BS (Under five years)"; }
    	                    	else { $arr['name'] = "Malaria BS (5 Years and above)"; }
    	                	}
    	                    
    	                    foreach ($measures as $measure) {
    	                        $tMeasure = Measure::find($measure->measure_id);
    	                        $arr['total'] = $this->getGroupedTestCounts($malariaTest, null, $ageRange, $from, $toPlusOne);
    	                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, $ageRange, $from, $toPlusOne, null, "Positive");
    	                        array_push($malariaTestList, $arr);
    	                    }
	                	                    
    	                }
    	            }
                }
                  
                $moh706List['malariaTestList'] = $malariaTestList;
                                
                /* Stool Anlaysis */
                
                $stoolAnalysisId = TestType::getTestTypeIdByTestName('Stool analysis');
                $stoolAnalysis = TestType::find($stoolAnalysisId);
                $measures = TestTypeMeasure::where('test_type_id', $stoolAnalysisId)->orderBy('measure_id', 'DESC')->get();
                $stoolAnalysisList = array();
                $stoolAnalysisTotalExam = $this->getGroupedTestCounts($stoolAnalysis, null, null, $from, $toPlusOne);
                /*get measures that were positive*/
                foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        if(!in_array($tMeasure->name, [ 'Taenia spp.', 'H. nana', 'H. diminuta', 'Hookworm', 'Roundworms', 'S. mansoni', 'Trichuris trichiura', 'Entamoeba hystolytica'])){continue;}//add measures to be listed the report in the array
                        $arr['name'] = $tMeasure->name == 'Entamoeba hystolytica' ? 'Amoeba' : $tMeasure->name;
                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, true);
                        array_push($stoolAnalysisList, $arr);
                }
                $moh706List['stoolAnalysisList'] = $stoolAnalysisList;
                $moh706List['stoolAnalysisTotalExam'] = $stoolAnalysisTotalExam;
                
                /*===============*/
                /* HAEMATOLOGY */
                /*===============*/
                
                /* Haematology Test*/
                
                $haematologyTestArr = array ('Full haemogram/Full blood count', 'Haemoglobin', 'CD4 count'); //haemotology tests
                $haematologyTestList = array();
                $CD4_FLAG = "CD4 count";
                foreach($haematologyTestArr as $ht) {
                    $arr['name'] = $ht == 'Haemoglobin' ? 'HB estimation tests' : $ht;//test name
                    $haematologyTestId = TestType::getTestTypeIdByTestName($ht);
                    $haematologyTestObj = TestType::find($haematologyTestId);//get the testtype object
                    $measures = TestTypeMeasure::where('test_type_id', $haematologyTestId)->orderBy('measure_id', 'DESC')->get();  
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        if(!in_array($tMeasure->name, ['Haemoglobin', 'CD4 count'])){continue;}
                        $arr['total'] = $this->getGroupedTestCounts($haematologyTestObj, null, null, $from, $toPlusOne);
                        //for CD4 count
                        if($tMeasure->name == 'CD4 count')
                        {
                            $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low', 500], null);
                        }else
                        {
                            $arr['low'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['Low', 5], null);
                        }
                        $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High', 5, 10], null);
                        array_push($haematologyTestList, $arr);
                    }
                }
                $moh706List['haematologyTestList'] = $haematologyTestList;
                $moh706List['CD4_FLAG'] = $CD4_FLAG;
                
                /* Other Haematology Tests*/
                
                $otherHaematologyTestsArr = array ('Peripheral Blood films', 'Sickling test', 
                    'Bone marrow aspirates', 'Reticulocyte count', 'Coagulation Profile', 'Erythrocyte sedimentation rate (ESR)');
                $otherHaematologyTestsList = array();
                $ESR_FLAG = "Erythrocyte sedimentation rate (ESR)";
                foreach($otherHaematologyTestsArr as $oht) { 
                    $otherHaematologyTestsId = TestType::getTestTypeIdByTestName($oht);
                    $otherHaematologyTests = TestType::find($otherHaematologyTestsId);
                    $measures = TestTypeMeasure::where('test_type_id', $otherHaematologyTestsId)->orderBy('measure_id', 'DESC')->get();
                    $CoagProfName = 'Coagulation Profile';
                    $CoagProfAggregateTotal = 0;
                    $CoagProfAggregatePositive = 0;
                    $CoagProfAggregateHigh = 0;

                    /* get measures that were positive */
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        if ($oht == $CoagProfName) {
                        	$CoagProfAggregateTotal += $this->getGroupedTestCounts($otherHaematologyTests, null, null, $from, $toPlusOne);
                        	$CoagProfAggregatePositive += $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                        	$CoagProfAggregateHigh += $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
                        }
                        else{
                        	$arr['name'] = $tMeasure->name;
	                        $arr['total'] = $this->getGroupedTestCounts($otherHaematologyTests, null, null, $from, $toPlusOne);
	                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
	                        $arr['high'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, ['High'], null);
	                        array_push($otherHaematologyTestsList, $arr);
                        }
                        
                    }
                    if ($oht == $CoagProfName) {
                    	$arr['name'] = $CoagProfName;
                    	$arr['total'] = $CoagProfAggregateTotal;
                    	$arr['positive'] = $CoagProfAggregatePositive;
                    	$arr['high'] = $CoagProfAggregateHigh;
                    	array_push($otherHaematologyTestsList, $arr);
                    }
                }
                $moh706List['otherHaematologyTestsList'] = $otherHaematologyTestsList;
                $moh706List['ESR_FLAG'] = $ESR_FLAG;
                
                /* Blood Grouping */
                
                $bloodGroupingArr = array ('Blood grouping', 'Blood unit grouped', 'Blood units received from blood trasnfusion centres',
                    'Blood units collected at facility', 'Blood units trasnfused', 'Transfusion reactions reported and investigated', 
                    'Blood crossed matched', 'Blood units discarded');
                $bloodGroupingList = array();
                $BG_FLAG = 'Blood units received from blood trasnfusion centres';
                foreach($bloodGroupingArr as $bg) { 
                    $bloodGroupingId = TestType::getTestTypeIdByTestName($bg);
                    $bloodGrouping = TestType::find($bloodGroupingId);
                    /* get measures that were positive */
                    $arr['name'] = $bg;
                    if($bg == 'Blood grouping')
                    { $arr['total'] = $this->getGroupedTestCounts($bloodGrouping, null, null, $from, $toPlusOne); }
                    else { $arr['total'] = null;}
                    array_push($bloodGroupingList, $arr);
                }
                $moh706List['bloodGroupingList'] = $bloodGroupingList;
                $moh706List['BG_FLAG'] = $BG_FLAG;
                
                 /* Blood screening at facility*/
                
                $bloodScreeningArr = array ('Hepatitis C test', 'Hepatitis B test (HBs Ag)', 'HIV ELISA', 'Rapid Plasma Reagin (RPR)/VDRL');
                $bloodScreeningList = array();
                foreach($bloodScreeningArr as $bsf) { 
                    $bloodScreeningId = TestType::getTestTypeIdByTestName($bsf);
                    $bloodScreening = TestType::find($bloodScreeningId);
                    $measures = TestTypeMeasure::where('test_type_id', $bloodScreeningId)->orderBy('measure_id', 'DESC')->get();
                    /* get measures that were positive */
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name == 'Rapid Plasma Reagin (RPR)/VDRL' ? 'Syphilis' : $tMeasure->name;
                        $arr['total'] = $this->getGroupedTestCounts($bloodScreening, null, null, $from, $toPlusOne);
                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                        array_push($bloodScreeningList, $arr);
                    }
                }
                $moh706List['bloodScreeningList'] = $bloodScreeningList;
                
                /*===============*/
                /* BACTERIOLOGY */
                /*===============*/
                
                /* Bacteriological sample*/
                $bacterolgicalSampleList = array();
                $sampleTypesList = array('Urine', 'Pus Swab', 'High vaginal swab', 'Throat Swab', 'Rectal swab', 
                            'Whole blood', 'Water Samples', 'Food samples', 'Urethral swab');
                $positiveCount = 0;
                foreach ($specTypeIds as $key) 
                {
                    $specimenType = SpecimenType::find($key->spec_id)->name;
                    if(!in_array($specimenType, $sampleTypesList)){
                            continue;
                    }
                    if($specimenType == "High vaginal swab") {
                        $specimenType = $specimenType." (HVS) "; //conform with the testtype name
                    }
                    $cultureId = TestType::getTestTypeIdByTestName($specimenType.' Culture');
                    $culture = TestType::find($cultureId);
                    $measures = TestTypeMeasure::where('test_type_id', $cultureId)->orderBy('measure_id', 'DESC')->get();
                    //number of culture positive for every bacteriological sample
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $positiveCount =+ $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);//aggregate positive cultures' measure
                    }

                    $totalCount = DB::select(DB::raw("select count(specimen_id) as per_spec_count from tests".
                                                                                     " join specimens on tests.specimen_id=specimens.id".
                                                                                     " join test_types on tests.test_type_id=test_types.id".
                                                                                     " where specimens.specimen_type_id=?".
                                                                                     " and test_types.test_category_id=?".
                                                                                     " and test_status_id in(?,?)".
                                                                                     " and tests.time_created BETWEEN ? and ?;"), 
                                                                                    [$key->spec_id, $labSecId, Test::COMPLETED, Test::VERIFIED, $from, $toPlusOne]);
                    $arr['name'] = $specimenType;
                    $arr['count'] = $totalCount[0]->per_spec_count;
                    $arr['total'] = $this->getGroupedTestCounts($culture, null, null, $from, $toPlusOne);//total cultures for every bacteriological sample
                    $arr['positive'] = $positiveCount;
                    array_push($bacterolgicalSampleList, $arr);
                }
                $moh706List['bacterolgicalSampleList'] = $bacterolgicalSampleList;
                    
                /* Stool culture */

                $stoolCultureList = array();
                $stoolCultureId = TestType::getTestTypeIdByTestName("Stool culture");
                $stoolCulture = TestType::find($stoolCultureId);
                $measures = TestTypeMeasure::where('test_type_id', $stoolCultureId)->orderBy('measure_id', 'DESC')->get();
                /* get measures that were positive */
                foreach ($measures as $measure) {
                    $tMeasure = Measure::find($measure->measure_id);
                    $arr['name'] = $tMeasure->name;
                    $arr['total'] = $this->getGroupedTestCounts($stoolCulture, null, null, $from, $toPlusOne);
                    $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                    array_push($stoolCultureList, $arr);
                }
                $moh706List['stoolCultureList'] = $stoolCultureList;

                /* Stool Isolates */
                $stoolIsolateList = array();
                $stoolIsolates = ['Salmonella species','Shigella', 'Escherichia coli', 'Vibrio cholerae' ];
                foreach ($stoolIsolates as $isolate)
                {
                    $arr['name'] = $isolate;
                    foreach ($sampleTypesList as $sampleType) 
                    {
                        $arr['positive'] = TestResult::microCounts($isolate,$sampleType, $from, $toPlusOne)[0]->total;
                    } 
                    array_push($stoolIsolateList, $arr);
                }
                $moh706List['stoolIsolateList'] = $stoolIsolateList;
                
                /* Bacterial Meningitis*/
                
                $bacterialMeningitisArr = array ('CSF culture', 'Neisseria meningitidis A', 'Neisseria meningitidis B', 'Neisseria meningitidis C',
                                        'Neisseria meningitidis W135', 'Neisseria meningitidis X', 'Neisseria meningitidis Y', 
                                        'Neisseria meningitidis (indeterminate)', 'Streptococcus pneumoniae','Haemophilus influenzae (type b)',
                                        'Cryptococcal Meningitis', 'B. anthracis', 'Y. pestis');
                $CSF_FLAG = "CSF culture";
                $BP_FLAG = "B. anthracis";
                $YP_FLAG = "Y. pestis";
                $bacterialMeningitisList = array();
                foreach($bacterialMeningitisArr as $bm) { 
                    if ($bm == $CSF_FLAG){
                    	$bacterialMeningitisId = TestType::getTestTypeIdByTestName($bm);
                        $bacterialMeningitis = TestType::find($bacterialMeningitisId);
                        $measures = TestTypeMeasure::where('test_type_id', $bacterialMeningitisId)->orderBy('measure_id', 'DESC')->get();
                        /* get measures that were positive */
                        foreach ($measures as $measure) {
                            $tMeasure = Measure::find($measure->measure_id);
                            $arr['name'] = $tMeasure->name;
                            
                            $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                            if($tMeasure->name == 'CSF culture')
                            {
                                $arr['total'] = $this->getGroupedTestCounts($bacterialMeningitis, null, null, $from, $toPlusOne);
                                $arr['contaminated'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                            }                       
                            array_push($bacterialMeningitisList, $arr);
                        }
                    }
                    else{
                    	$arr['name'] = $bm;
	                    foreach ($sampleTypesList as $sampleType) 
	                    {
	                        $arr['positive'] = TestResult::microCounts($bm,$sampleType, $from, $toPlusOne)[0]->total;
	                    } 
	                    array_push($bacterialMeningitisList, $arr);
                    }
                }
                $moh706List['CSF_FLAG'] = $CSF_FLAG;
                $moh706List['BP_FLAG'] = $BP_FLAG;
                $moh706List['YP_FLAG'] = $YP_FLAG;
                $moh706List['bacterialMeningitisList'] = $bacterialMeningitisList;
                
                /* Sputum*/
                
                $sputumArr = array('Sputum for AFB', 'TB new suspects', 'TB followup', 'GeneXpert', 'MDR TB');
                $sputumList = array();
                foreach($sputumArr as $sp)
                {
                    $sputumId = TestType::getTestTypeIdByTestName($sp);
                    $sputum = TestType::find($sputumId);
                    $measures = TestTypeMeasure::where('test_type_id', $sputumId)->orderBy('measure_id', 'DESC')->get();
                    /* get measures that were positive */
                    foreach ($measures as $measure) {
                        $tMeasure = Measure::find($measure->measure_id);
                        $arr['name'] = $tMeasure->name;
                        $arr['total'] = $this->getGroupedTestCounts($sputum, null, null, $from, $toPlusOne);
                        $arr['positive'] = $this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, null, null);
                        array_push($sputumList, $arr);
                    }
                    if($sp != "Sputum for AFB")
                    {
                        /*TODO: complete below report after design work is complete */
                        $arr['name'] = $sp;
                        $arr['total'] = 0;
                        $arr['positive'] = 0;
                        array_push($sputumList, $arr);
                    }
                }
                $moh706List['sputumList'] = $sputumList;
                
                /*========================*/
                /* HISTOLOGY AND CYTOLOGY */
                /*========================*/
                
                /* Smears */
                $moh706List['smearsList'] = $this->histologyCytologySerology(array('Pap Smear', 'Tissue Impression', 'Touch preparations'), $from, $toPlusOne); 
                /* Fine needles aspirates*/
                $moh706List['aspiratesList'] = $this->histologyCytologySerology(array('Fine Needle aspirates Thyroid','Lymph nodes tissue', 
                    'Fine Needle aspirates Liver', 'Fine Needle aspirates Breast', 'Fine Needle aspirates soft tissue masses'), $from, $toPlusOne);
                /* Fluid cytology*/
                $moh706List['fluidCytologyList'] = $this->histologyCytologySerology(array('Ascitic fluid','CSF', 'Pleural fluid', 'Urine'), $from, $toPlusOne);
                /* Tissue Histology*/
                $moh706List['tissueHistologyList'] = $this->histologyCytologySerology(
                        array('Cervix','Prostrate', 'Breast tissue', 'Ovarian cyst', 'Uterus', 'Skin', 'Head and neck', 'Dental', 'GIT', 'Lymph nodes'), 
                        $from, 
                        $toPlusOne);
                /* Bone Murrow Studies*/
                $moh706List['boneMurrowStudiesList'] = $this->histologyCytologySerology(array('Bone marrow aspirates','Trephine biopsy'), $from, $toPlusOne);
                
                /*========================*/
                /* SEROLOGY */
                /*========================*/
                /* Serology test */
                $moh706List['serologyList'] = $this->histologyCytologySerology(
                        array('VDRL','TPHA', 'ASOT test', 'HIV rapid testing', 'Brucella test', 'Rheumatoid factor', 'Helicobacter pylori',
                            'Hepatitis A test', 'Hepatitis B test (HBs Ag)', 'Hepatitis C test', 'HCG', 'CRAG test'), 
                        $from, 
                        $toPlusOne);
                $moh706List['CRAG_FLAG'] = 'CRAG test';
                
                /*=====================================*/
                /* SPECIMEN REFERRAL TO HIGHTER LEVELS */
                /*=====================================*/     
                $specimenReferralList = array();
                $specimenReferals = array( 
                    "CD4" => array ("CD4 count"),
                    "Viral Load" => array ("Viral Load"),
                    "EID" => array ("Early infant diagnosis"),
                    "Discordant/discrepant" => array ("Discordant/discrepant"),
                    "TB Culture" => array ("Sputum culture"),
                    "Virological" => array ("Viral load", "Rota Virus", "Yellow fever", "Herpes simplex", 
                                        "Polio", "Dangue Fever", "Hepatitis B test (HBs Ag)", "Hepatitis C test", "Culture for haemophilus influenza", 
                                        "Rabiess", "Measles"),
                    "Clinical Chemistry" => array ("CSF  glucose analysis", "CSF protein analysis", "SGOT/ASTr", "SGPT/ALT", 
                                        "Total Protein", "Albumin", "Total Bilirubin", "Direct Bilirubin", "Alkaline Phosphatase", 
                                        "Gamma GT", "Urea", "Potassium", "Sodium", "Chloride", "Creatinine", "Phosphorous", "Uric acid", 
                                        "Calcium", "Amylase", "Total cholestrol", "LDL", "HDL", "Triglycerides", "Fasting blood sugar",
                                        "Random Blood sugar", "OGTT", "HBA1C", "Occult blood test", "Thyroid function tests", "Thyroid function tests",
                                        "Acid Phosphatase", "Bence Jones proteins", "Carcinoembryonic antigen test (CEA)", "Cancer antigen 15-3 (C15-3)"),
                    "Histology/cytology" => array ("Tissue Impression", "Fine Needle aspirates Thyroid", "Fine Needle aspirates Lymph node", "Fine Needle aspirates Liver", 
                                        "Fine Needle aspirates Breast", "Fine Needle aspirates soft tissue masses", "Pap Smear", "Ascitic fluid", "CSF", 
                                        "Pleural fluid", "Cervix tissue", "Prostrate tissue", "Breast Tissue","Ovarian cyst", "Fibroids","Lymph nodes tissue", 
                                        "Touch preparations","Bone marrow aspirate", "Trephine biopsy","Ovary tissue", "Uterus tissue","Skin tissue", 
                                        "Head and Neck tissue","Dental tissue", "Gastro intestinal tract (GIT) tissue"),
                    "Haematology" => array ("Full haemogram/Full blood count", "White blood cells", "Platelets", "Erythrocyte sedimentation rate (ESR)", 
                                        "Haemoglobin", "Reticulocyte count", "Sickling test", "CD4 count", "CD4 %", "Coagulation Profile", "HB electrophoresis", 
                                        "Bone marrow aspirate", "Peripheral Blood films"),
                    "Parasitology" => array ("Blood slide for Malaria", "Stool analysis Taenia spp", "Stool analysis H.nana", "Stool analysis H.diminuta", 
                                        "Stool analysis Hookworm", "Stool analysis Roundworms", "Stool analysis S. mansoni", "Stool analysis Trichuris trichiura", 
                                        "Stool analysis Strongyloides stercoralis", "Stool analysis Isospora belli", "Stool analysis Entamoeba hystolytica",
                                        "Stool analysis Giardia lamblia", "Borelia", "Microfilariae", "Trypanosomes", "Malaria  Rapid Diagnostic Test"),
                    "Blood samples for transfusion screening" => array ("Early infant diagnosis")
                     );
                
                if($specimenReferals)
                {
                    foreach($specimenReferals as $specName => $value)
                    { 
                        $specimenTotal = 0;
                        $resultsReceivedTotal = 0;
                                                
                        foreach($value as $tests){
                            $testId = TestType::getTestTypeIdByTestName(trim($tests));
                            $specRef = TestType::find($testId);
                            $specimenTotal += $this->getReferredSpecimenCount($specRef, $testId, $from, $toPlusOne);
                            $resultsReceivedTotal += $this->getReferredSpecimenReceivedResult($specRef, $testId, $from, $toPlusOne);
                            //$output = "<script>console.log( 'TEST ID: " .$testId. ":::: TEST NAME: ".$tests."');</script>";
                            //echo $output;
                        }
                        $arr['name'] = $specName;
                        $arr['total'] = $specimenTotal;
                        $arr['positive'] = $resultsReceivedTotal;//TODO: need to be No. of results received
                        array_push($specimenReferralList, $arr);
                    }
                }
                $moh706List['specimenReferralList'] = $specimenReferralList;
                
                /*===============================*/
                /*  DRUG SUSCEPTIBILITY TESTING  */
                /*===============================*/
                $drugsArr = array('Ampicillin','Chloramphenicol','Ceftriaxone','Penicillin', 'Oxacillin', 'Ciprofloxacin', 
                   'Nalidixic acid','Trimethoprim-sulphamethoxazole', 'Tetracycline', 'Augmentin');//hold the list of drugs to be reported
                $drugs = Drug::all();//get all drugs from the catalog
                $drugList = array();
                $organismsArr = array('Haemophilus','Neisseria meningitidis','Streptococcus Pneumoniae',
                    'Salmonella','Shigella', 'Vibrio cholerae', 'Bacillus anthracis', 'Yersinia pestis');//hold the list of drugs to be reported
                $organisms = Organism::all();
                $organismsList = array();
                //print_r(Organism::find(Organism::getOrganismIdByName('Shigella')));
                
                
                //Haemophilus Neisseria Streptococcus
                /*foreach ($drugs as $drug){
                    $arr['name'] = $drug->name;
                    array_push($drugList, $arr);
                }*/
                foreach ($drugsArr as $dg){
                    $drugID = Drug::getDrugIdByName($dg);
                    $drug = Drug::find($drugID);
                    $arr['name'] = $dg ; //$drug->name;
                    array_push($drugList, $arr);
                }
                $moh706List['drugs'] = $drugList;
                
                /*foreach ($organisms as $organism){
                    $arr['name'] = $organism->name;
                    array_push($organismsList, $arr);
                }*/
                $sensitivity=Susceptibility::getDrugSusceptibilityTesting(15, 1,'I');
                foreach($organismsArr as $org)
                {
                   $orgID = Organism::getOrganismIdByName($org);
                   $organism  = Organism::find($orgID);
                   $arr['name'] = $organism->name;
                   $arr['drug'] = array();
                   foreach ($drugsArr as $dg){//create drug sensisity 
                        $drugID = Drug::getDrugIdByName($dg);
                        $ar['s'] = Susceptibility::getDrugSusceptibilityTesting($orgID, $drugID,'S');
                        $ar['i'] = Susceptibility::getDrugSusceptibilityTesting($orgID, $drugID,'I');
                        $ar['r'] = Susceptibility::getDrugSusceptibilityTesting($orgID, $drugID,'R');
                        /* ==  DEBUGGING SCRIPT == */
                        //$output = "<script>console.log( 'Debug Objects: " .$dg. "' );</script>";
                        //echo $output;
                        /* ==  DEBUGGING SCRIPT == */
                        array_push($arr['drug'], $ar);
                    }
                   array_push($organismsList, $arr);
                }
                $moh706List['organisms'] = $organismsList;
/*Export to excel or view*/
		if(Input::has('excel')){
			$date = date("Ymdhi");
			$fileName = "MOH706_".$date.".xls";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			//$content = $table;
                        $content = View::make('reports.moh.706')
                                ->with('from', $from)
                                ->with('end', $end)
                                ->with('moh706List', $moh706List);
                        
	    	return Response::make($content,200, $headers);
		}
		else{
			//return View::make('reports.moh.706');
			return View::make('reports.moh.index')
                                ->with('table', $table)
                                ->with('from', $from)
                                ->with('end', $end)
                                ->with('moh706List', $moh706List);
		}
	}
        
	/**
	 * Manage Diseases reported on
	 * @param
	 */
	public function disease(){
		if (Input::all()) {
			$rules = array();
			$newDiseases = Input::get('new-diseases');

			if (Input::get('new-diseases')) {
				// create an array that form the rules array
				foreach ($newDiseases as $key => $value) {
					
					//Ensure no duplicate disease
					$rules['new-diseases.'.$key.'.disease'] = 'unique:diseases,name';
				}
			}

			$validator = Validator::make(Input::all(), $rules);

			if ($validator->fails()) {
				return Redirect::route('reportconfig.disease')->withErrors($validator);
			} else {

		        $allDiseaseIds = array();
				
				//edit or leave disease entries as is
				if (Input::get('diseases')) {
					$diseases = Input::get('diseases');

					foreach ($diseases as $id => $disease) {
		                $allDiseaseIds[] = $id;
						$diseases = Disease::find($id);
						$diseases->name = $disease['disease'];
						$diseases->save();
					}
				}
				
				//save new disease entries
				if (Input::get('new-diseases')) {
					$diseases = Input::get('new-diseases');

					foreach ($diseases as $id => $disease) {
						$diseases = new Disease;
						$diseases->name = $disease['disease'];
						$diseases->save();
		                $allDiseaseIds[] = $diseases->id;
					}
				}

		        //check if action is from a form submission
		        if (Input::get('from-form')) {
			     	// Delete any pre-existing disease entries
			     	//that were not captured in any of the above save loops
			        $allDiseases = Disease::all(array('id'));

			        $deleteDiseases = array();

			        //Identify disease entries to be deleted by Ids
			        foreach ($allDiseases as $key => $value) {
			            if (!in_array($value->id, $allDiseaseIds)) {

							//Allow delete if not in use
							$inUseByReports = Disease::find($value->id)->reportDiseases->toArray();
							if (empty($inUseByReports)) {
							    
							    // The disease is not in use
			                	$deleteDiseases[] = $value->id;
							}
			            }
			        }
			        //Delete disease entry if any
			        if(count($deleteDiseases)>0){

			        	Disease::destroy($deleteDiseases);
			        }
		        }
			}
		}
		$diseases = Disease::all();

		return View::make('reportconfig.disease')
					->with('diseases', $diseases);
	}

	public function stockLevel(){
		
		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');

		$to = Input::get('end');
		if(!$to) $to = $date;
		
		$reportTypes = array('Monthly', 'Quarterly');
		

		$selectedReport = Input::get('report_type');	
		if(!$selectedReport)$selectedReport = 0;

		switch ($selectedReport) {
			case '0':
			
				$reportData = Receipt::getIssuedCommodities($from, $to.' 23:59:59');
				$reportTitle = Lang::choice('messages.monthly-stock-level-report-title',1);
				break;
			case '1':
				$reportData = Receipt::getIssuedCommodities($from, $to.' 23:59:59');
				$reportTitle = Lang::choice('messages.quarterly-stock-level-report-title',1);
				break;
				default:
				$reportData = Receipt::getIssuedCommodities($from, $to.' 23:59:59');
				$reportTitle = Lang::choice('messages.monthly-stock-level-report-title',1);
				break;
		}

		$reportTitle = str_replace("[FROM]", $from, $reportTitle);
		$reportTitle = str_replace("[TO]", $to, $reportTitle);
		
		return View::make('reports.inventory.index')
					->with('reportTypes', $reportTypes)
					->with('reportData', $reportData)
					->with('reportTitle', $reportTitle)
					->with('selectedReport', $selectedReport)
					->withInput(Input::all());
	}
	/**
	* Function to calculate the mean, SD, and UCL, LCL
	* for a given control measure.
	*
	* @param control_measure_id
	* @return json string
	* 
	*/
	public function leveyJennings($control, $dates)
	{
		foreach ($control->controlMeasures as $key => $controlMeasure) {
			if(!$controlMeasure->isNumeric())
			{
				//We ignore non-numeric results
				continue;
			}

			$results = $controlMeasure->results()->whereBetween('created_at', $dates)->lists('results');

			$count = count($results);

			if($count < 6)
			{
				$response[] = array('success' => false,
					'error' => "Too few results to create LJ for ".$controlMeasure->name);
				continue;
			}

			//Convert string results to float 
			foreach ($results as &$result) {
				$result = (double) $result;
			}

			$total = 0;
			foreach ($results as $res) {
				$total += $res;
			}

			$average = round($total / $count, 2);

			$standardDeviation = $this->stat_standard_deviation($results);
			$standardDeviation  = round($standardDeviation, 2);

			$response[] = array('success' => true,
							'total' => $total,
							'average' => $average,
							'standardDeviation' => $standardDeviation,
							'plusonesd' => $average + $standardDeviation,
							'plustwosd' => $average + ($standardDeviation * 2),
							'plusthreesd' => $average + ($standardDeviation * 3),
							'minusonesd' => $average - ($standardDeviation),
							'minustwosd' => $average - ($standardDeviation * 2),
							'minusthreesd' => $average - ($standardDeviation * 3),
							'dates' => $controlMeasure->results()->lists('created_at'),
							'controlName' => $controlMeasure->name,
							'controlUnit' => $controlMeasure->unit,
							'results' => $results);
		}
		return json_encode($response);
	}

    /**
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     * 
     * @param array $a 
     * @param bool $sample [optional] Defaults to false
     * @return float|bool The standard deviation or false on error.
     */
    function stat_standard_deviation(array $a, $sample = false) {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
           --$n;
        }
        return sqrt($carry / $n);
    }

	/**
	 * Display data after applying the filters on the report uses patient ID
	 *
	 * @return Response
	 */
	public function cd4(){
		//	check if accredited
		$accredited = array();
		$from = Input::get('start');
		$to = Input::get('end');
		$pending = Input::get('pending');
		$date = date('Y-m-d');
		$error = '';
		//	Check dates
		if(!$from)
			$from = date('Y-m-01');
		if(!$to)
			$to = $date;
		//	Get columns
		$columns = array(Lang::choice('messages.cd4-less', 1), Lang::choice('messages.cd4-greater', 1));
		$rows = array(Lang::choice('messages.baseline', 1), Lang::choice('messages.follow-up', 1));
		//	Get test
		$test = TestType::find(TestType::getTestTypeIdByTestName('cd4'));
		$counts = array();
		foreach ($columns as $column)
		{
			foreach ($rows as $row)
			{
				if($test != null) {
					$counts[$column][$row] = $test->cd4($from, $to, $column, $row);
				}
				else {
					$counts[$column][$row] = 0;
				}
			}
		}
		if(Input::has('word'))
		{
			$date = date("Ymdhi");
			$fileName = "cd4_report_".$date.".doc";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.cd4.export')
				->with('columns', $columns)
				->with('rows', $rows)
				->with('accredited', $accredited)
				->with('test', $test)
				->with('counts', $counts)
				->withInput(Input::all());
	    	return Response::make($content,200, $headers);
		}
		else if(Input::has('excel'))
		{
			$date = date("Ymdhi");
			$fileName = "cd4_report_".$date.".xls";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.cd4.export')
				->with('columns', $columns)
				->with('rows', $rows)
				->with('accredited', $accredited)
				->with('test', $test)
				->with('counts', $counts)
				->withInput(Input::all());
	    	return Response::make($content,200, $headers);
		}
		else
		{
			return View::make('reports.cd4.index')
				->with('columns', $columns)
				->with('rows', $rows)
				->with('accredited', $accredited)
				->with('test', $test)
				->with('counts', $counts)
				->withInput(Input::all());
		}
	}
    /**
    *	Function to check for accredited test types
    *
    */
    public function accredited($tests)
    {
    	$accredited = array();
		foreach ($tests as $test) {
			if($test->testType->isAccredited())
				array_push($accredited, $test->id);
		}
		return $accredited;
    }
    /**
	 * Display specimen rejection chart
	 *
	 * @return Response
	 */
	public static function specimenRejectionChart($testTypeID = 0){
		$from = Input::get('start');
		$to = Input::get('end');
		$spec_type = Input::get('specimen_type');
		$months = json_decode(self::getMonths($from, $to));

		//	Get specimen rejection reasons available in the time period
		$rr = Specimen::select(DB::raw('DISTINCT(reason) AS rr, rejection_reason_id'))
						->join('rejection_reasons', 'rejection_reasons.id', '=', 'specimens.rejection_reason_id')
						->whereBetween('time_rejected', [$from, $to])
						->groupBy('rr')
						->get();

		$options = '{
		    "chart": {
		        "type": "spline"
		    },
		    "title": {
		        "text":"Rejected Specimen per Reason Overtime"
		    },
		    "subtitle": {
		        "text":'; 
		        if($from==$to)
		        	$options.='"'.trans('messages.for-the-year').' '.date('Y').'"';
		        else
		        	$options.='"'.trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to.'"';
		    $options.='},
		    "credits": {
		        "enabled": false
		    },
		    "navigation": {
		        "buttonOptions": {
		            "align": "right"
		        }
		    },
		    "series": [';
		    	$counts = count($rr);

			    	foreach ($rr as $rrr) 
			    	{
		        		$options.= '{
		        			"name": "'.$rrr->rr.'","data": [';
	        				$counter = count($months);
	            			foreach ($months as $month) 
	            			{
		            			$data = Specimen::where('rejection_reason_id', $rrr->rejection_reason_id)->whereRaw('MONTH(time_rejected)='.$month->months);
		            			if($spec_type)
		            				$data = $data->where('specimen_type_id', $spec_type);
		            			$data = $data->count();		            				
            					$options.= $data;
            					if($counter==1)
	            					$options.='';
	            				else
	            					$options.=',';
		            			$counter--;
				    		}
				    		$options.=']';
				    	if($counts==1)
							$options.='}';
						else
							$options.='},';
						$counts--;
					}
			$options.='],
		    "xAxis": {
		        "categories": [';
		        $count = count($months);
	            	foreach ($months as $month) {
	    				$options.= '"'.$month->label." ".$month->annum;
	    				if($count==1)
	    					$options.='" ';
	    				else
	    					$options.='" ,';
	    				$count--;
	    			}
	            $options.=']
		    },
		    "yAxis": {
		        "title": {
		            "text": "No. of Rejected Specimen"
		        }
		    }
		}';
	return View::make('reports.rejection.index')
						->with('options', $options)
						->withInput(Input::all());
	}
}
