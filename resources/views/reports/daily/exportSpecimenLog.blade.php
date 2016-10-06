<html>
<head>
{{ HTML::style('css/bootstrap.min.css') }}
{{ HTML::style('css/bootstrap-theme.min.css') }}
</head>
<body>
@include("reportHeader")
<div id="content">
	<strong>
		<p>
			{{trans('messages.rejected-specimen')}} 
			@if($testCategory)
				{{' - '.App\Models\TestCategory::find($testCategory)->name}}
			@endif
			@if($testType)
				{{' ('.TestType::find($testType)->name.') '}}
			@endif
			<?php $from = isset($input['start'])?$input['start']:date('Y-m-d'); ?>
			<?php $to = isset($input['end'])?$input['end']:date('Y-m-d'); ?>
			@if($from!=$to)
				{{trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to}}
			@else
				{{trans('messages.for').' '.date('d-m-Y')}}
			@endif
		</p>
	</strong>
	<br>
	<table class="table table-bordered">
		<tbody>
			<th>{{ trans('messages.patient-number') }}</th>
                        <th>{{ trans('messages.patient-name') }}</th>
                        <th>{{trans('messages.specimen-number-title')}}</th>
			<th>{{trans('messages.specimen')}}</th>
			<th>{{trans('messages.lab-receipt-date')}}</th>
			<th>{{ trans_choice('messages.test', 2) }}</th>
			<th>{{trans_choice('messages.test-category', 1)}}</th>
			<th>{{trans('messages.rejection-reason-title')}}</th>
			<th>{{trans('messages.reject-explained-to')}}</th>
			<th>{{trans('messages.date-rejected')}}</th>
			@forelse($specimens as $specimen)
			<tr>
				<td>{{ $specimen->test->visit->patient->id }}</td>
                                <td>{{ $specimen->test->visit->patient->name }}</td>
                                <td>{{ $specimen->id }}</td>
				<td>{{ $specimen->specimenType->name }}</td>
				<td>{{ $specimen->test->time_created }}</td>
				<td>{{ $specimen->test->testType->name }}</td>
				<td>{{ $specimen->test->testType->testCategory->name }}</td>
				<td>{{ $specimen->rejectionReason->reason }}</td>
				<td>{{ $specimen->reject_explained_to }}</td>
				<td>{{ $specimen->time_rejected }}</td>
			</tr>
			@empty
			<tr><td colspan="8">{{trans('messages.no-records-found')}}</td></tr>
			@endforelse
		</tbody>
	</table>
</div>
</body>
</html>