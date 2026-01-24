@extends('layouts.default')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6">Validar Documento</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="document_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Documento
                </label>
                <select name="document_type" id="document_type" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Selecione...</option>
                    <option value="RG">RG</option>
                    <option value="CNH">CNH</option>
                    <option value="CPF">CPF</option>
                    <option value="PASSPORT">Passaporte</option>
                </select>
                @error('document_type')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    Imagem do Documento
                </label>
                <input type="file" name="image" id="image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPEG, PNG, GIF (máx. 5MB)</p>
                @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">
                Enviar para Validação
            </button>

            <a href="{{ route('documents.index') }}" class="block text-center text-blue-600 hover:text-blue-700 text-sm">
                Ver Histórico
            </a>
        </form>
    </div>
</div>
@endsection
