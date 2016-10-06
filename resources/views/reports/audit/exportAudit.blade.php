<html>
<head>
{{ HTML::style('css/bootstrap.min.css') }}
{{ HTML::style('css/bootstrap-theme.min.css') }}
<style type="text/css">
	#content table, #content th, #content td {
	   border: 1px solid black;
	   font-size:12px;
	}
	#content p{
		font-size:12px;
	 }
</style>
</head>
<body>
<div id="wrap">
    <div class="container-fluid">
    	<div id="report_content">
				<table width="100%" style="font-size:12px;">
					<thead>
						<tr>
							<td>{{ HTML::image(Config::get('blis.organization-logo'),  Config::get('blis.country') . trans('messages.court-of-arms'), array('width' => '90px')) }}</td>
							<td colspan="3" style="text-align:center;">
								<strong><p> {{ strtoupper(Config::get('blis.organization')) }}<br>
								{{ strtoupper(Config::get('blis.address-info')) }}</p>
								<p>{{ trans('messages.laboratory-report')}}<br>
							</td>
							<td>
								{{ HTML::image(Config::get('blis.organization-logo'),  Config::get('blis.country') . trans('messages.court-of-arms'), array('width' => '90px')) }}
							</td>
						</tr>
					</thead>
				</table>
			<strong>
				<p>
					{{trans('messages.patient-report').' - '.date('d-m-Y')}}
				</p>
			</strong>
			<table class="table table-bordered">
				<tbody>
					<tr>
						<th>{{ trans('messages.patient-name')}}</th>
						@if(Entrust::can('view_names'))
							<td>{{ $test->visit->patient->name }}</td>
						@else
							<td>N/A</td>
						@endif
						<th>{{ trans('messages.gender')}}</th>
						<td>{{ $test->visit->patient->getGender(false) }}</td>
					</tr>
					<tr>
						<th>{{ trans('messages.patient-id')}}</th>
						<td>{{ $test->visit->patient->patient_number}}</td>
						<th>{{ trans('messages.age')}}</th>
						<td>{{ $test->visit->patient->getAge()}}</td>
					</tr>
					<tr>
						<th>{{ trans('messages.patient-lab-number')}}</th>
						<td>{{ $test->visit->patient->external_patient_number }}</td>
						<th>{{ trans('messages.requesting-facility-department')}}</th>
						<td>{{ Config::get('blis.organization') }}</td>
					</tr>
				</tbody>
			</table>
			<table class="table table-bordered">
				<tbody>
					<tr>
						<th colspan="7">{{trans('messages.specimen')}}</th>
					</tr>
					<tr>
						<th>{{ trans_choice('messages.specimen-type', 1)}}</th>
						<th>{{ trans_choice('messages.test', 2)}}</th>
						<th>{{ trans('messages.date-ordered') }}</th>
						<th>{{ trans_choice('messages.test-category', 2)}}</th>
						<th>{{ trans('messages.specimen-status')}}</th>
						<th>{{ trans('messages.collected-by')."/".trans('messages.rejected-by')}}</th>
						<th>{{ trans('messages.date-checked')}}</th>
					</tr>
					@if($test)
							<tr>
								<td>{{ $test->specimen->specimenType->name }}</td>
								<td>{{ $test->testType->name }}</td>
								<td>{{ $test->isExternal()?$test->external()->request_date:$test->time_created }}</td>
								<td>{{ $test->testType->testCategory->name }}</td>
								@if($test->specimen->specimen_status_id == Specimen::NOT_COLLECTED)
									<td>{{trans('messages.specimen-not-collected')}}</td>
									<td></td>
									<td></td>
								@elseif($test->specimen->specimen_status_id == Specimen::ACCEPTED)
									<td>{{trans('messages.specimen-accepted')}}</td>
									<td>{{$test->specimen->acceptedBy->name}}</td>
									<td>{{$test->specimen->time_accepted}}</td>
								@elseif($test->specimen->specimen_status_id == Specimen::REJECTED)
									<td>{{trans('messages.specimen-rejected')}}</td>
									<td>{{$test->specimen->rejectedBy->name}}</td>
									<td>{{$test->specimen->time_rejected}}</td>
								@endif
							</tr>
					@else
						<tr>
							<td colspan="7">{{trans("messages.no-records-found")}}</td>
						</tr>
					@endif

				</tbody>
			</table>

			<table class="table table-bordered">
				<tbody>
					<tr>
						<th colspan="2">{{trans('messages.audit-report')}}</th>
					</tr>
					<tr>
						<th>{{trans_choice('messages.test-type', 1)}}</th>
						<th>{{trans('messages.previous-results')}}</th>
					</tr>
					@if($test)
							<tr>
								<td>{{ $test->testType->name }}</td>
								<td>
									@foreach($test->testResults as $result)
										<p class="view">
											<strong>{{ Measure::find($result->measure_id)->name }}</strong></br> 
											<u>{{trans('messages.current-result')}}
												<table class="table">
													<tbody>
														<tr>
															<td>{{trans('messages.current-result')}}: {{ $result->result }}
															{{ Measure::getRange($test->visit->patient, $result->measure_id) }}
															{{ Measure::find($result->measure_id)->unit }}
															</td>
															<td>{{trans('messages.entered-by')}}: {{ $test->testedBy->name}}</td>
															<td>{{trans('messages.results-entry-date')}}: {{ $test->testResults->last()->time_entered }}</td>
														</tr>
													</tbody>
												</table>
											</br>

											<u>{{trans('messages.previous-results')}}</u> </br>
											<table class="table">
												<tbody>
												@foreach($result->auditResults as $auditResult)
													<tr>
														<td>{{trans('messages.result-name')}} : {{ $auditResult->previous_results }}
														{{Measure::getRange($test->visit->patient, $result->measure_id) }} {{ Measure::find($result->measure_id)->unit }}</td>
														<td>{{trans('messages.entered-by')}} : {{ $auditResult->user->username }}</td>
														<td>{{trans('messages.created-at')}} : {{ $auditResult->created_at }}</td>
													</tr>
												@endforeach
												</tbody>
											</table>
										</p>
									@endforeach
								</td>
							</tr>
					@else
						<tr>
							<td colspan="8">{{trans("messages.no-records-found")}}</td>
						</tr>
					@endif
				</tbody>
			</table>

			</div>
    </div>
</div>
</body>
</html>