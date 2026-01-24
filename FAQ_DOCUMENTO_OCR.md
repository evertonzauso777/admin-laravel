# ❓ FAQ - Validação de Documentos com OCR

---

## P: Como funciona o fluxo completo?

**R:** 
1. Usuário acessa `/documents/create`
2. Seleciona tipo de documento e faz upload da imagem
3. Controller recebe arquivo e chama `OcrService`
4. Service envia arquivo para API externa via `Http::attach()`
5. API retorna dados extraídos (JSON)
6. Laravel armazena resposta no BD
7. Usuário vê resultado em `/documents/{id}`

---

## P: Onde os arquivos são armazenados?

**R:** 
```
storage/app/public/documents/
```

Acessível em:
```
http://localhost:8000/storage/documents/arquivo.jpg
```

---

## P: Qual é o tamanho máximo de arquivo?

**R:** 
Configurado em `DocumentValidationController.php`:
```php
'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB
```

Para mudar:
```php
'image' => '...|max:10240' // 10MB
```

---

## P: Como customizar para meus documentos específicos?

**R:** 
Use `AdvancedOcrService.php`:

```php
// app/Http/Controllers/DocumentValidationController.php
public function __construct(AdvancedOcrService $ocrService)
{
    $this->ocrService = $ocrService;
}

public function store(Request $request)
{
    if ($validated['document_type'] === 'CPF') {
        $result = $this->ocrService->validateCPF($file);
    } elseif ($validated['document_type'] === 'RG') {
        $result = $this->ocrService->validateRG($file);
    }
    // ... resto do código
}
```

---

## P: Como testar sem uma imagem real?

**R:** 
Crie um arquivo de teste:

```bash
# Usar curl com qualquer imagem
curl -X POST http://localhost:8000/documents \
  -F "image=@/caminho/imagem.jpg" \
  -F "document_type=RG" \
  -H "Authorization: Bearer TOKEN"
```

Ou use imagens online:
```bash
wget https://example.com/document.jpg -O test.jpg
```

---

## P: Como verificar os logs?

**R:** 
```bash
# Ver todos os logs
tail -f storage/logs/laravel.log

# Filtrar por OCR
grep -i "OCR" storage/logs/laravel.log

# Ver apenas erros
grep -i "error" storage/logs/laravel.log
```

---

## P: O que acontece se a API OCR cair?

**R:** 
Status será `error` e `error_message` conterá detalhes:

```json
{
  "validation_status": "error",
  "error_message": "Connection refused at http://localhost:5000/ocr"
}
```

Usuário pode tentar novamente depois.

---

## P: Como listar todos os documentos do usuário?

**R:** 
```bash
php artisan tinker

# Listar documentos de usuário 1
$user = User::find(1);
$user->documentValidations()->get();

# Filtrar por status
$user->documentValidations()->where('validation_status', 'validated')->get();
```

---

## P: Posso deletar um documento?

**R:** 
Sim, via interface ou API:

```bash
# Deletar documento 5
DELETE /documents/5
```

Isso remove arquivo E registro do BD.

---

## P: Como fazer backup dos documentos?

**R:** 
```bash
# Copiar pasta de documentos
cp -r storage/app/public/documents /backup/

# Fazer dump do BD
php artisan db:seed
mysqldump -u root -p banco > backup.sql
```

---

## P: Como mover para produção?

**R:** 
```bash
# 1. Usar storage S3/Cloud
# Editar config/filesystems.php

# 2. Configurar URL correta da API
# Editar OcrService.php ou .env

# 3. Rodar migração
php artisan migrate --env=production

# 4. Verificar logs
tail -f storage/logs/laravel.log
```

---

## P: Como aumentar o timeout da API?

**R:** 
```php
// app/Services/OcrService.php
protected $timeout = 60; // 60 segundos

// Ou dinamicamente:
$this->ocrService->setTimeout(120); // 2 minutos
```

---

## P: Posso processar múltiplos documentos?

