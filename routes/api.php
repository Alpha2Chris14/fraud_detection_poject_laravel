<?php

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::fallback(function(){
    return response()->json([
        'message' => 'Page Not Found. If error persists, contact info@website.com'], 404);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
   Route::prefix('fraud')->group(function () {
       Route::post("store",[ReportController::class,'store']);
       Route::post("checkAccount",[ReportController::class,'checkAccount']);
   }); 
   Route::post("threshold",[ReportController::class,'setBankThreshold']);
   Route::get("banks",[ReportController::class,'banks']);
   Route::get("getReports",[ReportController::class,"getReportsForBank"]);
   Route::get("approve",[ReportController::class,'approve']);
   Route::get("download-excel",[ReportController::class,'downloadExcel']);
});
