<?php

declare(strict_types=1);

return [
    // Common
    'common' => [
        'footer_support' => 'Dúvidas? suporte@syncro.chat',
        'footer_support_text' => 'Dúvidas?',
        'footer_copyright' => '© :year Syncro · syncro.chat',
        'cta_access' => 'Acessar minha conta →',
        'trial_badge' => '14 dias de teste grátis — sem cartão de crédito',
        'cant_click' => 'Não conseguiu clicar? Copie e cole no navegador:',
    ],

    // Welcome
    'welcome' => [
        'subject' => 'Bem-vindo à Syncro!',
        'title' => 'Bem-vindo, :name!',
        'body' => 'Sua conta na :tenant está ativa. Aqui estão os primeiros passos:',
        'step1_title' => 'Configure seu pipeline',
        'step1_desc' => 'Crie etapas para organizar seus leads.',
        'step2_title' => 'Importe seus contatos',
        'step2_desc' => 'Suba uma planilha ou adicione manualmente.',
        'step3_title' => 'Conecte seu WhatsApp',
        'step3_desc' => 'Responda seus clientes direto pelo painel.',
        'cta' => 'Acessar minha conta →',
        'trial_badge' => '14 dias de teste grátis — sem cartão de crédito',
    ],

    // Verify Email
    'verify' => [
        'subject' => 'Confirme seu email — Syncro',
        'title' => 'Confirme seu email',
        'greeting' => 'Olá, :name! Bem-vindo à Syncro.',
        'body' => 'Para ativar sua conta, confirme seu email clicando no botão abaixo.',
        'cta' => 'Confirmar meu email →',
        'expire' => 'Este link expira em :hours horas.',
        'cant_click' => 'Não conseguiu clicar? Copie e cole no navegador:',
        'ignore' => 'Se você não criou uma conta na Syncro, ignore este email.',
    ],

    // Reset Password
    'reset' => [
        'subject' => 'Redefinição de senha — Syncro',
        'title' => 'Redefinição de senha',
        'greeting' => 'Olá, :name! Recebemos uma solicitação para redefinir sua senha.',
        'warning' => 'Este link é válido por :minutes minutos.',
        'warning_label' => 'Atenção:',
        'cta' => 'Redefinir minha senha →',
        'cant_click' => 'Não conseguiu clicar? Copie e cole:',
        'ignore' => 'Se você não solicitou, ignore este email.',
    ],

    // Reengagement
    'reengagement' => [
        'title' => ':name, seus leads estão esperando!',
        'cta' => 'Acessar minha conta →',
    ],

    // Partner Approved
    'partner_approved' => [
        'subject' => 'Seu cadastro de parceiro foi aprovado!',
        'title' => 'Bem-vindo ao programa de parceiros!',
        'body' => 'Olá, :name! Seu cadastro como parceiro da :tenant foi aprovado. Você já pode acessar a plataforma e gerenciar seus clientes.',
        'code_label' => 'Seu código de parceiro',
        'code_hint' => 'Compartilhe com seus clientes para vinculá-los à sua agência.',
        'cta' => 'Acessar minha conta →',
        'includes_title' => 'O que está incluso:',
        'include_1' => 'Plano Partner gratuito',
        'include_2' => 'Gerencie contas dos seus clientes',
        'include_3' => 'Leads e pipelines ilimitados',
        'include_4' => 'Agentes de IA inclusos',
        'include_5' => 'Suporte prioritário',
    ],

    // Verify Agency Email
    'verify_agency' => [
        'subject' => 'Confirme seu email de parceiro — Syncro',
        'title' => 'Confirme seu email de parceiro',
        'body' => 'Olá, :name! Sua agência :tenant foi cadastrada no Programa de Parceiros.',
        'next_step_title' => 'Próximo passo',
        'next_step_body' => 'Após confirmar seu email, seu cadastro será analisado pela nossa equipe. Você receberá uma notificação quando aprovado (geralmente em até 24h).',
        'cta' => 'Confirmar meu email →',
        'cant_click' => 'Não conseguiu clicar? Copie e cole:',
        'ignore' => 'Se você não se cadastrou como parceiro, ignore este email.',
    ],

    // Agency Referral Notification
    'agency_referral' => [
        'subject' => 'Novo cliente indicado — :client',
        'title' => ':name, você tem um novo cliente!',
        'body' => 'Um novo cliente se cadastrou na Syncro usando o seu código de agência parceira.',
        'client_label' => 'Novo cliente',
        'registered_at' => 'Cadastrado em :date',
        'total_clients_label' => 'Total de clientes indicados',
        'cta' => 'Ver meus clientes',
        'footer_note' => 'Você está recebendo este email porque é parceiro Syncro.',
    ],

    // Partner Client Unlinked
    'partner_unlinked' => [
        'subject' => 'Cliente desvinculado — Syncro',
        'title' => ':name, um cliente se desvinculou.',
        'body' => 'O cliente abaixo se desvinculou da sua agência parceira no Syncro.',
        'client_label' => 'Cliente desvinculado',
        'unlinked_at' => 'Desvinculado em :date',
        'commission_title' => 'O que acontece com suas comissões?',
        'commission_pending' => 'Comissões pendentes (em período de carência) foram canceladas.',
        'commission_released' => 'Comissões já liberadas ou sacadas foram mantidas integralmente.',
        'cta' => 'Acessar painel de parceiro',
        'footer_note' => 'Você está recebendo este email porque é parceiro Syncro. Para dúvidas sobre comissões, entre em contato com o suporte.',
    ],

    // Subscription Activated
    'subscription_activated' => [
        'subject' => 'Assinatura confirmada — Syncro',
        'title' => 'Parabéns, :name!',
        'body' => 'Sua assinatura da :tenant foi confirmada com sucesso.',
        'body_with_plan' => 'no plano :plan (R$ :price/mês)',
        'welcome_message' => 'Bem-vindo ao time Syncro!',
        'billing_note' => 'A cobrança é mensal e renovada automaticamente.',
        'cta' => 'Acessar minha conta',
    ],

    // Subscription Cancelled
    'subscription_cancelled' => [
        'subject' => 'Assinatura cancelada — Syncro',
        'title' => 'Olá, :name.',
        'body' => 'Confirmamos o cancelamento da assinatura da :tenant.',
        'body_with_plan' => 'Seu acesso ao plano :plan foi encerrado.',
        'reactivate_note' => 'Se você cancelou por engano ou quer reativar sua conta, basta assinar novamente a qualquer momento.',
        'support_question' => 'Algum problema que podemos resolver?',
    ],

    // Trial Ending Soon
    'trial_ending' => [
        'subject' => 'Seu trial expira em :days dias — Syncro',
        'title_last_day' => 'Último dia de trial!',
        'title_days' => ':days dias restantes',
        'subtitle' => 'Seu período de teste está acabando',
        'greeting' => 'Olá, :name!',
        'body_last_day' => 'Seu trial na :tenant expira hoje. Para continuar usando a plataforma sem interrupção, assine agora.',
        'body_days' => 'Seu trial na :tenant expira em :days dias. Para não perder o acesso, assine antes do vencimento.',
        'cta' => 'Assinar agora',
        'lose_title' => 'O que você perde ao expirar:',
        'lose_1' => 'Acesso ao CRM e kanban de leads',
        'lose_2' => 'Histórico de conversas WhatsApp',
        'lose_3' => 'Automações e agentes de IA',
        'lose_4' => 'Relatórios e dashboards',
    ],

    // Payment Failed
    'payment_failed' => [
        'subject' => 'Falha no pagamento — Syncro',
        'title' => 'Falha no pagamento',
        'greeting' => 'Olá, :name!',
        'body' => 'Houve uma falha ao cobrar a mensalidade da :tenant. Para evitar a suspensão do seu acesso, regularize o pagamento o quanto antes.',
        'cta' => 'Regularizar pagamento',
        'warning' => 'Se o problema persistir, entre em contato com seu banco ou atualize os dados do cartão na página de cobrança.',
        'support_question' => 'Precisa de ajuda?',
    ],

    // Upsell Upgrade
    'upsell' => [
        'subject' => 'Hora de crescer — Syncro',
        'greeting' => 'Olá, :name!',
        'cta' => 'Ver planos',
        'why_title' => 'Por que fazer upgrade?',
        'why_1' => 'Mais leads, usuários e pipelines',
        'why_2' => 'Recursos avançados de IA e automação',
        'why_3' => 'Maior capacidade para crescer seu negócio',
        'support_question' => 'Dúvidas? Fale conosco em',
    ],
];
