<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QcTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() 
        {
                Schema::create('lots', function(Blueprint $table)
                {
                	$table->increments('id')->unsigned();
                	$table->string('lot_no', 100)->unique();
                	$table->string('description', 400)->nullable();
                        $table->date('expiry')->nullable();

                	$table->softDeletes();
                        $table->timestamps();
                });

                Schema::create('controls', function(Blueprint $table)
                {
                	$table->increments('id')->unsigned();
                	$table->string('name', 100)->unique();
                	$table->string('description', 400)->nullable();
                        $table->integer('instrument_id')->unsigned();

                	$table->timestamps();
                        $table->softDeletes();

                        $table->foreign('instrument_id')->references('id')->on('instruments');
                });

                Schema::create('control_measures', function(Blueprint $table)
                {
                	$table->increments('id');
                	$table->string('name');
                	$table->string('unit');
                	$table->integer('control_id')->unsigned();
                        $table->integer('control_measure_type_id')->unsigned();

                        $table->softDeletes();
                        $table->timestamps();

                        $table->foreign('control_measure_type_id')->references('id')->on('measure_types');
                	$table->foreign('control_id')->references('id')->on('controls');
                });

                Schema::create('control_measure_ranges', function(Blueprint $table){
                	$table->increments('id');
                	$table->decimal('upper_range', 6, 2)->nullable();
                	$table->decimal('lower_range', 6, 2)->nullable();
                	$table->string('alphanumeric', '100')->nullable();
                	$table->integer('control_measure_id')->unsigned();
                        
                        $table->softDeletes();
                        $table->timestamps();

                	$table->foreign('control_measure_id')->references('id')->on('control_measures');
                });

                Schema::create('control_tests', function(Blueprint $table){
                        $table->increments('id');
                        $table->integer('control_id')->unsigned();
                        $table->integer('lot_id')->unsigned();
                        $table->string('performed_by', 100)->nullable();
                        $table->integer('user_id')->unsigned();

                        $table->timestamps();

                        $table->foreign('control_id')->references('id')->on('controls');
                        $table->foreign('lot_id')->references('id')->on('lots');
                        $table->foreign('user_id')->references('id')->on('users');
                });

                Schema::create('control_results', function(Blueprint $table){
                	$table->increments('id');
                	$table->string('results');
                	$table->integer('control_measure_id')->unsigned();
                        $table->integer('control_test_id')->unsigned();
                        $table->integer('user_id')->unsigned();

                        $table->timestamps();

                        $table->foreign('control_test_id')->references('id')->on('control_tests');
                	$table->foreign('control_measure_id')->references('id')->on('control_measures');
                        $table->foreign('user_id')->references('id')->on('users');
                });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('control_results');
                Schema::dropIfExists('control_tests');
		Schema::dropIfExists('control_measure_ranges');
		Schema::dropIfExists('control_measures');
		Schema::dropIfExists('controls');
		Schema::dropIfExists('lots');
	}
}
