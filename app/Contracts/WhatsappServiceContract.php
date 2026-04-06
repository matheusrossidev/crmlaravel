<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface comum entre WAHA e Cloud API.
 *
 * Implementações:
 *  - App\Services\WahaService          (provider='waha')
 *  - App\Services\WhatsappCloudService (provider='cloud_api')
 *
 * Quem usa essa interface NÃO precisa saber qual provider está por trás —
 * use App\Services\WhatsappServiceFactory::for($instance) pra obter o
 * service correto baseado no campo `provider` da WhatsappInstance.
 *
 * O retorno de cada método é um array com as chaves comuns:
 *   ['id' => string|null, 'error' => string|null, 'raw' => mixed]
 */
interface WhatsappServiceContract
{
    /**
     * Envia mensagem de texto.
     *
     * @param  string  $chatId   formato WAHA: "5511999999999@c.us" / Cloud: "5511999999999"
     * @param  string  $text
     * @return array
     */
    public function sendText(string $chatId, string $text): array;

    /**
     * Envia imagem por URL pública.
     *
     * @param  string  $chatId
     * @param  string  $url       URL acessível publicamente
     * @param  string  $caption   opcional
     * @return array
     */
    public function sendImage(string $chatId, string $url, string $caption = ''): array;

    /**
     * Envia imagem por upload (arquivo local) — útil quando a URL pública
     * não é alcançável pelo provider.
     *
     * @param  string  $chatId
     * @param  string  $filePath  caminho absoluto no disco
     * @param  string  $mimeType
     * @param  string  $caption
     * @return array
     */
    public function sendImageBase64(string $chatId, string $filePath, string $mimeType, string $caption = ''): array;

    /**
     * Envia mensagem de voz por URL pública.
     *
     * @param  string  $chatId
     * @param  string  $url       URL acessível publicamente
     * @return array
     */
    public function sendVoice(string $chatId, string $url): array;

    /**
     * Envia mensagem de voz por upload (arquivo local).
     *
     * @param  string  $chatId
     * @param  string  $filePath  caminho absoluto no disco
     * @param  string  $mimeType  ex: "audio/ogg"
     * @return array
     */
    public function sendVoiceBase64(string $chatId, string $filePath, string $mimeType): array;

    /**
     * Envia documento (PDF, DOCX, ZIP, etc) por upload.
     *
     * @param  string  $chatId
     * @param  string  $filePath
     * @param  string  $mimeType
     * @param  string  $filename
     * @param  string  $caption
     * @return array
     */
    public function sendFileBase64(string $chatId, string $filePath, string $mimeType, string $filename, string $caption = ''): array;

    /**
     * Envia mensagem interativa de lista (menu com seções/rows).
     *
     * @param  string  $chatId
     * @param  string  $description    texto principal
     * @param  array   $rows           [['id' => 'opt1', 'title' => 'Opção 1', 'description' => '...'], ...]
     * @param  string|null  $title     título do header
     * @param  string  $buttonText     label do botão que abre a lista
     * @param  string|null  $footer
     * @return array
     */
    public function sendList(
        string $chatId,
        string $description,
        array $rows,
        ?string $title = null,
        string $buttonText = 'Selecione',
        ?string $footer = null,
    ): array;

    /**
     * Envia uma reação (emoji) a uma mensagem específica.
     *
     * @param  string  $messageId  ID da mensagem (waha_message_id ou cloud_message_id)
     * @param  string  $emoji
     * @return array
     */
    public function sendReaction(string $messageId, string $emoji): array;

    /**
     * Retorna 'waha' ou 'cloud_api'.
     */
    public function getProviderName(): string;
}
