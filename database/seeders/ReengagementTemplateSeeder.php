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
            // ── WhatsApp (pt_BR) ──
            [
                'stage'   => '7d',
                'channel' => 'whatsapp',
                'locale'  => 'pt_BR',
                'body'    => "Oi {{nome}}! 👋\n\nFaz *{{dias_sem_login}} dias* que você não acessa o Syncro.\n\nEnquanto isso, você tem:\n📋 *{{leads_sem_contato}} leads* esperando resposta\n💬 *{{conversas_abertas}} conversas* abertas\n📌 *{{tarefas_pendentes}} tarefas* pendentes\n\nSeus leads estão esfriando! Que tal dar uma olhada?\n\n👉 {{link_crm}}",
            ],
            [
                'stage'   => '14d',
                'channel' => 'whatsapp',
                'locale'  => 'pt_BR',
                'body'    => "{{nome}}, seus leads estão esperando! 🔥\n\nJá faz *{{dias_sem_login}} dias* sem acessar o Syncro.\n\nVocê tem *{{leads_sem_contato}} leads sem contato* e *{{conversas_abertas}} conversas abertas*.\n\nCada dia sem resposta é uma venda perdida. Retome agora:\n\n👉 {{link_leads}}",
            ],
            [
                'stage'   => '30d',
                'channel' => 'whatsapp',
                'locale'  => 'pt_BR',
                'body'    => "{{nome}}, faz um mês! 😟\n\nSentimos sua falta no Syncro. Você tem *{{leads_total}} leads* na base e *{{vendas_mes}} vendas* esse mês.\n\nPrecisa de ajuda pra retomar? Responda aqui que te ajudamos.\n\nOu acesse direto: {{link_crm}}",
            ],

            // ── Email (pt_BR) ──
            [
                'stage'   => '7d',
                'channel' => 'email',
                'locale'  => 'pt_BR',
                'subject' => 'Seus leads estão esperando, {{nome}}',
                'body'    => "Oi {{nome}}!\n\nFaz {{dias_sem_login}} dias que você não acessa o Syncro da {{empresa}}.\n\nEnquanto isso:\n- {{leads_sem_contato}} leads estão sem contato há mais de 5 dias\n- {{conversas_abertas}} conversas abertas esperando resposta\n- {{tarefas_pendentes}} tarefas pendentes\n\nSeus leads estão esfriando. Acesse agora e retome suas vendas!",
            ],
            [
                'stage'   => '14d',
                'channel' => 'email',
                'locale'  => 'pt_BR',
                'subject' => '{{leads_sem_contato}} leads esperando por você, {{nome}}',
                'body'    => "{{nome}}, já faz {{dias_sem_login}} dias sem acessar o Syncro.\n\nVocê tem {{leads_sem_contato}} leads sem contato e {{conversas_abertas}} conversas abertas.\n\nCada dia sem resposta é uma oportunidade perdida. Seus concorrentes não estão esperando.\n\nRetome suas vendas agora — leva menos de 1 minuto.",
            ],
            [
                'stage'   => '30d',
                'channel' => 'email',
                'locale'  => 'pt_BR',
                'subject' => 'Sentimos sua falta, {{nome}}',
                'body'    => "{{nome}}, faz {{dias_sem_login}} dias desde seu último acesso.\n\nSua base tem {{leads_total}} leads e {{vendas_mes}} vendas foram registradas esse mês pela sua equipe.\n\nPrecisa de ajuda para retomar? Estamos aqui para isso.\n\nResponda este email ou acesse o Syncro — preparamos tudo para você voltar com força.",
            ],

            // ── WhatsApp (en) ──
            [
                'stage'   => '7d',
                'channel' => 'whatsapp',
                'locale'  => 'en',
                'body'    => "Hi {{nome}}! 👋\n\nIt's been *{{dias_sem_login}} days* since you last accessed Syncro.\n\nMeanwhile, you have:\n📋 *{{leads_sem_contato}} leads* waiting for a response\n💬 *{{conversas_abertas}} conversations* open\n📌 *{{tarefas_pendentes}} tasks* pending\n\nYour leads are going cold! How about taking a look?\n\n👉 {{link_crm}}",
            ],
            [
                'stage'   => '14d',
                'channel' => 'whatsapp',
                'locale'  => 'en',
                'body'    => "{{nome}}, your leads are waiting! 🔥\n\nIt's been *{{dias_sem_login}} days* without accessing Syncro.\n\nYou have *{{leads_sem_contato}} leads without contact* and *{{conversas_abertas}} open conversations*.\n\nEvery day without a response is a lost sale. Get back now:\n\n👉 {{link_leads}}",
            ],
            [
                'stage'   => '30d',
                'channel' => 'whatsapp',
                'locale'  => 'en',
                'body'    => "{{nome}}, it's been a month! 😟\n\nWe miss you at Syncro. You have *{{leads_total}} leads* in your database and *{{vendas_mes}} sales* this month.\n\nNeed help getting back on track? Reply here and we'll help.\n\nOr access directly: {{link_crm}}",
            ],

            // ── Email (en) ──
            [
                'stage'   => '7d',
                'channel' => 'email',
                'locale'  => 'en',
                'subject' => 'Your leads are waiting, {{nome}}',
                'body'    => "Hi {{nome}}!\n\nIt's been {{dias_sem_login}} days since you last accessed Syncro at {{empresa}}.\n\nMeanwhile:\n- {{leads_sem_contato}} leads have had no contact for over 5 days\n- {{conversas_abertas}} open conversations waiting for a reply\n- {{tarefas_pendentes}} pending tasks\n\nYour leads are going cold. Access now and get back to selling!",
            ],
            [
                'stage'   => '14d',
                'channel' => 'email',
                'locale'  => 'en',
                'subject' => '{{leads_sem_contato}} leads waiting for you, {{nome}}',
                'body'    => "{{nome}}, it's been {{dias_sem_login}} days without accessing Syncro.\n\nYou have {{leads_sem_contato}} leads without contact and {{conversas_abertas}} open conversations.\n\nEvery day without a response is a lost opportunity. Your competitors aren't waiting.\n\nGet back to selling now — it takes less than 1 minute.",
            ],
            [
                'stage'   => '30d',
                'channel' => 'email',
                'locale'  => 'en',
                'subject' => 'We miss you, {{nome}}',
                'body'    => "{{nome}}, it's been {{dias_sem_login}} days since your last access.\n\nYour database has {{leads_total}} leads and {{vendas_mes}} sales were recorded this month by your team.\n\nNeed help getting back on track? We're here for you.\n\nReply to this email or access Syncro — we've got everything ready for your comeback.",
            ],
        ];

        foreach ($templates as $t) {
            ReengagementTemplate::updateOrCreate(
                ['stage' => $t['stage'], 'channel' => $t['channel'], 'locale' => $t['locale']],
                $t,
            );
        }
    }
}
