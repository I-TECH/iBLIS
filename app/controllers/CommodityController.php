<?php

class CommodityController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$commodities = InventoryCommodity::all();
		return View::make('inventory.commodityList')->with('commodities', $commodities);
	}


	public function create()
	{
		$metrics= Metric::orderBy('name', 'ASC')->lists('name', 'id');
		return View::make('inventory.commodities')->with('metrics', $metrics);
	}

	public function store()
	{
		//
		$rules = array(
			'commodity' => 'required|unique:inventory_commodities,name');
		$validator = Validator::make(Input::all(), $rules);

		if ($validator->fails()) {
			return Redirect::back()->withErrors($validator);
		} else {
			// store
			$commodity = new InventoryCommodity;
			$commodity->name= Input::get('commodity');
			$commodity->description= Input::get('description');
			$commodity->inventory_metrics_id= Input::get('unit-of-issue');
			$commodity->unit_price= Input::get('unit-price');
			$commodity->item_code = Input::get('item-code');
			$commodity->storage_req = Input::get('storage-req');
			$commodity->min_level = Input::get('min-level');
			$commodity->max_level = Input::get('max-level');

			try{
				$commodity->save();
				return Redirect::route('inventory.commodityList')
					->with('message', trans('messages.success-creating-commodity'));
			}catch(QueryException $e){
				Log::error($e);
			}
		}
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$metrics= Metric::orderBy('name', 'ASC')->lists('name', 'id');
		$commodity = InventoryCommodity::find($id);

		//Open the Edit View and pass to it the $patient
		return View::make('inventory.editCommodities')->with('metrics', $metrics)->with('commodity', $commodity);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//Validate
		$rules = array('commodity' => 'required');
		$validator = Validator::make(Input::all(), $rules);

		// process the login
		if ($validator->fails()) {
			return Redirect::back()->withErrors($validator)->withInput(Input::except('password'));
		} else {
		// Update
			$commodity = InventoryCommodity::find($id);
			$commodity->name= Input::get('commodity');
			$commodity->description= Input::get('description');
			$commodity->inventory_metrics_id= Input::get('unit_of_issue');
			$commodity->unit_price= Input::get('unit_price');
			$commodity->item_code= Input::get('item_code');
			$commodity->storage_req= Input::get('storage_req');
			$commodity->min_level= Input::get('min_level');
			$commodity->max_level= Input::get('max_level');

			$commodity->save();

			$url = Session::get('SOURCE_URL');

			return Redirect::to($url)
					->with('message', trans('messages.success-updating-commodity')) ->with('activecommodity', $commodity ->id);
		}
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
