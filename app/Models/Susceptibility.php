<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Susceptibility extends Model
{
	/**
	 * Enabling soft deletes for drug susceptibility.
	 *
	 */
	use SoftDeletes;
	protected $dates = ['deleted_at'];
    	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'drug_susceptibility';
	/**
	 * User relationship
	 */
	public function user()
	{
	  return $this->belongsTo('User', 'user_id');
	}
	/**
	 * Test relationship
	 */
	public function test()
    {
        return $this->hasOne('Test', 'test_id');
    }
    /*
    *	Function to return drug susceptibility given testId, organismId and drugId
    *
    */
    public static function getDrugSusceptibility($test_id, $organism_id, $drug_id){
    	$susceptibility = Susceptibility::where('test_id', $test_id)
    									->where('organism_id', $organism_id)
    									->where('drug_id', $drug_id)
    									->first();
    	return $susceptibility;
    }
}