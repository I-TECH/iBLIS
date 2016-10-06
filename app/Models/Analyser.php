<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Analyser extends Model
{
	/**
	 * Enabling soft deletes for analysers.
	 *
	 */
	use SoftDeletes;
	protected $dates = ['deleted_at'];
    	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'analysers';
	/**
	 * TestCategory relationship
	 */
	public function testCategory()
	{
	  return $this->belongsTo('App\Models\TestCategory', 'test_category_id');
	}
	/*
	*	Constants for feed source RS232,TCP/IP, MSACCESS,HTTP,TEXT
	*/
	const RS232 = 0;
	const TCPIP = 1;
	const MSACCESS = 2;
	const HTTP = 3;
	const TEXT = 4;
	/*
	*	Constants for communication type
	*/
	const UNIDIRECTIONAL = 0;
	const BIDIRECTIONAL = 1;
	/*
	*	Return corresponding comm-type
	*/
	public function feedsource()
	{
		$feed_sources = ['RS232', 'TCP/IP', 'MSACCESS', 'HTTP', 'TEXT'];
		return $feed_sources[$this->feed_source];
	}
	/*
	*	Return corresponding feed-source
	*/
	public function commtype()
	{
		$comm_types = ['Uni-directional', 'Bi-directional'];
		return $comm_types[$this->comm_type];
	}
}