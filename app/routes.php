<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
/* Routes accessible before logging in */
Route::group(array("before" => "guest"), function()
{
    /*
    |-----------------------------------------
    | API route
    |-----------------------------------------
    | Proposed route for the BLIS api, we will receive api calls 
    | from other systems from this route.
    */
    Route::post('/api/receiver', array(
        "as" => "api.receiver",
        "uses" => "InterfacerController@receiveLabRequest"
    ));

	Route::any('/', array(
	    "as" => "user.login",
	    "uses" => "UserController@loginAction"
	));
    
});

/* Routes accessible AFTER logging in */
Route::group(array("before" => "auth"), function()
{
    Route::any('/home', array(
        "as" => "user.home",
        "uses" => "UserController@homeAction"
        ));

    Route::group(array("before" => "checkPerms:manage_users"), function() {
        Route::resource('user', 'UserController');

        Route::get("/user/{id}/delete", array(
            "as"   => "user.delete",
            "uses" => "UserController@delete"
        ));
    });
    
    Route::any("/logout", array(
        "as"   => "user.logout",
        "uses" => "UserController@logoutAction"
    ));

    Route::any('/user/{id}/updateown', array(
        "as" => "user.updateOwnPassword",
        "uses" => "UserController@updateOwnPassword"
        ));

	Route::resource('patient', 'PatientController');

    Route::get("/patient/{id}/delete", array(
        "as"   => "patient.delete",
        "uses" => "PatientController@delete"
    ));

    Route::post("/patient/search", array(
        "as"   => "patient.search",
        "uses" => "PatientController@search"
    ));

    Route::any("/instrument/getresult", array(
        "as"   => "instrument.getResult",
        "uses" => "InstrumentController@getTestResult"
    ));

    Route::group(array("before" => "checkPerms:manage_test_catalog"), function()
    {
        Route::resource('specimentype', 'SpecimenTypeController');

        Route::get("/specimentype/{id}/delete", array(
            "as"   => "specimentype.delete",
            "uses" => "SpecimenTypeController@delete"
        ));

        Route::resource('testcategory', 'TestCategoryController');
        
        Route::get("/testcategory/{id}/delete", array(
            "as"   => "testcategory.delete",
            "uses" => "TestCategoryController@delete"
        ));

        Route::resource('measure', 'MeasureController');
    
        Route::get("/measure/{id}/delete", array(
            "as"   => "measure.delete",
            "uses" => "MeasureController@delete"
        ));

        Route::resource('testtype', 'TestTypeController');

        Route::get("/testtype/{id}/delete", array(
            "as"   => "testtype.delete",
            "uses" => "TestTypeController@delete"
        ));

        Route::resource('specimenrejection', 'SpecimenRejectionController');

        Route::any("/specimenrejection/{id}/delete", array(
            "as"   => "specimenrejection.delete",
            "uses" => "SpecimenRejectionController@delete"
        ));
    });

    Route::group(array("before" => "checkPerms:manage_lab_configurations"), function()
    {
        Route::resource('instrument', 'InstrumentController');

        Route::get("/instrument/{id}/delete", array(
            "as"   => "instrument.delete",
            "uses" => "InstrumentController@delete"
        ));

        Route::any("/instrument/importdriver", array(
            "as"   => "instrument.importDriver",
            "uses" => "InstrumentController@importDriver"
        ));
    });


    Route::any("/test", array(
        "as"   => "test.index",
        "uses" => "TestController@index"
    ));

    Route::post("/test/resultinterpretation", array(
    "as"   => "test.resultinterpretation",
    "uses" => "TestController@getResultInterpretation"
    ));

     Route::any("/test/{id}/receive", array(
        "before" => "checkPerms:receive_external_test",
        "as"   => "test.receive",
        "uses" => "TestController@receive"
    ));

    Route::any("/test/create/{patient?}", array(
        "before" => "checkPerms:request_test",
        "as"   => "test.create",
        "uses" => "TestController@create"
    ));

     Route::post("/test/savenewtest", array(
        "before" => "checkPerms:request_test",
        "as"   => "test.saveNewTest",
        "uses" => "TestController@saveNewTest"
    ));

     Route::post("/test/acceptspecimen", array(
        "before" => "checkPerms:accept_test_specimen",
        "as"   => "test.acceptSpecimen",
        "uses" => "TestController@accept"
    ));

     Route::get("/test/{id}/refer", array(
        "before" => "checkPerms:refer_specimens",
        "as"   => "test.refer",
        "uses" => "TestController@showRefer"
    ));

    Route::post("/test/referaction", array(
        "before" => "checkPerms:refer_specimens",
        "as"   => "test.referAction",
        "uses" => "TestController@referAction"
    ));

    Route::get("/test/{id}/reject", array(
        "before" => "checkPerms:reject_test_specimen",
        "as"   => "test.reject",
        "uses" => "TestController@reject"
    ));

    Route::post("/test/rejectaction", array(
        "before" => "checkPerms:reject_test_specimen",
        "as"   => "test.rejectAction",
        "uses" => "TestController@rejectAction"
    ));

     Route::post("/test/changespecimen", array(
        "before" => "checkPerms:change_test_specimen",
        "as"   => "test.changeSpecimenType",
        "uses" => "TestController@changeSpecimenType"
    ));

     Route::post("/test/updatespecimentype", array(
        "before" => "checkPerms:change_test_specimen",
        "as"   => "test.updateSpecimenType",
        "uses" => "TestController@updateSpecimenType"
    ));

    Route::post("/test/start", array(
        "before" => "checkPerms:start_test",
        "as"   => "test.start",
        "uses" => "TestController@start"
    ));

     Route::get("/test/{test}/enterresults", array(
        "before" => "checkPerms:enter_test_results",
        "as"   => "test.enterResults",
        "uses" => "TestController@enterResults"
    ));

    Route::get("/test/{test}/edit", array(
        "before" => "checkPerms:edit_test_results",
        "as"   => "test.edit",
        "uses" => "TestController@edit"
    ));

     Route::post("/test/{test}/saveresults", array(
        "before" => "checkPerms:edit_test_results",
        "as"   => "test.saveResults",
        "uses" => "TestController@saveResults"
    ));

    Route::get("/test/{test}/viewdetails", array(
        "as"   => "test.viewDetails",
        "uses" => "TestController@viewDetails"
    ));

    Route::any("/test/{test}/verify", array(
        "before" => "checkPerms:verify_test_results",
        "as"   => "test.verify",
        "uses" => "TestController@verify"
    ));

    Route::group(array("before" => "admin"), function()
    {
        Route::resource("permission", "PermissionController");

        Route::get("role/assign", array(
            "as"   => "role.assign",
            "uses" => "RoleController@assign"
        ));
        Route::post("role/assign", array(
            "as"   => "role.assign",
            "uses" => "RoleController@saveUserRoleAssignment"
        ));
        Route::resource("role", "RoleController");

        Route::get("/role/{id}/delete", array(
            "as"   => "role.delete",
            "uses" => "RoleController@delete"
        ));
    });

    // Check if able to manage lab configuration
    Route::group(array("before" => "checkPerms:manage_lab_configurations"), function()
    {
        Route::resource("facility", "FacilityController");

        Route::get("/facility/{id}/delete", array(
            "as"   => "facility.delete",
            "uses" => "FacilityController@delete"
        ));
    });
    

    //  Check if able to manage reports
    Route::group(array("before" => "checkPerms:view_reports"), function()
    {
        Route::any("/patientreport", array(
            "as"   => "reports.patient.index",
            "uses" => "ReportController@loadPatients"
        ));

        Route::any("/patientreport/{id}", array(
            "as" => "reports.patient.report", 
            "uses" => "ReportController@viewPatientReport"
            ));

        Route::any("/patientreport/{id}/{visit}", array(
            "as" => "reports.patient.report", 
            "uses" => "ReportController@viewPatientReport"
            ));

        Route::any("/dailylog", array(
            "as"   => "reports.daily.log",
            "uses" => "ReportController@dailyLog"
        ));
        Route::get('reports/dropdown', array(
            "as"    =>  "reports.dropdown",
            "uses"  =>  "ReportController@reportsDropdown"
        ));

        Route::any("/prevalence", array(
            "as"   => "reports.aggregate.prevalence",
            "uses" => "ReportController@prevalenceRates"
        ));

        Route::any("/counts", array(
            "as"   => "reports.aggregate.counts",
            "uses" => "ReportController@countReports"
        ));

        Route::any("/tat", array(
            "as"   => "reports.aggregate.tat",
            "uses" => "ReportController@turnaroundTime"
        ));

        Route::any("/infection", array(
            "as"   => "reports.aggregate.infection",
            "uses" => "ReportController@infectionReport"
        ));
        
        Route::any("/userstatistics", array(
            "as"   => "reports.aggregate.userStatistics",
            "uses" => "ReportController@userStatistics"
        ));
        
    });
      
        Route::post("/inventory/store_receipts", array(
            "as"   => "inventory.store_receipts",
            "uses" => "InventoryController@store_receipts"
        ));

        Route::post("/inventory/store_issues", array(
            "as"   => "inventory.store_issues",
            "uses" => "InventoryController@store_issues"
        ));
        
        Route::any("/inventory/labStockCard", array(
            "as"   => "inventory.labStockCard",
            "uses" => "InventoryController@index"
        ));
         Route::any("/inventory/receipts", array(
            "as"   => "inventory.receipts",
            "uses" => "InventoryController@receipts"
        ));
        Route::any("/inventory/issues", array(
            "as"   => "inventory.issues",
            "uses" => "InventoryController@issues"
        ));
        Route::any("/inventory/labTopup", array(
            "as"   => "inventory.labTopup",
            "uses" => "InventoryController@labTopup"
        ));
        Route::post("/inventory/store_FormLabTopup", array(
            "as"   => "inventory.store_FormLabTopup",
            "uses" => "InventoryController@store_FormLabTopup"
        ));
        Route::any("/inventory/formLabTopup", array(
            "as"   => "inventory.formLabTopup",
            "uses" => "InventoryController@formLabTopup"
        ));
        Route::any("/inventory/formStockTake", array(
            "as"   => "inventory.formStockTake",
            "uses" => "InventoryController@formStockTake"
        ));
        Route::post("/inventory/store_FormStockTake", array(
            "as"   => "inventory.store_FormStockTake",
            "uses" => "InventoryController@store_FormStockTake"
        ));
        Route::post("/inventory/store_stockTake", array(
            "as"   => "inventory.store_stockTake",
            "uses" => "InventoryController@store_stockTake"
        ));
         Route::any("/inventory/stockTakeCard", array(
            "as"   => "inventory.stockTakeCard",
            "uses" => "InventoryController@stockTakeCard"
        ));
        Route::get("/inventory/receiptsList", array(
            "as"   => "inventory.receiptsList",
            "uses" => "InventoryController@receiptsList"
        ));
        Route::any("/inventory/issuesList", array(
            "as"   => "inventory.issuesList",
            "uses" => "InventoryController@issuesList"
        ));
       // route to edit receipts
        Route::get("/inventory/{id}/editReceipts", array(
             "as"   => "inventory.editReceipts",
            "uses" => "InventoryController@editReceipts"
        )); 

        Route::post("/inventory/{id}/updateReceipts", array(
            "as"   => "inventory.updateReceipts",
            "uses" => "InventoryController@updateReceipts"
        ));

        Route::get("/inventory/{id}/editIssues", array(
             "as"   => "inventory.editIssues",
            "uses" => "InventoryController@editIssues"
        )); 
        Route::get("/inventory/{id}/editLabTopUp", array(
             "as"   => "inventory.editLabTopup",
            "uses" => "InventoryController@editLabTopUp"
        )); 
        Route::post("/inventory/{id}/updateLabTopup", array(
            "as"   => "inventory.updateLabTopup",
            "uses" => "InventoryController@updateLabTopup"
        ));
        Route::post("/inventory/{id}/updateIssuedCommodities", array(
            "as"   => "inventory.updateIssuedCommodities",
            "uses" => "InventoryController@updateIssuedCommodities"
        ));
        Route::get("/inventory/{id}/deleteReceipts", array(
            "as"   => "inventory.deleteReceipts",
            "uses" => "InventoryController@deleteReceipts"
        ));
        
        Route::get("/inventory/{id}/deleteLabTopupCommodity", array(
            "as"   => "inventory.deleteLabTopupCommodity",
            "uses" => "InventoryController@deleteLabTopupCommodity"
        ));
        Route::get("/inventory/{id}/deleteIssuedCommodity", array(
            "as"   => "inventory.deleteIssuedCommodity",
            "uses" => "InventoryController@deleteIssuedCommodity"
        ));
        Route::get('commodity/dropdown', array(
            "as"    =>  "commodity.dropdown",
            "uses"  =>  "InventoryController@commodityDropdown"
        ));
        Route::resource('commodity', 'CommodityController');

        Route::get("/inventory/commodityList", array(
            "as"   => "inventory.commodityList",
            "uses" => "CommodityController@index"
        ));

        Route::resource('suppliers', 'SuppliersController');

        Route::get("/inventory/suppliersList", array(
            "as"   => "inventory.suppliersList",
            "uses" => "SuppliersController@index"
        ));

        Route::resource('metrics', 'MetricsController');

        Route::get("/inventory/metricsList", array(
            "as"   => "inventory.metricsList",
            "uses" => "MetricsController@index"
        ));
    
});
