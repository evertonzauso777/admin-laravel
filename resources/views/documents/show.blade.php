@extends('layouts.default')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold mb-6">Resultado da Validação</h2>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold mb-2">Status</h3>
                <div class="flex items-center">
                    @if ($validation->validation_status === 'validated')
                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold">
                            ✓ Validado com Sucesso
                        </span>
                    @elseif ($validation->validation_status === 'failed')
                        <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-semibold">
                            ⚠ Validação Falhou
                        </span>
                    @elseif ($validation->validation_status === 'error')
                        <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded-full font-semibold">
                            ✗ Erro no Processamento
                        </span>
                    @else
                        <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold">
                            ⏳ Pendente
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-600">Tipo de Documento</p>
                    <p class="font-semibold">{{ $validation->document_type }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Data da Validação</p>
                    <p class="font-semibold">{{ $validation->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Imagem do Documento -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Documento Enviado</h3>
            <img src="{{ Storage::url($validation->image_path) }}" alt="Documento" class="max-w-full rounded-md">
        </div>

        <!-- Dados Extraídos -->
        @if ($validation->extracted_data)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Dados Extraídos</h3>
                <div class="bg-gray-50 p-4 rounded-md">
                    <pre class="text-sm overflow-auto">{{ json_encode($validation->extracted_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif

        <!-- Resposta Completa da API -->
        @if ($validation->ocr_response)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Resposta Completa da API OCR</h3>
                <details class="bg-gray-50 p-4 rounded-md">
                    <summary class="cursor-pointer font-semibold text-blue-600 hover:text-blue-700">
                        Ver Detalhes Técnicos
                    </summary>
                    <pre class="text-xs overflow-auto mt-4">{{ json_encode($validation->ocr_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            </div>
        @endif

        <!-- Mensagem de Erro -->
        @if ($validation->error_message)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-red-800 mb-2">Erro</h3>
                <p class="text-red-700">{{ $validation->error_message }}</p>
            </div>
        @endif

        <!-- Ações -->
        <div class="flex gap-2">
            <a href="{{ route('documents.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                Voltar ao Histórico
            </a>
            <a href="{{ route('documents.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                Validar Outro Documento
            </a>
            <form action="{{ route('documents.destroy', $validation) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    Deletar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
