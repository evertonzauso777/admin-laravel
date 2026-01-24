# 📄 Integração de Validação de Documentos com OCR no Laravel

## 🎯 Visão Geral

Este guia explica como implementar a validação de documentos (RG, CNH, CPF, etc.) usando uma API OCR externa (`http://localhost:5000/ocr`) no seu projeto Laravel.

---

## 📋 Arquivos Criados/Modificados

### ✅ Modelos
- **`app/Models/DocumentValidation.php`** - Model para armazenar histórico de validações
- **`app/Models/User.php`** - Modificado para adicionar relacionamento com DocumentValidation

### ✅ Migrations
- **`database/migrations/2025_01_24_000000_create_document_validations_table.php`** - Cria tabela de validações

### ✅ Services
- **`app/Services/OcrService.php`** - Integração com a API OCR

### ✅ Controllers
- **`app/Http/Controllers/DocumentValidationController.php`** - Lógica principal de validação
- **`app/Http/Controllers/TestOcrController.php`** - Controller para testes

### ✅ Rotas
- **`routes/web.php`** - Rotas para documentos criadas

### ✅ Views
- **`resources/views/documents/create.blade.php`** - Formulário de envio
- **`resources/views/documents/show.blade.php`** - Resultado da validação
- **`resources/views/documents/index.blade.php`** - Histórico de validações

---

## 🚀 Passo a Passo de Implementação

### 1️⃣ **Executar Migration**

```bash
php artisan migrate
```

Isso criará a tabela `document_validations` com os campos:
- `id` - ID único
- `user_id` - Usuário que enviou
- `document_type` - Tipo (RG, CNH, CPF, PASSPORT)
- `image_path` - Caminho do arquivo
- `ocr_response` - Resposta da API (JSON)
- `extracted_data` - Dados extraídos (JSON)
- `validation_status` - Status (pending, validated, failed, error)
- `error_message` - Mensagem de erro (se houver)

### 2️⃣ **Verificar Configuração Storage**

No `.env`, assegure que está configurado:

```env
FILESYSTEM_DISK=public
```

E execute (se não tiver feito):

```bash
php artisan storage:link
```

### 3️⃣ **Testar a Integração**

#### Opção A: Via Interface Web

1. Acesse: `http://localhost:8000/documents/create`
2. Selecione tipo de documento
3. Envie uma imagem
4. Veja o resultado em `http://localhost:8000/documents/{id}`

#### Opção B: Via API (para testes)

```bash
curl -X POST http://localhost:8000/api/test-ocr \
  -F "image=@/F:/Users/Tonsbug/RG.PNG"
```

### 4️⃣ **Testar a API OCR Diretamente**

```bash
curl --location 'http://localhost:5000/ocr' \
  --form 'image=@"/F:/Users/Tonsbug/RG.PNG"'
```

---

## 🔧 Customização

### Modificar URL da API OCR

No `OcrService.php`:

```php
protected $apiUrl = 'http://seu-servidor:5000/ocr';
```

Ou dinamicamente:

```php
$this->ocrService->setApiUrl('http://novo-url:5000/ocr');
```

### Adaptar Parsing de Dados

Na classe `OcrService`, modifique o método `parseResponse()` conforme a resposta da sua API:

```php
private function parseResponse(array $response): array
{
    // Adaptar conforme retorno real
    return [
        'text' => $response['text'] ?? null,
        'confidence' => $response['confidence'] ?? null,
        'fields' => $response['fields'] ?? []
    ];
}
```

### Adicionar Validações Específicas

Modifique `validateExtractedData()`:

```php
private function validateExtractedData(array $response): bool
{
    // Ex: validar CPF
    return $this->isValidCPF($response['cpf'] ?? null);
}

private function isValidCPF(string $cpf): bool
{
    // Implementar validação CPF
    return true;
}
```

---

## 📊 Fluxo Completo

```
1. Usuário acessa /documents/create
   ↓
2. Faz upload da imagem
   ↓
3. Laravel salva arquivo em storage/public/documents/
   ↓
4. DocumentValidationController chama OcrService
   ↓
5. OcrService faz POST para http://localhost:5000/ocr
   ↓
6. API retorna dados extraídos
   ↓
7. Laravel salva resposta em document_validations
   ↓
8. Usuário vê resultado em /documents/{id}
```

---

## 🗄️ Estrutura do Banco

```sql
document_validations
├── id (PK)
├── user_id (FK)
├── document_type (RG|CNH|CPF|PASSPORT)
├── image_path
├── ocr_response (JSON)
├── validation_status (pending|validated|failed|error)
├── extracted_data (JSON)
├── error_message
├── created_at
└── updated_at
```

---

## 🔒 Segurança

- ✅ Autenticação obrigatória (`middleware('auth')`)
- ✅ Validação de arquivo (image, max 5MB)
- ✅ Verificação de propriedade (usuário só vê seus documentos)
- ✅ Tratamento de erros com logging
- ✅ Storage protegido

---

## 📝 Endpoints

| Método | URL | Descrição |
|--------|-----|-----------|
| GET | `/documents` | Lista histórico |
| GET | `/documents/create` | Formulário de envio |
| POST | `/documents` | Envia documento |
| GET | `/documents/{id}` | Ver resultado |
| DELETE | `/documents/{id}` | Deletar registro |

---

## 🛠️ Troubleshooting

### Erro: "API não responde"

```php
// Verificar timeout
$this->ocrService->setTimeout(60); // 60 segundos

// Verificar URL
$this->ocrService->setApiUrl('http://localhost:5000/ocr');
```

### Erro: "Arquivo não salvou"

```bash
# Verificar permissões
php artisan storage:link

# Criar pasta
mkdir -p storage/app/public/documents
chmod -R 775 storage/app/public/documents
```

### Erro: "Usuário não autorizado"

Certifique-se de estar autenticado:

```php
auth()->check() // retorna true
```

---

## 📚 Logs

Verifique logs em:

```
storage/logs/laravel.log
```

Busque por "OCR" para ver todas as operações.

---

## 🎉 Pronto!

Sua integração está completa. Acesse `/documents` para começar! 

Para dúvidas, verifique:
- `OcrService.php` - Integração com API
- `DocumentValidationController.php` - Lógica de validação
- `DocumentValidation.php` - Model de dados
