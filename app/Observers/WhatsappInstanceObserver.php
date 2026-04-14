<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Cache;

/**
 * Observer de WhatsappInstance.
 *
 * Responsabilidade atual: invalidar o cache `tenant:{id}:has_cloud_api`
 * usado pelo helper `tenantHasCloudApi()` sempre que uma instância
 * cloud_api é criada/atualizada/deletada.
 *
 * Sem isso, o menu de "Templates WhatsApp" poderia aparecer/sumir com
 * até 60s de atraso (TTL do cache do helper).
 */
class WhatsappInstanceObserver
{
    public function saved(WhatsappInstance $instance): void
    {
        // Cobre create + update. Se provider mudou (raro, mas possível via tinker),
        // invalida ambos tenant_ids pra garantir consistência.
        $this->forgetCache($instance);
    }

    public function deleted(WhatsappInstance $instance): void
    {
        $this->forgetCache($instance);
    }

    private function forgetCache(WhatsappInstance $instance): void
    {
        // Só invalida se a instância afeta o status de cloud_api do tenant.
        // WAHA não usa esse cache — pulamos pra não gerar I/O desnecessário.
        if (($instance->provider ?? 'waha') !== 'cloud_api') {
            return;
        }

        Cache::forget("tenant:{$instance->tenant_id}:has_cloud_api");
    }
}
