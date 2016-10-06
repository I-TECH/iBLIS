@extends("layout")

@section("content")
<div class="row">
    <div class="col-sm-12">
        <ul class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-cubes"></i> {!! trans('messages.inventory') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans_choice('messages.item', 2) !!}</li>
        </ul>
    </div>
</div>
<div class="conter-wrapper">
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-header">
				    <i class="fa fa-book"></i> {!! trans_choice('messages.item', 2) !!} 
				    <span>
					    <a class="btn btn-sm btn-belize-hole" href="{!! url("item/create") !!}" >
							<i class="fa fa-plus-circle"></i>
							{!! trans('messages.new').' '.trans_choice('messages.item', 1) !!}
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
								<th>{!! trans('messages.unit') !!}</th>
								<th>{!! trans('messages.quantity') !!}</th>
								<th>{!! trans('messages.min-level') !!}</th>
								<th>{!! trans('messages.max-level') !!}</th>
								<th>{!! trans('messages.remarks') !!}</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
						@foreach($items as $key => $value)
							<tr @if(session()->has('active_item'))
				                    {!! (session('active_item') == $value->id)?"class='warning'":"" !!}
				                @endif
				                >
								<td>{!! $value->name !!}</td>
								<td>{!! $value->quantity() !!}</td>
								<td>{!! $value->unit !!}</td>
								<td>{!! $value->min_level !!}</td>
								<td>{!! $value->max_level !!}</td>
								<td>{!! $value->remarks !!}</td>
								
								<td>

								<!-- show the test category (uses the show method found at GET /item/{id} -->
									<a class="btn btn-sm btn-success" href="{!! url("item/" . $value->id) !!}" >
										<i class="fa fa-folder-open-o"></i>
										{!! trans('messages.view') !!}
									</a>

								<!-- edit this test category (uses edit method found at GET /item/{id}/edit -->
									<a class="btn btn-sm btn-info" href="{!! url("item/" . $value->id . "/edit") !!}" >
										<i class="fa fa-edit"></i>
										{!! trans('messages.edit') !!}
									</a>

								<!-- sshow button for logging stock usage -->
									<a class="btn btn-sm btn-midnight-blue" href="{!! url("stock/" . $value->id)."/log" !!}" >
										<i class="fa fa-file-text"></i>
										{!! trans('messages.log-usage') !!}
									</a>

								<!-- sshow button for generating barcode -->
									<a class="btn btn-sm btn-asbestos" href="#" onclick="get_barcode('{!! $value->id.$value->name !!}')">
										<i class="fa fa-barcode"></i>
										{!! trans('messages.barcode') !!}
									</a>

								<!-- sshow button for adding stock -->
									<a class="btn btn-sm btn-wisteria" href="{!! url("stock/" . $value->id)."/create" !!}" >
										<i class="fa fa-shopping-cart"></i>
										{!! trans('messages.add-stock') !!}
									</a>
									
								<!-- delete this test category (uses delete method found at GET /item/{id}/delete -->
									<button class="btn btn-sm btn-danger delete-item-link"
										data-toggle="modal" data-target=".confirm-delete-modal"	
										data-id='{!! url("item/" . $value->id . "/delete") !!}'>
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
	<div id="count" style='display:none;'>0</div>
	<div id ="barcodeList" style="display:none;"></div>
	<!-- jQuery barcode script -->
	<script type="text/javascript" src="{{ asset('js/barcode.js') }} "></script>
	{!! session(['SOURCE_URL' => URL::full()]) !!}
</div>
@endsection