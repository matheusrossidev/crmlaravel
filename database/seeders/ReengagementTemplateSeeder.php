<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ReengagementTemplate;
use Illuminate\Database\Seeder;

class ReengagementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── WhatsApp ──
            [
                'stage'   => '7d',
                'channel' => 'whatsapp',
                'body'    => "Oi {{nome}}! 👋\n\nFaz *{{dias_sem_login}} dias* que você não acessa o Syncro.\n\nEnquanto isso, você tem:\n📋 *{{leads_sem_contato}} leads* esperando resposta\n💬 *{{conversas_abertas}} conversas* abertas\n📌 *{{tarefas_pendentes}} tarefas* pendentes\n\nSeus leads estão esfriando! Que tal dar uma olhada?\n\n👉 {{link_crm}}",
            ],
            [
                'stage'   => '14d',
                'channel' => 'whatsapp',
                'body'    => "{{nome}}, seus leads estão esperando! 🔥\n\nJá faz *{{dias_sem_login}} dias* sem acessar o Syncro.\n\nVocê tem *{{leads_sem_contato}} leads sem contato* e *{{conversas_abertas}} conversas abertas*.\n\nCada dia sem resposta é uma venda perdida. Retome agora:\n\n👉 {{link_leads}}",
            ],
            [
                'stage'   => '30d',
                'channel' => 'whatsapp',
                'body'    => "{{nome}}, faz um mês! 😟\n\nSentimos sua falta no Syncro. Você tem *{{leads_total}} leads* na base e *{{vendas_mes}} vendas* esse mês.\n\nPrecisa de ajuda pra retomar? Responda aqui que te ajudamos.\n\nOu acesse direto: {{link_crm}}",
            ],

            // ── Email ──
            [
                'stage'   => '7d',
                'channel' => 'email',
                'subject' => 'Seus leads estão esperando, {{nome}}',
                'body'    => "Oi {{nome}}!\n\nFaz {{dias_sem_login}} dias que você não acessa o Syncro da {{empresa}}.\n\nEnquanto isso:\n- {{leads_sem_contato}} leads estão sem contato há mais de 5 dias\n- {{conversas_abertas}} conversas abertas esperando resposta\n- {{tarefas_pendentes}} tarefas pendentes\n\nSeus leads estão esfriando. Acesse agora e retome suas vendas!",
            ],
            [
                'stage'   => '14d',
                'channel' => 'email',
                'subject' => '{{leads_sem_contato}} leads esperando por você, {{nome}}',
                'body'    => "{{nome}}, já faz {{dias_sem_login}} dias sem acessar o Syncro.\n\nVocê tem {{leads_sem_contato}} leads sem contato e {{conversas_abertas}} conversas abertas.\n\nCada dia sem resposta é uma oportunidade perdida. Seus concorrentes não estão esperando.\n\nRetome suas vendas agora — leva menos de 1 minuto.",
            ],
            [
                'stage'   => '30d',
                'channel' => 'email',
                'subject' => 'Sentimos sua falta, {{nome}}',
                'body'    => "{{nome}}, faz {{dias_sem_login}} dias desde seu último acesso.\n\nSua base tem {{leads_total}} leads e {{vendas_mes}} vendas foram registradas esse mês pela sua equipe.\n\nPrecisa de ajuda para retomar? Estamos aqui para isso.\n\nResponda este email ou acesse o Syncro — preparamos tudo para você voltar com força.",
            ],
        ];

        foreach ($templates as $t) {
            ReengagementTemplate::updateOrCreate(
                ['stage' => $t['stage'], 'channel' => $t['channel']],
                $t,
            );
        }
    }
}
