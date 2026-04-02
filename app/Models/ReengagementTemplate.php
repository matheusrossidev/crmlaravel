<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReengagementTemplate extends Model
{
    protected $fillable = [
        'stage',
        'channel',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Available variables for template interpolation.
     */
    public static function availableVariables(): array
    {
        return [
            '{{nome}}'               => 'Nome do usuário',
            '{{empresa}}'            => 'Nome do tenant/empresa',
            '{{dias_sem_login}}'     => 'Dias desde o último login',
            '{{leads_total}}'        => 'Total de leads ativos',
            '{{leads_sem_contato}}'  => 'Leads sem atividade há 5+ dias',
            '{{leads_novos_semana}}' => 'Leads criados nos últimos 7 dias',
            '{{conversas_abertas}}'  => 'Conversas WA/IG abertas',
            '{{tarefas_pendentes}}'  => 'Tarefas pendentes ou atrasadas',
            '{{vendas_mes}}'         => 'Vendas fechadas no mês atual',
            '{{link_crm}}'           => 'URL do CRM (app.syncro.chat)',
            '{{link_leads}}'         => 'Link direto para /contatos',
            '{{link_chats}}'         => 'Link direto para /chats',
        ];
    }

    /**
     * Interpolate variables into the template body.
     */
    public function render(array $variables): string
    {
        $text = $this->body;

        foreach ($variables as $key => $value) {
            $text = str_replace($key, (string) $value, $text);
        }

        return $text;
    }
}
