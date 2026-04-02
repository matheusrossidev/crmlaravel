<?php

declare(strict_types=1);

return [
    // Common
    'common' => [
        'footer_support' => 'Questions? suporte@syncro.chat',
        'footer_support_text' => 'Questions?',
        'footer_copyright' => '© :year Syncro · syncro.chat',
        'cta_access' => 'Access my account →',
        'trial_badge' => '14-day free trial — no credit card required',
        'cant_click' => 'Can\'t click? Copy and paste into your browser:',
    ],

    // Welcome
    'welcome' => [
        'subject' => 'Welcome to Syncro!',
        'title' => 'Welcome, :name!',
        'body' => 'Your account at :tenant is active. Here are your first steps:',
        'step1_title' => 'Set up your pipeline',
        'step1_desc' => 'Create stages to organize your leads.',
        'step2_title' => 'Import your contacts',
        'step2_desc' => 'Upload a spreadsheet or add manually.',
        'step3_title' => 'Connect your WhatsApp',
        'step3_desc' => 'Reply to your clients directly from the panel.',
        'cta' => 'Access my account →',
        'trial_badge' => '14-day free trial — no credit card required',
    ],

    // Verify Email
    'verify' => [
        'subject' => 'Confirm your email — Syncro',
        'title' => 'Confirm your email',
        'greeting' => 'Hello, :name! Welcome to Syncro.',
        'body' => 'To activate your account, confirm your email by clicking the button below.',
        'cta' => 'Confirm my email →',
        'expire' => 'This link expires in :hours hours.',
        'cant_click' => 'Can\'t click? Copy and paste into your browser:',
        'ignore' => 'If you did not create an account on Syncro, please ignore this email.',
    ],

    // Reset Password
    'reset' => [
        'subject' => 'Password reset — Syncro',
        'title' => 'Password reset',
        'greeting' => 'Hello, :name! We received a request to reset your password.',
        'warning' => 'This link is valid for :minutes minutes.',
        'warning_label' => 'Attention:',
        'cta' => 'Reset my password →',
        'cant_click' => 'Can\'t click? Copy and paste:',
        'ignore' => 'If you did not request this, please ignore this email.',
    ],

    // Reengagement
    'reengagement' => [
        'title' => ':name, your leads are waiting!',
        'cta' => 'Access my account →',
    ],

    // Partner Approved
    'partner_approved' => [
        'subject' => 'Your partner registration is approved!',
        'title' => 'Welcome to the partner program!',
        'body' => 'Hello, :name! Your registration as a partner of :tenant has been approved. You can now access the platform and manage your clients.',
        'code_label' => 'Your partner code',
        'code_hint' => 'Share with your clients to link them to your agency.',
        'cta' => 'Access my account →',
        'includes_title' => 'What\'s included:',
        'include_1' => 'Free Partner plan',
        'include_2' => 'Manage your clients\' accounts',
        'include_3' => 'Unlimited leads and pipelines',
        'include_4' => 'AI agents included',
        'include_5' => 'Priority support',
    ],

    // Verify Agency Email
    'verify_agency' => [
        'subject' => 'Confirm your partner email — Syncro',
        'title' => 'Confirm your partner email',
        'body' => 'Hello, :name! Your agency :tenant has been registered in the Partner Program.',
        'next_step_title' => 'Next step',
        'next_step_body' => 'After confirming your email, your registration will be reviewed by our team. You\'ll receive a notification when approved (usually within 24h).',
        'cta' => 'Confirm my email →',
        'cant_click' => 'Can\'t click? Copy and paste:',
        'ignore' => 'If you did not register as a partner, please ignore this email.',
    ],

    // Agency Referral Notification
    'agency_referral' => [
        'subject' => 'New client referral — :client',
        'title' => ':name, you have a new client!',
        'body' => 'A new client signed up on Syncro using your agency partner code.',
        'client_label' => 'New client',
        'registered_at' => 'Registered on :date',
        'total_clients_label' => 'Total referred clients',
        'cta' => 'View my clients',
        'footer_note' => 'You are receiving this email because you are a Syncro partner.',
    ],

    // Partner Client Unlinked
    'partner_unlinked' => [
        'subject' => 'Client unlinked — Syncro',
        'title' => ':name, a client has been unlinked.',
        'body' => 'The client below has been unlinked from your partner agency on Syncro.',
        'client_label' => 'Unlinked client',
        'unlinked_at' => 'Unlinked on :date',
        'commission_title' => 'What happens to your commissions?',
        'commission_pending' => 'Pending commissions (in grace period) have been cancelled.',
        'commission_released' => 'Released or withdrawn commissions have been kept in full.',
        'cta' => 'Access partner panel',
        'footer_note' => 'You are receiving this email because you are a Syncro partner. For commission questions, contact support.',
    ],

    // Subscription Activated
    'subscription_activated' => [
        'subject' => 'Subscription confirmed — Syncro',
        'title' => 'Congratulations, :name!',
        'body' => 'Your subscription for :tenant has been confirmed successfully.',
        'body_with_plan' => 'on the :plan plan (R$ :price/month)',
        'welcome_message' => 'Welcome to the Syncro team!',
        'billing_note' => 'Billing is monthly and renewed automatically.',
        'cta' => 'Access my account',
    ],

    // Subscription Cancelled
    'subscription_cancelled' => [
        'subject' => 'Subscription cancelled — Syncro',
        'title' => 'Hello, :name.',
        'body' => 'We confirm the cancellation of the subscription for :tenant.',
        'body_with_plan' => 'Your access to the :plan plan has been ended.',
        'reactivate_note' => 'If you cancelled by mistake or want to reactivate your account, just subscribe again at any time.',
        'support_question' => 'Any issue we can help resolve?',
    ],

    // Trial Ending Soon
    'trial_ending' => [
        'subject' => 'Your trial expires in :days days — Syncro',
        'title_last_day' => 'Last day of trial!',
        'title_days' => ':days days remaining',
        'subtitle' => 'Your trial period is ending',
        'greeting' => 'Hello, :name!',
        'body_last_day' => 'Your trial at :tenant expires today. To continue using the platform without interruption, subscribe now.',
        'body_days' => 'Your trial at :tenant expires in :days days. To keep your access, subscribe before it ends.',
        'cta' => 'Subscribe now',
        'lose_title' => 'What you lose when it expires:',
        'lose_1' => 'Access to CRM and lead kanban',
        'lose_2' => 'WhatsApp conversation history',
        'lose_3' => 'Automations and AI agents',
        'lose_4' => 'Reports and dashboards',
    ],

    // Payment Failed
    'payment_failed' => [
        'subject' => 'Payment failed — Syncro',
        'title' => 'Payment failed',
        'greeting' => 'Hello, :name!',
        'body' => 'There was a failure charging the monthly fee for :tenant. To avoid suspension of your access, please resolve the payment as soon as possible.',
        'cta' => 'Resolve payment',
        'warning' => 'If the problem persists, contact your bank or update your card details on the billing page.',
        'support_question' => 'Need help?',
    ],

    // Upsell Upgrade
    'upsell' => [
        'subject' => 'Time to grow — Syncro',
        'greeting' => 'Hello, :name!',
        'cta' => 'View plans',
        'why_title' => 'Why upgrade?',
        'why_1' => 'More leads, users, and pipelines',
        'why_2' => 'Advanced AI and automation features',
        'why_3' => 'Greater capacity to grow your business',
        'support_question' => 'Questions? Contact us at',
    ],
];
