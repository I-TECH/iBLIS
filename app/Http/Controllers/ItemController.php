<?php namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\Barcode;
use Response;
use Session;
use Auth;
use Lang;

class ItemController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//List all items
		$items =Item::orderBy('name', 'ASC')->get();
		//	Barcode
		$barcode = Barcode::first();
		//Load the view and pass the items
		return view('inventory.item.index')->with('items', $items)->with('barcode', $barcode);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//Create Item
		return view('inventory.item.create');
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(ItemRequest $request)
	{
		//store
		$item = new Item;
		$item->name = Input::get('name');
        $item->unit = Input::get('unit');
        $item->remarks = Input::get('remarks');
        $item->min_level = Input::get('min_level');
        $item->max_level = Input::get('max_level');
        $item->storage_req = Input::get('storage_req');

		$item->user_id = Auth::user()->id;
		try{
			$item->save();
			$url = session('SOURCE_URL');
        
        	return redirect()->to($url)
				->with('message', trans('messages.record-successfully-saved')) ->with('activeitem', $item ->id);
		}catch(QueryException $e){
			\Log::error($e);
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
		//show a Item
		$item =Item::find($id);
		//	Barcode
		$barcode = Barcode::first();
		//show the view and pass the $item to it
		return view('inventory.item.show')->with('item', $item)->with('barcode', $barcode);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//Get the Item
		$item =Item::find($id);

		//Open the Edit View and pass to it the $item
		return view('inventory.item.edit')->with('item', $item);
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//store
		$item = Item::find($id);
		$item->name = Input::get('name');
        $item->unit = Input::get('unit');
        $item->remarks = Input::get('remarks');
        $item->min_level = Input::get('min_level');
        $item->max_level = Input::get('max_level');
        $item->storage_req = Input::get('storage_req');

		$item->user_id = Auth::user()->id;
		$item->save();

		// redirect
		$url = session('SOURCE_URL');
        
        return redirect()->to($url)
			->with('message', trans('messages.record-successfully-updated')) ->with('activeitem', $item ->id);
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
	/**
	 * Remove the specified resource from storage (soft delete).
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function delete($id)
	{
		//Soft delete the Item
		$item =Item::find($id);		
		$url = session('SOURCE_URL'); 
		if(count($item->stocks)>0 || count($item->requests)>0)
		{
			return redirect()->to($url)->with('message', trans('messages.failure-delete-record'));
		}
		else
		{
			$item->delete();
        	return redirect()->to($url)->with('message', trans('messages.record-successfully-deleted'));
        }
	}
}
