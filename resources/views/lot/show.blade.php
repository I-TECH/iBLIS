@extends("layout")
@section("content")
	<div>
		<ol class="breadcrumb">
		  <li><a href="{{{URL::route('user.home')}}}">{{trans('messages.home')}}</a></li>
		  <li><a href="{{ URL::route('lot.index') }}">{{trans_choice('messages.lot',2)}}</a></li>
		  <li class="active">{{trans('messages.lot-details')}}</li>
		</ol>
	</div>
	<div class="panel panel-primary">
		<div class="panel-heading ">
			<span class="glyphicon glyphicon-cog"></span>
			{{trans('messages.lot-details')}}
			<div class="panel-btn">
				<a class="btn btn-sm btn-info" href="{{ URL::route('lot.edit', array($lot->id)) }}">
					<span class="glyphicon glyphicon-edit"></span>
					{{trans('messages.edit')}}
				</a>
			</div>
		</div>
		<div class="panel-body">
			<div class="display-details">
				<h3 class="view"><strong>{{trans_choice('messages.lot-number',1)}}</strong>{{ $lot->lot_no }} </h3>
				<p class="view-striped"><strong>{{trans('messages.description')}}</strong>
					{{ $lot->description }}</p>
				<p class="view-striped"><strong>{{trans('messages.date-created')}}</strong>
					{{ $lot->created_at }}</p>
			</div>
		</div>
	</div>
@stop