<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\Book;
use App\Models\User;


Route::middleware(['auth'])->group(function() {
    Route::get('/', function () {


        // Example of creating a new Book instance
        // $post = new \App\Models\Book();
        // $post->title = 'Laravel 10';
        // $post->body = 'Laravel 12 is the latest version of the Laravel framework.';
        // $post->save();

        /// Example create method usage
        // $post = \App\Models\Book::create([
        //     'title' => 'Laravel 10',
        //     'body' => 'Laravel 12 is the latest version of the Laravel framework.'
        // ]);


        // ------------------------

        // $book = Book::find(1);

        //$book = Book::where('id', 1)->first();

        // $book = Book::where('title', 'LIKE', '%laravel%')->get();
        // dd($book);


        // -------------------------

        // $input = [
        //     'title' => "Meu novo titulo vindo do input", 
        //     'body' => 'Meu novo body vindo do input'
        // ];

        // $book = Book::find(2);
        // $book->fill($input);
        // $book->save();


        // -------------------------
    
        // $book = Book::find(3);
        // $book->delete();

        // -------------------------

        // 1 -> 1
        // $user = User::find(1);
        // $user = User::with('profile')->find(1);
        // $user->profile()->create([
        //     'type' => 'PJ', 
        //     'document_number' => '9238748234'
        // ]);
        // dd($user);


        // 1 -> N


        return view('home');
    });

    Route::get('admin/usuarios', [UserController::class, 'index'])->name('users.index');
    Route::get('admin/usuarios/cadastrar', [UserController::class, 'create'])->name('users.store');
    Route::post('admin/usuarios/cadastrar', [UserController::class, 'store'])->name('users.create');
    Route::get('admin/usuarios/{id}', [UserController::class, 'show']);

});
