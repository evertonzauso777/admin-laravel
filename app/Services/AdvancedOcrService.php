<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

/**
 * Serviço avançado com validações específicas
 * Estenda OcrService para customizações mais elaboradas
 */
class AdvancedOcrService extends OcrService
{
    /**
     * Valida RG especificamente
     */
    public function validateRG(UploadedFile $file): array
    {
        $result = $this->validateDocument($file);

        if (!$result['success']) {
            return $result;
        }

        $data = $result['data'];

        // Validações específicas para RG
        $validation = [
            'success' => true,
            'errors' => [],
            'data' => $data
        ];

        // Verificar se tem número de RG
        if (empty($data['number'] ?? null)) {
            $validation['errors'][] = 'Número de RG não encontrado';
            $validation['success'] = false;
        }

        // Verificar se tem data de emissão
        if (empty($data['issued_date'] ?? null)) {
            $validation['errors'][] = 'Data de emissão não encontrada';
            $validation['success'] = false;
        }

        // Verificar órgão emissor
        if (empty($data['issuer'] ?? null)) {
            $validation['errors'][] = 'Órgão emissor não encontrado';
            $validation['success'] = false;
        }

        return $validation;
    }

    /**
     * Valida CPF especificamente
     */
    public function validateCPF(UploadedFile $file): array
    {
        $result = $this->validateDocument($file);

        if (!$result['success']) {
            return $result;
        }

        $data = $result['data'];
        $cpf = $data['cpf'] ?? null;

        // Validar formato CPF
        if (!$this->isValidCPFFormat($cpf)) {
            return [
                'success' => false,
                'error' => 'CPF inválido ou não encontrado',
                'data' => $data
            ];
        }

        // Verificar dígitos verificadores
        if (!$this->isValidCPFChecksum($cpf)) {
            return [
                'success' => false,
                'error' => 'CPF inválido (dígitos verificadores incorretos)',
                'data' => $data
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'cpf' => $cpf
        ];
    }

    /**
     * Valida CNH especificamente
     */
    public function validateCNH(UploadedFile $file): array
    {
        $result = $this->validateDocument($file);

        if (!$result['success']) {
            return $result;
        }

        $data = $result['data'];

        $validation = [
            'success' => true,
            'errors' => [],
            'data' => $data
        ];

        // Validações específicas para CNH
        if (empty($data['number'] ?? null)) {
            $validation['errors'][] = 'Número de CNH não encontrado';
            $validation['success'] = false;
        }

        if (empty($data['validity'] ?? null)) {
            $validation['errors'][] = 'Validade não encontrada';
            $validation['success'] = false;
        }

        // Verificar se expirou
        if (isset($data['validity']) && $this->isCNHExpired($data['validity'])) {
            $validation['errors'][] = 'CNH expirada';
            $validation['success'] = false;
        }

        if (empty($data['categories'] ?? null)) {
            $validation['errors'][] = 'Categorias de habilitação não encontradas';
            $validation['success'] = false;
        }

        return $validation;
    }

    /**
     * Validar formato CPF
     */
    protected function isValidCPFFormat(?string $cpf): bool
    {
        if (!$cpf) {
            return false;
        }

        // Remove pontuação
        $cpf = preg_replace('/\D/', '', $cpf);

        // CPF deve ter 11 dígitos
        return strlen($cpf) === 11 && ctype_digit($cpf);
    }

    /**
     * Validar dígitos verificadores CPF
     */
    protected function isValidCPFChecksum(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // CPF 111.111.111-11 é inválido
        if ($cpf === '11111111111' || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validar primeiro dígito
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $digit1 = 11 - ($sum % 11);
        $digit1 = $digit1 > 9 ? 0 : $digit1;

        if ($cpf[9] != $digit1) {
            return false;
        }

        // Validar segundo dígito
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $digit2 = 11 - ($sum % 11);
        $digit2 = $digit2 > 9 ? 0 : $digit2;

        return $cpf[10] == $digit2;
    }

    /**
     * Verificar se CNH expirou
     */
    protected function isCNHExpired(string $validity): bool
    {
        try {
            $expiryDate = \DateTime::createFromFormat('d/m/Y', $validity);
            return $expiryDate < new \DateTime();
        } catch (\Exception $e) {
            Log::warning('Erro ao verificar validade de CNH', [
                'validity' => $validity,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Extrair nome
     */
    public function extractName(array $ocrResponse): ?string
    {
        return $ocrResponse['name'] ?? null;
    }

    /**
     * Extrair data de nascimento
     */
    public function extractBirthDate(array $ocrResponse): ?string
    {
        return $ocrResponse['birth_date'] ?? null;
    }

    /**
     * Extrair foto (base64 se disponível)
     */
    public function extractPhoto(array $ocrResponse): ?string
    {
        return $ocrResponse['photo'] ?? null;
    }
}
