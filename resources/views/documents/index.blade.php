@extends('layouts.default')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Histórico de Validações</h2>
            <a href="{{ route('documents.create') }}" class="px-4 py-2 bg-blue-600 text-black rounded-md hover:bg-blue-700 transition">
                + Validar Novo Documento
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if ($validations->count() > 0)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Tipo</th>
                            <th scope="col">Status</th>
                            <th scope="col">Data</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($validations as $validation)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                        {{ $validation->document_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($validation->validation_status === 'validated')
                                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full">✓ Validado</span>
                                    @elseif ($validation->validation_status === 'failed')
                                        <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full">⚠ Falhou</span>
                                    @elseif ($validation->validation_status === 'error')
                                        <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded-full">✗ Erro</span>
                                    @else
                                        <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full">⏳ Pendente</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $validation->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('documents.show', $validation) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                        Ver Detalhes
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="mt-6">
                {{ $validations->links() }}
            </div>
        @else
            <div class="bg-gray-100 rounded-lg p-8 text-center">
                <p class="text-gray-600 mb-4">Nenhum documento validado ainda</p>
                <a href="{{ route('documents.create') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                    Validar Primeiro Documento
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
