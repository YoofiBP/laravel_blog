<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResources(['posts' => PostController::class]);

    Route::prefix('users')->group(function () {
        Route::post('/login', [UserController::class, 'login'])->name("login")->withoutMiddleware(['auth:sanctum']);
        Route::post('/signup',[UserController::class, 'signUp'])->name("user.signup")->withoutMiddleware(['auth:sanctum']);
        Route::get('/me', [UserController::class, 'getUser'])->name('user.getUser');
        Route::delete('/', [UserController::class, 'deleteMe'])->name('user.deleteMe');
        Route::post('/logout', [UserController::class, 'logout'])->name('logout');
        Route::get('/', [UserController::class, 'index'])->name('user.index');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('user.destroy');
        Route::patch('/{user}', [UserController::class, 'update'])->name('user.update');
    });

    Route::post('/posts/{post}/comments', [PostController::class, 'saveComment'])->name('comments.store');
    Route::get('/posts/{post}/comments', [PostController::class, 'viewComments'])->name('comments.index');
    Route::delete('/posts/{post}/comments/{comment}', [PostController::class, 'deleteComment'])->name('comments.destroy');
});

Route::fallback(function () {
    return response()->json(["error" => "Whoops there's nothing to see here"], 404);
});
