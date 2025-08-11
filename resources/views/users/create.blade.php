@extends('layouts.default')

@section("content")
 <h1>Cadastro de usuários</h1>
  <a href="{{ route('users.index') }}">Listar usuários</a>
  <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        {{ $errors->any() }}
        @if($errors->any())
            @foreach($errors->all() as $error)
                <div> 
                    {{  $error }}    
                </div>
            @endforeach
        @endif
        <div>
            <label for=''>Nome</label>
            <input type="text" name='name' value="{{ old('name') }}">
        </div>

        <div>
            <label for=''>Email</label>
            <input type="text" name='email' value="{{ old('name') }}">
        </div>

        <div>
            <label for=''>Senha</label>
            <input type="password" name='password'>
        </div>

           <div>
            <label for=''>Avatar</label>
            <input type="file" name="avatar">
        </div>

        <div>
            <button type="submit">Cadastrar</button>
        </div>
  </form>
@endsection


