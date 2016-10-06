@extends("layout")
@section("content")
<div>
    <ol class="breadcrumb">
      <li><a href="{{{URL::route('user.home')}}}">{{ trans('messages.home') }}</a></li>
      <li><a href="{{ URL::route('control.resultsIndex') }}">{{ trans_choice('messages.controlresults',2) }}</a></li>
      <li class="active">{{trans('messages.show-results')}}</li>
    </ol>
</div>
@if (Session::has('message'))
    <div class="alert alert-info">{{ Session::get('message') }}</div>
@endif
<div class="panel panel-primary">
    <div class="panel-heading ">
        <span class="glyphicon glyphicon-adjust"></span>
        {{ trans('messages.list-results') .'  '. $control->name  }}
    </div>
    <div class="panel-body">
        <table class="table table-striped table-hover table-condensed search-table">
            <thead>
                <tr>
                    <th> {{ trans('messages.lot-number') }} </th>
                    <th> {{ trans_choice('messages.control', 1) }} </th>
                    <th> {{ trans('messages.test-results') }} </th>
                    <th> {{ trans('messages.performed-by') }} </th>
                    <th>{{ trans_choice('messages.created-at', 1) }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($control->controlTests as $controlResult)
                <tr>
                    <td>{{$controlResult->lot->lot_no}}</td>
                    <td>{{$controlResult->control->name}}</td>
                    <td>{{implode(', ', $controlResult->controlResults->lists('results'))}}</td>
                    <td>{{$controlResult->performed_by}}</td>
                    <td>{{$controlResult->created_at}}</td>
                    <td>
                        <a class="btn btn-sm btn-info" href="{{ URL::to("controlresults/" . $controlResult->id . "/resultsEdit") }}" >
                            <span class="glyphicon glyphicon-edit"></span>
                            {{ trans('messages.edit') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ Session::put('SOURCE_URL', URL::full()) }}
    </div>
</div>
@stop