**R:** 
Sim! Use Jobs/Queue:

```php
// Criar job
php artisan make:job ProcessDocumentValidation

// app/Jobs/ProcessDocumentValidation.php
public function handle()
{
    $result = $this->ocrService->validateDocument($this->file);
    // ... processar ...
}

// No controller:
ProcessDocumentValidation::dispatch($file, $userId);
```

---

## P: Como integrar com Frontend React/Vue?

**R:** 
Use a API REST conforme `EXEMPLO_API_REST.md`:

```javascript
async function validateDocument(file, type) {
  const formData = new FormData();
  formData.append('image', file);
  formData.append('document_type', type);

  const response = await fetch('/documents', {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': token
    }
  });

  return response.json();
}
```

---

## P: Posso validar múltiplos tipos simultaneamente?

**R:** 
Não recomendado, mas você pode:

```php
// Loop pelo controller
$documentTypes = ['RG', 'CPF'];

foreach ($documentTypes as $type) {
    $validation = DocumentValidation::create([...]);
}
```

Melhor: enviar um por vez.

---

## P: Como fazer cache de resultados?

**R:** 
```php
// app/Services/OcrService.php
public function validateDocument(UploadedFile $file): array
{
    $hash = md5_file($file->getRealPath());
    
    // Verificar cache
    $cached = Cache::get('ocr_' . $hash);
    if ($cached) {
        return $cached;
    }

    // Fazer requisição...
    $result = [...];

    // Cachear por 30 dias
    Cache::put('ocr_' . $hash, $result, now()->addDays(30));

    return $result;
}
```

---

## P: Posso enviar documentos via API?

**R:** 
Sim! Veja `EXEMPLO_API_REST.md` para:
- Endpoints JSON
- Exemplos com curl
- Integração JavaScript

---

## P: Como implementar validação em fila?

**R:** 
```php
// .env
QUEUE_CONNECTION=database

// Criar migration de filas
php artisan queue:table
php artisan migrate

// Dispatch job
ProcessDocumentValidation::dispatch($file);

// Rodar worker
php artisan queue:work
```

---

## P: Como adicionar notificações?

**R:** 
```php
// app/Models/DocumentValidation.php
public function markAsValidated()
{
    $this->update(['validation_status' => 'validated']);
    
    // Notificar usuário
    $this->user->notify(new DocumentValidatedNotification($this));
}
```

---

## P: Quanto custa usar a API OCR?

**R:** 
Depende do provedor:
- Localmente: Grátis ✅
- Google Vision: $1.50/1000 requisições
- AWS Textract: $0.15/página
- Azure Computer Vision: $2.50/1000 requisições

Implemente cache para reduzir custos!

---

## P: Posso usar essa solução comercialmente?

**R:** 
Sim! Apenas:
1. Verificar licença da API OCR
2. Implementar termos de privacidade
3. Cumprir LGPD/GDPR
4. Fazer backup seguro

---

## P: Como deletar dados antigos?

**R:** 
```bash
# Criar comando
php artisan make:command DeleteOldDocuments

# app/Console/Commands/DeleteOldDocuments.php
$old = DocumentValidation::where('created_at', '<', now()->subMonths(6))->get();

foreach ($old as $doc) {
    Storage::delete($doc->image_path);
    $doc->delete();
}
```

---

## P: Suporta OCR offline?

**R:** 
Sim! Use Tesseract localmente:

```bash
# Instalar
apt-get install tesseract-ocr

# Usar no Laravel
$process = new Process(['tesseract', 'image.jpg', 'output']);
$result = json_decode($process->getOutput());
```

---

## ❓ Ainda tem dúvidas?

Verifique:
- `DOCUMENT_VALIDATION_GUIDE.md` - Guia completo
- `RESUMO_VALIDACAO_DOCUMENTOS.md` - Resumo rápido
- `CHECKLIST_IMPLEMENTACAO.md` - Passo a passo
- Logs em `storage/logs/laravel.log`

---

**Sucesso! 🚀**
