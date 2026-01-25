<?php

namespace App\Http\Controllers;

use App\Models\DocumentValidation;
use App\Models\User;
use App\Services\OcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentValidationController extends Controller
{
    protected $ocrService;

    public function __construct(OcrService $ocrService)
    {
        $this->ocrService = $ocrService;
        //$this->middleware('auth'); // Autenticação obrigatória
    }

    /**
     * Exibe formulário para envio de documento
     */
    public function create()
    {
        return view('documents.create');
    }

    /**
     * Processa upload e validação do documento
     */
    public function store(Request $request)
    {
        // Validações
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
            'document_type' => 'required|in:RG,CNH,CPF,PASSPORT'
        ]);

        try {
            // Armazenar arquivo
            $file = $request->file('image');
            $path = Storage::disk('public')->put('documents', $file);

            // Criar registro pendente
            $validation = DocumentValidation::create([
                'user_id' => auth()->id(),
                'document_type' => $validated['document_type'],
                'image_path' => $path,
                'validation_status' => 'pending'
            ]);

            // Chamar API OCR
            $ocrResult = $this->ocrService->validateDocument($file);

            if ($ocrResult['success']) {
                // Atualizar com resposta
                $validation->update([
                    'ocr_response' => $ocrResult['data'],
                    'extracted_data' => $ocrResult['extracted_data'],
                    'validation_status' => $ocrResult['is_valid'] ? 'validated' : 'failed'
                ]);

                Log::info('Documento validado com sucesso', [
                    'validation_id' => $validation->id,
                    'user_id' => auth()->id()
                ]);

                return redirect()->route('documents.show', $validation)
                    ->with('success', 'Documento validado com sucesso!');
            } else {
                // Erro na API
                $validation->update([
                    'validation_status' => 'error',
                    'error_message' => $ocrResult['error']
                ]);

                Log::warning('Erro na validação OCR', [
                    'validation_id' => $validation->id,
                    'error' => $ocrResult['error']
                ]);

                return back()
                    ->with('error', 'Erro ao processar documento: ' . $ocrResult['error'])
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Erro geral na validação de documento', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->with('error', 'Erro ao processar documento')
                ->withInput();
        }
    }

    /**
     * Exibe resultado da validação
     */
    public function show(DocumentValidation $validation)
    {
        // Verificar se o documento pertence ao usuário autenticado
        if ($validation->user_id !== auth()->id()) {
            abort(403);
        }

        return view('documents.show', compact('validation'));
    }

    /**
     * Lista histórico de validações do usuário
     */
    public function index()
    {
        $validations = auth()->user()
            ->documentValidations()
            ->latest()
            ->paginate(10);

        return view('documents.index', compact('validations'));
    }

    /**
     * Deletar documento
     */
    public function destroy(DocumentValidation $validation)
    {
        if ($validation->user_id !== auth()->id()) {
            abort(403);
        }

        // Deletar arquivo
        if (Storage::disk('public')->exists($validation->image_path)) {
            Storage::disk('public')->delete($validation->image_path);
        }

        $validation->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Documento removido com sucesso!');
    }
}
