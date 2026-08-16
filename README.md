# Neev Solusindo Chatbot

Laravel 12 application for the Neev Solusindo public landing page, floating
chatbot, standalone chatbot page, and chatbot administration tools.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- MySQL with the existing Neev Solusindo tables
- A Gemini API key

## Gemini configuration

The chatbot uses the native Google Gemini REST API through
`App\Services\LLMInterface`. Gemini is the only active runtime provider; an
OpenAI API key is not required.

Keep the real API key only in `.env` locally or in the secret environment
configuration of the production host. Never commit it.

```dotenv
LLM_PROVIDER=gemini

GEMINI_API_VERSION=v1beta
GEMINI_API_KEY=PASTE_MY_KEY_HERE
GEMINI_MODEL=models/gemini-3.6-flash
GEMINI_EMBED_MODEL=models/gemini-embedding-2
GEMINI_EMBED_DIM=768
GEMINI_GOOGLE_SEARCH=false

CHATBOT_HISTORY_MESSAGES=8
CHATBOT_MAX_TOKENS=512
CHATBOT_HTTP_TIMEOUT=25
CHATBOT_HTTP_RETRY=1
```

`GEMINI_GOOGLE_SEARCH` is optional and disabled by default. When enabled,
Gemini may use Google Search grounding for current external information. The
normal chatbot and company RAG flow do not require it.

The configured REST endpoints resolve to:

```text
https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent
https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent
```

Authentication is sent in the `x-goog-api-key` header. The key is never placed
in source code, URLs, command output, or application logs.

## Provider/model migration

Embeddings from OpenAI, an older Gemini model, or another output dimension are
not compatible with `gemini-embedding-2`. A full knowledge build first clears
stored vectors and then recreates them with the configured Gemini model and
dimension. If an API call fails, lexical retrieval remains available instead
of comparing incompatible vectors.

Run these commands after setting the Gemini key:

```shell
php artisan optimize:clear
php artisan chatbot:test-gemini
php artisan chatbot:build-knowledge
php artisan test
composer run dev
```

The health command checks a small embedding and chat request without printing
the API key. A successful result includes:

```text
Gemini API: OK
Chat model: gemini-3.6-flash
Embedding model: gemini-embedding-2
Embedding dimension: 768
Response: GEMINI_OK
```

## Verifying Gemini in Laravel logs

On Windows, follow the application log from a separate PowerShell terminal:

```powershell
Get-Content .\storage\logs\laravel.log -Wait
```

Successful Gemini calls do not write warnings. Failed calls write a safe entry
such as `Gemini chat error` or `Gemini embedding error`, with HTTP status,
Gemini error code, and message—but never the API key. You can also confirm that
knowledge rows contain 768-value JSON vectors after a successful rebuild.

## Routes

```text
/                  Public landing page with floating chatbot
/chatbot           Standalone chatbot
/chatbot/widget    Floating widget demo
/admin             Admin dashboard
/admin/faq         FAQ management
/admin/unanswered  Unanswered-question review
```

Landing-page detail/contact CTAs show a confirmation modal before navigating to
the official Neevwork website. Local section navigation and all chatbot/admin
controls are excluded from that behavior.

## Local development on Windows

```shell
composer run dev
```

This starts Laravel, the queue listener, and Vite. Laravel Pail requires the
`pcntl` extension and is therefore kept in the separate `dev:with-logs` script
for compatible Linux/macOS environments.
