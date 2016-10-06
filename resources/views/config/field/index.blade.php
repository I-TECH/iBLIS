@extends("layout")

@section("content")
<div class="row">
    <div class="col-sm-12">
        <ul class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-toggle-off"></i> {!! trans('messages.lab-configuration') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans_choice('messages.field', 2) !!}</li>
        </ul>
    </div>
</div>
<div class="conter-wrapper">
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-header">
				    <i class="fa fa-book"></i> {!! trans_choice('messages.field', 2) !!} 
				    <span>
					    <a class="btn btn-sm btn-belize-hole" href="{!! url("field/create") !!}" >
							<i class="fa fa-plus-circle"></i>
							{!! trans('messages.new').' '.trans_choice('messages.field', 1) !!}
						</a>
						<a class="btn btn-sm btn-carrot" href="#" onclick="window.history.back();return false;" alt="{!! trans('messages.back') !!}" title="{!! trans('messages.back') !!}">
							<i class="fa fa-step-backward"></i>
							{!! trans('messages.back') !!}
						</a>				
					</span>
				</div>
			  	<div class="card-block">	  		
					@if (Session::has('message'))
						<div class="alert alert-info">{!! Session::get('message') !!}</div>
					@endif
					@if($errors->all())
		            <div class="alert alert-danger alert-dismissible" role="alert">
		                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">{!! trans('messages.close') !!}</span></button>
		                {!! HTML::ul($errors->all(), array('class'=>'list-unstyled')) !!}
		            </div>
		            @endif
				 	<table class="table table-bordered table-sm search-table">
						<thead>
							<tr>
								<th>{!! trans('messages.name') !!}</th>
								<th>{!! trans_choice('messages.module', 1) !!}</th>
								<th>{!! trans_choice('messages.field-type', 1) !!}</th>
								<th>{!! trans("menu.options") !!}</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
						@foreach($fields as $key => $value)
							<tr @if(session()->has('active_field'))
				                    {!! (session('active_field') == $value->id)?"class='warning'":"" !!}
				                @endif
				                >
								<td>{!! $value->name !!}</td>
								<td>{!! $value->configurable->name !!}</td>
								<td>{!! $value->field_type !!}</td>
								<td>{!! implode(',', $value->options) !!}</td>
								
								<td>

								<!-- show the test category (uses the show method found at GET /field/{id} -->
									<a class="btn btn-sm btn-success" href="{!! url("field/" . $value->id) !!}" >
										<i class="fa fa-folder-open-o"></i>
										{!! trans('messages.view') !!}
									</a>

								<!-- edit this test category (uses edit method found at GET /field/{id}/edit -->
									<a class="btn btn-sm btn-info" href="{!! url("field/" . $value->id . "/edit") !!}" >
										<i class="fa fa-edit"></i>
										{!! trans('messages.edit') !!}
									</a>
									
								<!-- delete this test category (uses delete method found at GET /field/{id}/delete -->
									<button class="btn btn-sm btn-danger delete-item-link"
										data-toggle="modal" data-target=".confirm-delete-modal"	
										data-id='{!! url("field/" . $value->id . "/delete") !!}'>
										<i class="fa fa-trash-o"></i>
										{!! trans('messages.delete') !!}
									</button>
								</td>
							</tr>
						@endforeach
						</tbody>
					</table>
			  	</div>
			</div>
		</div>
	</div>
	{!! session(['SOURCE_URL' => URL::full()]) !!}
</div>
@endsection