/**
 * EXEMPLO: Integração via API REST (JSON)
 * 
 * Se preferir oferecer a validação como API para Frontend
 * ou outras aplicações, use este exemplo.
 */

// routes/api.php - Adicione estas rotas

use App\Http\Controllers\Api\DocumentValidationApiController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/documents/validate', [DocumentValidationApiController::class, 'validate']);
    Route::get('/documents', [DocumentValidationApiController::class, 'index']);
    Route::get('/documents/{validation}', [DocumentValidationApiController::class, 'show']);
    Route::delete('/documents/{validation}', [DocumentValidationApiController::class, 'destroy']);
});

---

// app/Http/Controllers/Api/DocumentValidationApiController.php

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentValidation;
use App\Services\OcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentValidationApiController extends Controller
{
    protected $ocrService;

    public function __construct(OcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * POST /api/documents/validate
     * 
     * Exemplo de requisição:
     * {
     *   "document_type": "RG",
     *   "image": "base64_image_data"
     * }
     */
    public function validate(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:RG,CNH,CPF,PASSPORT',
            'image' => 'required|string' // Base64
        ]);

        try {
            // Decodificar base64
            $imageData = base64_decode($validated['image']);
            $filename = time() . '.jpg';
            $path = storage_path('app/temp/' . $filename);

            file_put_contents($path, $imageData);

            // Criar arquivo uploadado
            $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                $path,
                $filename,
                'image/jpeg'
            );

            // Validar
            $ocrResult = $this->ocrService->validateDocument($file);

            if (!$ocrResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $ocrResult['error']
                ], 422);
            }

            // Salvar registro
            $validation = DocumentValidation::create([
                'user_id' => auth()->id(),
                'document_type' => $validated['document_type'],
                'image_path' => 'api/' . $filename,
                'ocr_response' => $ocrResult['data'],
                'validation_status' => 'validated'
            ]);

            // Limpar arquivo temporário
            @unlink($path);

            return response()->json([
                'success' => true,
                'data' => $validation->load('user')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/documents
     */
    public function index()
    {
        $validations = auth()->user()
            ->documentValidations()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $validations
        ]);
    }

    /**
     * GET /api/documents/{validation}
     */
    public function show(DocumentValidation $validation)
    {
        if ($validation->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $validation
        ]);
    }

    /**
     * DELETE /api/documents/{validation}
     */
    public function destroy(DocumentValidation $validation)
    {
        if ($validation->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        if (Storage::exists($validation->image_path)) {
            Storage::delete($validation->image_path);
        }

        $validation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Documento removido'
        ]);
    }
}

---

// EXEMPLOS DE USO COM CURL

# 1. Validar documento (com Base64)
curl -X POST http://localhost:8000/api/documents/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "document_type": "RG",
    "image": "'$(base64 -w0 /F:/RG.PNG)'"
  }'

# 2. Listar documentos
curl -X GET http://localhost:8000/api/documents \
  -H "Authorization: Bearer YOUR_TOKEN"

# 3. Ver detalhes
curl -X GET http://localhost:8000/api/documents/1 \
  -H "Authorization: Bearer YOUR_TOKEN"

# 4. Deletar
curl -X DELETE http://localhost:8000/api/documents/1 \
  -H "Authorization: Bearer YOUR_TOKEN"

---

// EXEMPLO COM JAVASCRIPT/FETCH

async function validateDocument(documentType, imageFile) {
  const reader = new FileReader();

  return new Promise((resolve, reject) => {
    reader.onload = async (e) => {
      const base64 = e.target.result.split(',')[1];

      try {
        const response = await fetch('/api/documents/validate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content
          },
          body: JSON.stringify({
            document_type: documentType,
            image: base64
          })
        });

        const data = await response.json();
        resolve(data);
      } catch (error) {
        reject(error);
      }
    };

    reader.readAsDataURL(imageFile);
  });
}

// Uso:
const input = document.getElementById('image');
const result = await validateDocument('RG', input.files[0]);
console.log(result);

---

// EXEMPLO COM AXIOS (Vue/React)

async validateDocument(documentType, imageFile) {
  const reader = new FileReader();

  return new Promise((resolve, reject) => {
    reader.onload = async (e) => {
      const base64 = e.target.result.split(',')[1];

      try {
        const response = await this.$axios.post('/api/documents/validate', {
          document_type: documentType,
          image: base64
        });

        resolve(response.data);
      } catch (error) {
        reject(error);
      }
    };

    reader.readAsDataURL(imageFile);
  });
}
