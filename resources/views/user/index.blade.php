@extends("layout")
@section("content")
<div>
	<ol class="breadcrumb">
	  <li><a href="{{{URL::route('user.home')}}}">{{ trans('messages.home') }}</a></li>
	  <li class="active">{{ trans_choice('messages.user',1) }}</li>
	</ol>
</div>
@if (Session::has('message'))
	<div class="alert alert-info">{{ Session::get('message') }}</div>
@endif
<div class="panel panel-primary">
	<div class="panel-heading ">
		<span class="glyphicon glyphicon-user"></span>
		List Users
		<div class="panel-btn">
			<a class="btn btn-sm btn-info" href="{{ URL::to("user/create") }}" >
				<span class="glyphicon glyphicon-plus-sign"></span>
				{{ trans('messages.new-user') }}
			</a>
		</div>
	</div>
	<div class="panel-body">
		<table class="table table-striped table-hover table-condensed search-table">
			<thead>
				<tr>
					<th>{{ trans('messages.username') }}</th>
					<th>{{ trans_choice('messages.name',1) }}</th>
					<th>{{ trans('messages.email') }}</th>
					<th>{{ trans('messages.gender') }}</th>
					<th>{{ trans('messages.designation') }}</th>
					<th>{{ trans('messages.actions') }}</th>
				</tr>
			</thead>
			<tbody>
			@foreach($users as $user)
				<tr @if(Session::has('activeuser'))
                            {{(Session::get('activeuser') == $user->id)?"class='info'":""}}
                        @endif
                        >

					<td>{{ $user->username }}</td>
					<td>{{ $user->name }}</td>
					<td>{{ $user->email }}</td>
					<td>{{ ($user->gender == 0) ? "Male":"Female" }}</td>
					<td>{{ $user->designation }}</td>

					<td>

						<!-- show the user (uses the show method found at GET /user/{id} -->
						<a class="btn btn-sm btn-success" href="{{ URL::to("user/" . $user->id) }}">
							<span class="glyphicon glyphicon-eye-open"></span>
							{{ trans('messages.view') }}
						</a>

						<!-- edit this user (uses the edit method found at GET /user/{id}/edit -->
						<a class="btn btn-sm btn-info" href="{{ URL::to("user/" . $user->id . "/edit") }}" >
							<span class="glyphicon glyphicon-edit"></span>
							{{ trans('messages.edit') }}
						</a>
						<!-- delete this user (uses the delete method found at GET /user/{id}/delete -->
						<button class="btn btn-sm btn-danger delete-item-link {{($user == App\Models\User::getAdminUser()) ? 'disabled': ''}}"
							data-toggle="modal" data-target=".confirm-delete-modal"	
							data-id='{{ URL::to("user/" . $user->id . "/delete") }}'>
							<span class="glyphicon glyphicon-trash"></span>
							{{ trans('messages.delete') }}
						</button>

					</td>
				</tr>
			@endforeach
			</tbody>
		</table>
		{{ Session::put('SOURCE_URL', URL::full()) }}
	</div>
</div>
@stop