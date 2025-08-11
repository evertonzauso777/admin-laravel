@extends('layouts.default')

@section("content")
 <h1>Usuário</h1>
    <p>ID: {{$user->id}}</p>
    <p>Email: {{$user->email}}</p>
    <p>Data de criação: {{$user->created_at}}</p>
    <p>Data de atualização: {{$user->updated_at}}</p>
@endsection


