<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configurable extends Model
{
	/**
	 * Enabling soft deletes for configurables.
	 *
	 */
	use SoftDeletes;
	protected $dates = ['deleted_at'];
    	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'configurables';
	/**
	 * Fields relationship
	 */
	public function fields()
	{
	  return $this->belongsToMany('App\Models\Field', 'configurable_fields', 'configurable_id', 'field_id');
	}
	/**
	 * confield relationship
	 */
    public function confield()
    {
        return $this->hasMany('App\Models\ConField');
    }
	/**
	 * value given for config
	 */
	public function conf($id)
	{
		return $this->confield()->where('field_id', $id)->first();
	}
	/**
	* Return Configurable ID given the name
	* @param $name the name of the module
	*/
	public static function idByRoute($name = NULL)
	{
		if($name)
		{
			if($name)
			{
				try 
				{
					$conf = Configurable::where('route', $name)->orderBy('name', 'asc')->firstOrFail();
					return $conf->id;
				} 
				catch (ModelNotFoundException $e) 
				{
					Log::error("The Configurable with route ` $name ` does not exist:  ". $e->getMessage());
					//TODO: send email?
					return null;
				}
			}
		}
		else{
			return null;
		}
	}
	/**
	* Return Configurable ID given the name
	* @param $name the name of the module
	*/
	public static function idByName($name = NULL)
	{
		if($name)
		{
			if($name)
			{
				try 
				{
					$conf = Configurable::where('name', $name)->orderBy('name', 'asc')->firstOrFail();
					return $conf->id;
				} 
				catch (ModelNotFoundException $e) 
				{
					Log::error("The Configurable ` $name ` does not exist:  ". $e->getMessage());
					//TODO: send email?
					return null;
				}
			}
		}
		else{
			return null;
		}
	}
}