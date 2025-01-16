<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('isup/event', function () {
    logger(\request()->all());

    return response()->json([
        'success' => true,
        'message' => 'Event received',
    ]);
});
