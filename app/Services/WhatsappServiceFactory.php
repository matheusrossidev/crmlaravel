<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\WhatsappServiceContract;
use App\Models\WhatsappInstance;

/**
 * Resolve qual implementação de WhatsappServiceContract usar baseado no
 * provider configurado em uma WhatsappInstance.
 *
 * Use sempre essa factory em vez de instanciar WahaService/WhatsappCloudService
 * diretamente — assim o código de envio (chatbot, AI, automação, chat manual)
 * funciona pra qualquer provider sem mudança.
 *
 * Exemplo:
 *   $service = WhatsappServiceFactory::for($instance);
 *   $service->sendText($chatId, $text);
 */
class WhatsappServiceFactory
{
    /**
     * Retorna o service apropriado pra uma instância específica.
     */
    public static function for(WhatsappInstance $instance): WhatsappServiceContract
    {
        return match ($instance->provider ?? 'waha') {
            'cloud_api' => new WhatsappCloudService($instance),
            default     => new WahaService($instance->session_name),
        };
    }

    /**
     * Atalho pra instanciar pelo ID — útil em jobs onde temos só o ID
     * (carrega a instância do banco antes).
     */
    public static function forInstanceId(int $instanceId): ?WhatsappServiceContract
    {
        $instance = WhatsappInstance::withoutGlobalScope('tenant')->find($instanceId);
        return $instance ? self::for($instance) : null;
    }
}
