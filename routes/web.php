<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentValidationController;
use App\Models\Book;
use App\Models\User;


Route::middleware(['auth'])->group(function() {
    Route::get('/', function () {
        return view('home');
    })->name('home');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users/create', [UserController::class, 'store'])->name('users.store');

    Route::get('/users/{user}', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Rotas para Validação de Documentos
    Route::prefix('documents')->group(function() {
        Route::get('/', [DocumentValidationController::class, 'index'])->name('documents.index');
        Route::get('/create', [DocumentValidationController::class, 'create'])->name('documents.create');
        Route::post('/', [DocumentValidationController::class, 'store'])->name('documents.store');
        Route::get('/{validation}', [DocumentValidationController::class, 'show'])->name('documents.show');
        Route::delete('/{validation}', [DocumentValidationController::class, 'destroy'])->name('documents.destroy');
    });

    Route::get('/react', function () {
        return view('react-embed');
    })->name('react.embed');

});
