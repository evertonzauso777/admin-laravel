<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {

      $users = User::paginate(2);  

      return view('users.index', [
        'greeting' => 'Hello World',
        'users' => $users
      ]);
    }

    public function show(User $id)
    {
        return view('users.show', [
            'user' => $id
        ]);
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request) 
    {

        $input = $request->validate([
          'name' => 'required',
          'email' => 'required|email',
          'password' => 'required|min:3',
          'avatar' => 'file'
        ]);

        if (!empty($input['avatar']) && $input['avatar']->isValid()) {
            $input['avatar']->store();
        }

        

        User::create($input);

        return redirect()->back();
    }
    
}
