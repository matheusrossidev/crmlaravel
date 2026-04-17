---
type: integration-reference
topic: media
last_review: 2026-04-17
related: ["[[README]]", "[[06-chatting-send]]", "[[07-chatting-receive]]"]
tags: [waha, media, files, upload]
---

# 14 — Media & Files

## Upload: URL vs Base64

Toda operação `sendImage`/`sendFile`/`sendVoice`/`sendVideo` do WAHA aceita **dois modos** pra `file`:

### Modo URL

```json
"file": {
  "mimetype": "image/jpeg",
  "url": "https://example.com/foto.jpg",
  "filename": "foto.jpeg"
}
```

- WAHA baixa o arquivo do servidor
- **Sem overhead de codificação** no client
- **Dependência da URL** estar acessível e não expirar
- Risco de **SSRF** — WAHA implementa proteção contra IPs privados

### Modo Base64

```json
"file": {
  "mimetype": "image/jpeg",
  "filename": "foto.jpeg",
  "data": "/9j/4AAQSkZJ..."
}
```

- Enviado em 1 request, WAHA não precisa fetch externo
- **Overhead de +33%** no tamanho (base64 expansion)
- Max request size do HTTP — pra arquivos grandes pode rejeitar (default ~50MB no nginx)
- Funciona pra mídia gerada localmente (screenshots, TTS, etc)

### Qual usar?

- **URL**: padrão quando tem a mídia em storage público (nossa app.syncro.chat com link direto).
- **Base64**: pra mídia gerada on-the-fly (ElevenLabs TTS, chart renders, etc).

Nosso código usa URL sempre que possível pra economizar bandwidth.

## Download de mídia inbound

Quando chega msg com `hasMedia: true`, o payload tem:

```json
"media": {
  "url": "http://waha-host/api/files/abc123.jpg",
  "mimetype": "image/jpeg",
  "filename": null,
  "error": null
}
```

**A URL aponta pro próprio WAHA** (`/api/files/{hash}`). Pra baixar:

```
GET /api/files/abc123.jpg
Headers: X-Api-Key: <key>
```

Retorna o arquivo binário diretamente.

### Download com SSRF guard

Nosso [ProfilePictureDownloader::download](app/Support/ProfilePictureDownloader.php):

1. Rejeita URLs com IP privado (10.*, 172.16-31.*, 192.168.*, 127.*, link-local)
2. Segue redirects até 3x
3. Valida MIME retornado vs esperado
4. Salva em `storage/app/public/profile-pics/{channel}/{tenant}/{id}.{ext}`
5. Retorna URL pública do storage Laravel

**Fallback**: se download falhar, retorna a URL original (WAHA CDN) — o CRM ainda tenta renderizar.

## Media conversion

WAHA pode converter antes de enviar.

### Voice conversion (MP3 → OGG Opus)

```
POST /api/{session}/media/convert/voice
{
  "url": "https://example.com/voice.mp3"
}
```

Retorna URL do arquivo convertido (voice do WhatsApp precisa ser OGG Opus pra aparecer como voice note).

Alternativa inline:
```json
"file": {
  "url": "https://example.com/voice.mp3"
},
"convert": true
```

### Video conversion

```
POST /api/{session}/media/convert/video
{
  "url": "https://example.com/video.mov"
}
```

Mesma lógica.

## MIME types suportados

Input:
- **Image**: JPEG, PNG, WebP, GIF (WhatsApp converte tudo pra JPEG)
- **Video**: MP4 H.264, AVI, MOV (convertido pra MP4)
- **Audio**: OGG Opus (voice), MP3/AAC (mensagem de áudio regular)
- **Document**: qualquer — PDF, DOCX, XLSX, ZIP, etc

Limites de tamanho do WhatsApp:
- Imagens: 16 MB
- Video/Audio: 16 MB (rede móvel) ou 100 MB (WiFi, upload manual)
- Documentos: 100 MB
- Voice notes: tipicamente limitado a poucos minutos

## URLs do CDN Meta expiram

⚠️ **URLs de mídia inbound** expiram em algumas horas (CDN Meta). Pra persistir:
1. Baixar imediatamente ao receber o webhook
2. Salvar em storage local/S3
3. Servir via nossa URL pública

Nosso `ProfilePictureDownloader` faz isso pro caso de foto de perfil. Pra mídia de mensagem, persistimos em `WhatsappMessage.media_url` após download.

## Gotchas

- **URL expira** — não armazenar URL do WAHA/CDN direto no banco como "source of truth". Baixar local.
- **`filename: null` é comum** em imagens tiradas na câmera — gerar nome baseado em MIME + timestamp.
- **SSRF bloqueia CDN privado** — se o WAHA rodar em rede privada, adicionar whitelist.
- **Base64 estoura request size** pra arquivos > 30 MB — usar URL.
- **`convert: true` aumenta latência** — WAHA precisa baixar + converter + re-enviar.
- **Voice note só aparece como "Voice" no WhatsApp se for OGG Opus** — MP3 aparece como anexo de áudio normal.
- **Algumas engines (WEBJS) rejeitam MIME type não-padrão** — GOWS é mais permissivo.

## Uso na Syncro

- [WahaService::sendImage/sendVoice/sendFile](app/Services/WahaService.php) — métodos de envio
- [WahaService::sendImageBase64](app/Services/WahaService.php) — variante base64
- [ProfilePictureDownloader](app/Support/ProfilePictureDownloader.php) — download + SSRF + storage local
- [ProcessWahaWebhook::handleInbound](app/Jobs/ProcessWahaWebhook.php) — baixa mídia inbound e salva
- Storage disk: `public` (`storage/app/public/profile-pics/whatsapp/{tenant_id}/`)
