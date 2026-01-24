# 🏗️ ARQUITETURA E DIAGRAMA DE FLUXO

## Diagrama de Arquitetura (ASCII)

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENTE (Navegador)                      │
│  http://localhost:8000/documents/create                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    POST /documents
                    (image + document_type)
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                   LARAVEL APPLICATION SERVER                     │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  Route: /documents → DocumentValidationController@store   │  │
│  └───────────────────┬────────────────────────────────────────┘  │
│                      │                                             │
│  ┌───────────────────▼────────────────────────────────────────┐  │
│  │  1. Validar entrada (image, document_type)                │  │
│  │  2. Armazenar arquivo em storage/app/public/documents/   │  │
│  │  3. Criar registro em document_validations (pending)      │  │
│  └───────────────────┬────────────────────────────────────────┘  │
│                      │                                             │
│  ┌───────────────────▼────────────────────────────────────────┐  │
│  │  Chamar OcrService->validateDocument($file)               │  │
│  └───────────────────┬────────────────────────────────────────┘  │
│                      │                                             │
└──────────────────────┼────────────────────────────────────────────┘
                       │
                HTTP POST request
                multipart/form-data
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│              API EXTERNA - OCR (localhost:5000)                   │
│                                                                   │
│  POST /ocr                                                        │
│  [Processamento da imagem]                                        │
│  [Extração de texto]                                              │
│  [Retorna JSON com dados]                                         │
│                                                                   │
│  Resposta:                                                        │
│  {                                                                │
│    "text": "123456789",                                           │
│    "confidence": 0.95,                                            │
│    "fields": {...}                                                │
│  }                                                                │
└───────────────────┬────────────────────────────────────────────┘
                    │
        Response com dados extraídos
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│              LARAVEL APPLICATION SERVER (cont)                    │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  4. Receber resposta da API                                │  │
│  │  5. Processar e validar dados                              │  │
│  │  6. Atualizar registro:                                    │  │
│  │     - ocr_response = JSON da API                           │  │
│  │     - extracted_data = dados processados                   │  │
│  │     - validation_status = validated/failed/error           │  │
│  └────────────────────────────────────────────────────────────┘  │
│                      │                                             │
│  ┌───────────────────▼────────────────────────────────────────┐  │
│  │  7. Salvar tudo no BD (PostgreSQL/MySQL)                   │  │
│  │     document_validations table                             │  │
│  └────────────────────────────────────────────────────────────┘  │
│                      │                                             │
│  ┌───────────────────▼────────────────────────────────────────┐  │
│  │  8. Redirecionar para:                                     │  │
│  │     /documents/{id}                                        │  │
│  └────────────────────────────────────────────────────────────┘  │
│                      │                                             │
└──────────────────────┼────────────────────────────────────────────┘
                       │
                    Redirect + Response
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                    CLIENTE (Navegador)                            │
│  http://localhost:8000/documents/{id}                             │
│  [Exibe resultado, dados extraídos, status]                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## Fluxo de Dados (Detalhado)

```
FASE 1: UPLOAD
═════════════════════════════════════════════════════════════════

Usuario
   │
   │ POST form
   ├─── document_type: "RG"
   ├─── image: <binary>
   │
   ▼
DocumentValidationController@store
   │
   ├─ Validar entrada
   ├─ Salvar arquivo
   │    └─ storage/app/public/documents/timestamp.jpg
   │
   ├─ Criar registro (pending)
   │    └─ DocumentValidation::create([
   │         user_id: 1,
   │         document_type: "RG",
   │         image_path: "documents/timestamp.jpg",
   │         validation_status: "pending"
   │       ])
   │
   └─ Chamar OcrService


FASE 2: INTEGRAÇÃO COM API OCR
═════════════════════════════════════════════════════════════════

OcrService@validateDocument
   │
   ├─ Http::attach('image', file, filename)
   ├─ ->post('http://localhost:5000/ocr')
   │
   └─ AGUARDA RESPOSTA
        │
        ▼
      API OCR
        │
        ├─ Processa imagem
        ├─ Extrai texto
        ├─ Detecta características
        └─ Retorna JSON
             │
             ▼
      Resposta recebida
        └─ {
             "text": "123456789",
             "confidence": 0.95,
             "fields": {...}
           }


FASE 3: PROCESSAMENTO
═════════════════════════════════════════════════════════════════

OcrService@extractData
   │
   ├─ Parse response
   │    ├─ Extrair campos específicos
   │    ├─ Validar dados
   │    └─ Verificar confiança
   │
   └─ Retornar [
        'success' => true,
        'extracted_fields' => [...],
        'is_valid' => true
      ]


FASE 4: ARMAZENAMENTO
═════════════════════════════════════════════════════════════════

DocumentValidation::update
   │
   ├─ ocr_response: (JSON bruto)
   ├─ extracted_data: (dados processados)
   ├─ validation_status: "validated" | "failed"
   └─ error_message: null | "mensagem"


FASE 5: APRESENTAÇÃO
═════════════════════════════════════════════════════════════════

GET /documents/{id}
   │
   ├─ Verificar propriedade (policy)
   ├─ Carregar documento
   └─ Renderizar view
        └─ Exibir:
           ├─ Status (✓ validado / ✗ erro)
           ├─ Imagem original
           ├─ Dados extraídos
           ├─ Confiança
           └─ JSON completo
```

