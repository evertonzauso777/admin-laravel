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

        // Local
        return 'http://localhost:5000/ocr/';
    }

    /**
     * Verifica se está rodando dentro de Docker
     */
    protected function isInsideDocker(): bool
    {
        return file_exists('/.dockerenv');
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

            // Fazer request multipart com a imagem
            $response = Http::timeout($this->timeout)
                ->attach('image', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($this->apiUrl);

            Log::info('Resposta da API OCR recebida', [
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
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
                'is_docker' => $this->isInsideDocker(),
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
        // Exemplo: adaptar conforme retorno da sua API
        return [
            'text' => $response['text'] ?? null,
            'confidence' => $response['confidence'] ?? null,
            'fields' => $response['fields'] ?? []
        ];
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
