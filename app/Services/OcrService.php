<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class OcrService
{
    protected $apiUrl;
    protected $timeout = 30;

    public function __construct()
    {
        // Detectar ambiente (Docker vs Local)
        $this->apiUrl = $this->detectApiUrl();
    }

    /**
     * Detecta a URL correta da API OCR
     * Em Docker: http://host.docker.internal:5000/ocr (Windows/Mac)
     *            http://172.17.0.1:5000/ocr (Linux)
     * Local: http://localhost:5000/ocr
     */
    protected function detectApiUrl(): string
    {
        $customUrl = env('OCR_API_URL');
        if ($customUrl) {
            return $customUrl;
        }
        return 'https://api-ocr.zauso-dev.com.br/ocr';
    }

    /**
     * Valida documento enviando para API OCR
     */
    public function validateDocument(UploadedFile $file): array
    {
        try {
            Log::info('Iniciando validação de documento', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);

            // Fazer request multipart com a imagem e parâmetros
            $response = Http::timeout($this->timeout)
                ->attach('image', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->attach('language', 'por')
                ->attach('output_format', 'json')
                ->post($this->apiUrl);

            Log::info('Resposta da API OCR recebida', [
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                $apiData = $response->json();
                
                // Processar dados com extractData
                $extractedData = $this->extractData($apiData);
                
                return [
                    'success' => true,
                    'data' => $apiData,
                    'extracted_data' => $extractedData['extracted_fields'],
                    'is_valid' => $extractedData['is_valid'],
                    'status' => $response->status()
                ];
            }

            return [
                'success' => false,
                'error' => 'Erro na API: ' . $response->status(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao chamar API OCR', [
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    /**
     * Extrai dados estruturados da resposta OCR
     */
    public function extractData(array $ocrResponse): array
    {
        // Adaptar conforme a estrutura que sua API retorna
        return [
            'raw_response' => $ocrResponse,
            'extracted_fields' => $this->parseResponse($ocrResponse),
            'is_valid' => $this->validateExtractedData($ocrResponse)
        ];
    }

    /**
     * Parse da resposta OCR
     */
    private function parseResponse(array $response): array
    {
        // Adaptar conforme retorno real da API
        return [
            'text' => $response['text'] ?? null,
            'filename' => $response['filename'] ?? null,
            'language' => $response['language'] ?? null,
            'preprocess' => $response['preprocess'] ?? false,
            'psm' => $response['psm'] ?? null,
            'word_count' => $response['word_count'] ?? 0,
            'text_length' => $response['text_length'] ?? 0,
            'timestamp' => $response['timestamp'] ?? null,
            'fields' => $this->extractFields($response['text'] ?? '')
        ];
    }

    /**
     * Extrai campos específicos do texto OCR
     */
    private function extractFields(string $text): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        return [
            'raw_lines' => $lines,
            'total_lines' => count($lines),
            'has_content' => !empty($lines) && strlen($text) > 10,
            'document_type' => $this->detectDocumentType($text)
        ];
    }

    /**
     * Detecta tipo de documento pelo texto
     */
    private function detectDocumentType(string $text): ?string
    {
        if (stripos($text, 'REGISTRO DE IDENTIDADE') !== false || stripos($text, 'RG') !== false) {
            return 'RG';
        } elseif (stripos($text, 'CARTEIRA NACIONAL') !== false || stripos($text, 'CNH') !== false) {
            return 'CNH';
        } elseif (stripos($text, 'CPF') !== false) {
            return 'CPF';
        } elseif (stripos($text, 'PASSPORT') !== false) {
            return 'PASSPORT';
        }

        return null;
    }

    /**
     * Valida se os dados extraídos são válidos
     */
    private function validateExtractedData(array $response): bool
    {
        // Implementar lógica de validação conforme necessário
        return isset($response['text']) && !empty($response['text']);
    }

    /**
     * Define URL da API
     */
    public function setApiUrl(string $url): self
    {
        $this->apiUrl = $url;
        return $this;
    }

    /**
     * Define timeout
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }
}
