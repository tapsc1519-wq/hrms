<?php

use App\Http\Controllers\Api\AgentCommandController;
use App\Http\Controllers\Api\AgentInventoryController;
use App\Http\Controllers\Api\ErpIdentityController;
use App\Http\Controllers\Api\ErpUserController;
use App\Http\Middleware\AgentTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('v1/agent/check-in', [AgentInventoryController::class, 'checkIn'])
    ->middleware([AgentTokenMiddleware::class, 'throttle:120,1']);
Route::get('v1/agent/commands', [AgentCommandController::class, 'poll'])
    ->middleware([AgentTokenMiddleware::class, 'throttle:120,1']);
Route::post('v1/agent/commands/{commandUuid}/result', [AgentCommandController::class, 'result'])
    ->middleware([AgentTokenMiddleware::class, 'throttle:120,1']);
Route::post('v1/products/erp/authenticate', ErpIdentityController::class)
    ->middleware('throttle:10,1');
Route::post('v1/products/erp/users', [ErpUserController::class, 'store'])
    ->middleware('throttle:30,1');
Route::put('v1/products/erp/users/password', [ErpUserController::class, 'updatePassword'])
    ->middleware('throttle:10,1');
