@extends('layouts.default')

@section("content")
    <h1 class="title">Olá</h1>
    <p>{{ $greeting }}</p>
    <ul>
        @foreach ($users as $user)
            <li>{{ $user->name }} (ID: {{ $user->id }})</li>
        @endforeach

        {{ $users->links() }}
@endsection
