@extends('layouts.default')
@section('page-title', 'Editar Usuário')
@php 
$breadcrumbs = [
    ['label' => 'Lista de Usuários', 'route' => route('users.index')]
];
@endphp

@section("content")
  @session('status')
        <div class="alert alert-success">
            {{ $value }}
        </div>
  @endsession
  
  <form 
        action="{{ route('users.update', $user->id) }}" 
        method="POST">
        
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Nome</label>
            <input 
                type="text" 
                class="form-control @error('name') is-invalid @enderror" 
                name='name' 
                value="{{ old('name') ?? $user->name }}">

            @error('name')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input 
                type="text"
                class="form-control @error('email') is-invalid @enderror"  
                name='email' 
                value="{{ old('email') ?? $user->email }}">

             @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror    
        </div>

        <div class="mb-3">
            <label class="form-label">Senha</label>
            <input 
                type="password" 
                class="form-control @error('password') is-invalid @enderror"  
                name='password'>
            
            @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror       
        </div>

        <div>
            <button class="btn btn-primary" type="submit">Editar</button>
        </div>
  </form>
@endsection