---

## Estrutura do Banco de Dados

```
document_validations (Tabela Principal)
┌─────────────────────────────────────────────────────────────┐
│ id                 INT PK                                    │
│ user_id            INT FK → users.id                         │
│ document_type      VARCHAR (RG, CNH, CPF, PASSPORT)          │
│ image_path         VARCHAR storage/app/public/documents/...  │
│ ocr_response       JSON [resposta bruta da API]              │
│ extracted_data     JSON [dados processados]                  │
│ validation_status  ENUM pending/validated/failed/error       │
│ error_message      TEXT [se houver erro]                     │
│ created_at         TIMESTAMP                                 │
│ updated_at         TIMESTAMP                                 │
└─────────────────────────────────────────────────────────────┘
            │
            ├─ FK → users.id
            │
            ▼
        users (Relacionamento)
    ┌──────────────────────────┐
    │ id                       │
    │ name                     │
    │ email                    │
    │ ...                      │
    └──────────────────────────┘
```

---

## Fluxo de Arquivos no Storage

```
storage/
├── app/
│   ├── public/
│   │   └── documents/              ← ARMAZENADOS AQUI
│   │       ├── 1705955000.jpg
│   │       ├── 1705955120.jpg
│   │       └── 1705955250.jpg
│   │
│   └── private/
│       └── (dados privados)
│
├── logs/
│   └── laravel.log                 ← LOGS AQUI
│
└── framework/
    ├── cache/
    ├── sessions/
    └── views/
```

---

## Fluxo de Requisições HTTP

```
1. UPLOAD
──────────────────────────────────────────────
POST /documents
Content-Type: multipart/form-data

document_type=RG&image=<file>

Response: 302 Redirect → /documents/{id}


2. VISUALIZAR RESULTADO
──────────────────────────────────────────────
GET /documents/{id}

Response: 200 OK
Body: HTML (documento com dados extraídos)


3. LISTAR HISTÓRICO
──────────────────────────────────────────────
GET /documents

Response: 200 OK
Body: HTML (tabela com histórico)


4. DELETAR
──────────────────────────────────────────────
DELETE /documents/{id}

Response: 302 Redirect → /documents
(Arquivo removido + Registro deletado)
```

---

## Fluxo de Autenticação

```
Usuário Não Autenticado
         │
         ▼
      Acessa /documents
         │
         ▼
      Middleware 'auth'
         │
         ├─ Autenticado? ✓ Prosseguir
         │
         └─ Não autenticado? ✗ Redirecionar
              └─ GET /login
```

---

## Fluxo de Erros

```
Tentativa de Validação
         │
    ┌────┴────────────────────────┐
    │                             │
    ▼                             ▼
Sucesso                          Erro
    │                             │
    ├─ 200 OK          ┌─────────┴────────────┬──────────┐
    │                  │                      │          │
    │                  ▼                      ▼          ▼
    │             API Error          Network Error    File Error
    │                  │                      │          │
    │                  ▼                      ▼          ▼
    │             status=error        status=error  status=error
    │                  │                      │          │
    │                  └──────────────┬───────┘          │
    │                                 │                  │
    │                         error_message            Log
    │                                 │                  │
    │                                 ▼                  ▼
    │                        view('documents.show')  storage/logs/
    │                                                  laravel.log
    │
    ▼
view('documents.show')
    └─ Exibir resultado com status
```

---

## Tecnologias Utilizadas

```
┌──────────────────────┐
│   Frontend           │
├──────────────────────┤
│ • Blade Templates    │
│ • Tailwind CSS       │
│ • HTML Forms         │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│   Laravel Backend    │
├──────────────────────┤
│ • Controllers        │
│ • Models (Eloquent)  │
│ • Services           │
│ • Routes             │
│ • Middleware         │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│   Database           │
├──────────────────────┤
│ • MySQL/PostgreSQL   │
│ • Migrations         │
│ • Seeding            │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│   Storage            │
├──────────────────────┤
│ • Filesystem local   │
│ • storage/app/public │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│   API Externa        │
├──────────────────────┤
│ • OCR Service        │
│ • localhost:5000/ocr │
│ • HTTP Multipart     │
└──────────────────────┘
```

---

## Resumo de Componentes

```
┌─── MODELS ───┐
│ • User       │
│ • DocumentV. │
└──────────────┘
      │
┌─── CONTROLLERS ───┐
│ • DocumentValCtrl │
│ • TestOcrCtrl     │
└────────────────────┘
      │
┌─── SERVICES ───┐
│ • OcrService   │
│ • AdvancedOcr  │
└────────────────┘
      │
┌─── ROUTES ───┐
│ • /documents │
│ • /documents │
└───────────────┘
      │
┌─── VIEWS ───┐
│ • create    │
│ • show      │
│ • index     │
└─────────────┘
```

---

**Fluxo completo visualizado! 🎨**
