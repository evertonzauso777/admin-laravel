<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{

    public function index()
    {
        $users = User::all();
        return view('users.index', [ 'users' => $users ]);
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
          'password' => 'required|min:3'
        ]);
        User::create($input);

        return redirect()->route('users.index')->with('status', 'Usuário adicionado com sucesso!');
    }


    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
         $input = $request->validate([
          'name' => 'required',
          'email' => 'required|email',
          'password' => 'exclude_if:password,null|min:6'
        ]);

        $user->fill($input);
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuário editado com sucesso!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()
            ->with('status', 'Usuário deletado com sucesso!');
    }

    // public function index()
    // {

    //   $users = User::paginate(2);  

    //   return view('users.index', [
    //     'greeting' => 'Hello World',
    //     'users' => $users
    //   ]);
    // }

    // public function show(User $id)
    // {
    //     return view('users.show', [
    //         'user' => $id
    //     ]);
    // }

    // public function create()
    // {
    //     return view('users.create');
    // }

    // public function store(Request $request) 
    // {

    //     $input = $request->validate([
    //       'name' => 'required',
    //       'email' => 'required|email',
    //       'password' => 'required|min:3',
    //       'avatar' => 'file'
    //     ]);

    //     if (!empty($input['avatar']) && $input['avatar']->isValid()) {
    //         $input['avatar']->store();
    //     }

        

    //     User::create($input);

    //     return redirect()->back();
    // }
    
}
