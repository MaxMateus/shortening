# Shortening API

API simples para encurtar URLs, com expiracao opcional e estatisticas de acesso.

**Funcionalidades**
- Criacao de URL curta com expiracao opcional
- Redirecionamento para a URL original
- Estatisticas de visitas por codigo
- Reuso da mesma URL curta se a original ainda estiver valida
- Rate limit no redirecionamento (5 req/s por codigo+IP)

**Stack**
- Laravel 12
- PHP 8.2
- MySQL 8 (via Docker) ou outro banco suportado pelo Laravel

**Configuracao**
- `APP_URL` define a base usada para montar `short_url`
- `DB_*` deve apontar para seu banco (no Docker, `DB_HOST=db`)

**Rodando com Docker**
1. `docker compose up -d --build`
2. `docker compose exec app composer install`
3. `docker compose exec app php artisan migrate`
4. A API fica em `http://localhost:8000`

**Rodando localmente**
1. `composer install`
2. `cp .env.example .env`
3. Ajuste `DB_*` no `.env`
4. `php artisan key:generate`
5. `php artisan migrate`
6. `php artisan serve`

**Endpoints**

`POST /short`
- Cria uma URL curta
- Body JSON
  - `original_url` (string, obrigatorio)
  - `expires_in` (int, segundos, opcional)
- Retorna `201` quando cria e `200` quando reaproveita uma URL valida

Exemplo:
```bash
curl -X POST http://localhost:8000/short \
  -H 'Content-Type: application/json' \
  -d '{"original_url":"https://example.com","expires_in":3600}'
```

Resposta:
```json
{
  "message": "URL encurtada com sucesso.",
  "data": {
    "code": "Ab12Cd",
    "short_url": "http://localhost:8000/Ab12Cd",
    "original_url": "https://example.com",
    "expires_at": "2026-02-05 15:30:00",
    "expired": false
  }
}
```

`GET /{code}`
- Redireciona (HTTP 302) para a URL original
- Se expirado, retorna `410`
- Se nao existir, retorna `404`

Exemplo:
```bash
curl -i http://localhost:8000/Ab12Cd
```

`GET /stats/{code}`
- Retorna estatisticas da URL

Exemplo:
```bash
curl http://localhost:8000/stats/Ab12Cd
```

Resposta:
```json
{
  "data": {
    "code": "Ab12Cd",
    "short_url": "http://localhost:8000/Ab12Cd",
    "original_url": "https://example.com",
    "expires_at": null,
    "expired": false,
    "visits": 12
  }
}
```

**Erros comuns**
- `422` Dados invalidos (ex.: `original_url` ausente ou mal formada)
- `404` URL nao encontrada
- `410` URL expirada

**Notas**
- O endpoint `POST /short` esta sem CSRF para uso via clientes externos.
- O rate limit do redirect e 5 requisicoes por segundo por combinacao `codigo+IP`.
