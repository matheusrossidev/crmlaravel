---
auto_generated: true
type: routes
tags: [routes, auto]
---

# Routes (auto-gerado)

> `php artisan obsidian:sync --routes` regenera essa nota. Não edite à mão.

Total: 458 rotas

## /root

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `//` | `dashboard` | `Tenant\DashboardController@index` |

## /2fa

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/2fa/challenge` | `2fa.challenge` | `Auth\TwoFactorController@showChallenge` |
| `POST` | `/2fa/challenge` | `2fa.verify` | `Auth\TwoFactorController@verifyChallenge` |

## /agencia

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/agencia/acessar/{tenant}` | `agency.access.enter` | `Tenant\AgencyAccessController@enter` |
| `GET|HEAD` | `/agencia/meus-clientes` | `agency.clients` | `Tenant\AgencyAccessController@clients` |
| `POST` | `/agencia/sair` | `agency.access.exit` | `Tenant\AgencyAccessController@exit` |

## /agenda

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/agenda` | `calendar.index` | `Tenant\CalendarController@index` |
| `GET|HEAD` | `/agenda/calendarios` | `calendar.calendars` | `Tenant\CalendarController@calendars` |
| `GET|HEAD` | `/agenda/eventos` | `calendar.events` | `Tenant\CalendarController@events` |
| `POST` | `/agenda/eventos` | `calendar.store` | `Tenant\CalendarController@store` |
| `PUT` | `/agenda/eventos/{id}` | `calendar.update` | `Tenant\CalendarController@update` |
| `DELETE` | `/agenda/eventos/{id}` | `calendar.destroy` | `Tenant\CalendarController@destroy` |
| `POST` | `/agenda/preferencias` | `calendar.preferences` | `Tenant\CalendarController@savePreferences` |

## /analyst-suggestions

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/analyst-suggestions/pending-count` | `analyst.pending-count` | `Tenant\AiAnalystController@pendingCount` |
| `POST` | `/analyst-suggestions/{suggestion}/approve` | `analyst.approve` | `Tenant\AiAnalystController@approve` |
| `POST` | `/analyst-suggestions/{suggestion}/reject` | `analyst.reject` | `Tenant\AiAnalystController@reject` |

## /api

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/api/internal/agno/conversations/{convId}/notify-intent` | `` | `Api\AgnoToolsController@notifyIntent` |
| `POST` | `/api/internal/agno/conversations/{convId}/transfer` | `` | `Api\AgnoToolsController@transferToHuman` |
| `PUT` | `/api/internal/agno/leads/{leadId}/stage` | `` | `Api\AgnoToolsController@setStage` |
| `POST` | `/api/internal/agno/leads/{leadId}/tags` | `` | `Api\AgnoToolsController@addTag` |
| `POST` | `/api/v1/leads` | `` | `Api\LeadController@store` |
| `GET|HEAD` | `/api/v1/leads/{lead}` | `` | `Api\LeadController@show` |
| `DELETE` | `/api/v1/leads/{lead}` | `` | `Api\LeadController@destroy` |
| `PUT` | `/api/v1/leads/{lead}/lost` | `` | `Api\LeadController@lost` |
| `PUT` | `/api/v1/leads/{lead}/stage` | `` | `Api\LeadController@stage` |
| `PUT` | `/api/v1/leads/{lead}/won` | `` | `Api\LeadController@won` |
| `GET|HEAD` | `/api/v1/pipelines` | `` | `Api\PipelineController@index` |
| `POST` | `/api/webhook/asaas` | `asaas.webhook` | `AsaasWebhookController@handle` |
| `GET|HEAD` | `/api/webhook/facebook/leadgen` | `facebook.leadgen.webhook.verify` | `FacebookLeadgenWebhookController@verify` |
| `POST` | `/api/webhook/facebook/leadgen` | `facebook.leadgen.webhook.handle` | `FacebookLeadgenWebhookController@handle` |
| `GET|HEAD` | `/api/webhook/instagram` | `instagram.webhook.verify` | `InstagramWebhookController@verify` |
| `POST` | `/api/webhook/instagram` | `instagram.webhook.handle` | `InstagramWebhookController@handle` |
| `POST` | `/api/webhook/stripe` | `stripe.webhook` | `StripeWebhookController@handle` |
| `POST` | `/api/webhook/waha` | `waha.webhook` | `WhatsappWebhookController@handle` |
| `GET|HEAD` | `/api/webhook/whatsapp-cloud` | `whatsapp-cloud.webhook.verify` | `WhatsappCloudWebhookController@verify` |
| `POST` | `/api/webhook/whatsapp-cloud` | `whatsapp-cloud.webhook.handle` | `WhatsappCloudWebhookController@handle` |
| `GET|HEAD` | `/api/widget/{token}.js` | `` | `Api\WebsiteWidgetController@script` |
| `GET|POST|HEAD` | `/api/widget/{token}/init` | `` | `Api\WebsiteWidgetController@init` |
| `POST` | `/api/widget/{token}/message` | `` | `Api\WebsiteWidgetController@message` |
| `GET|HEAD` | `/api/widget/{token}/wa-button.js` | `` | `Api\WebsiteWidgetController@waButtonScript` |
| `POST` | `/api/widget/{token}/wa-click` | `` | `Api\WebsiteWidgetController@trackWaClick` |

## /broadcasting

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|POST|HEAD` | `/broadcasting/auth` | `` | `\Illuminate\Broadcasting\BroadcastController@authenticate` |

## /busca

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/busca` | `global.search` | `Tenant\GlobalSearchController@search` |

## /campanhas

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/campanhas` | `campaigns.index` | `Tenant\CampaignController@index` |
| `GET|HEAD` | `/campanhas/analytics` | `campaigns.analytics` | `Tenant\CampaignController@analytics` |
| `GET|HEAD` | `/campanhas/drill-down` | `campaigns.drill-down` | `Tenant\CampaignController@drillDown` |

## /chat

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/chat/{tenantSlug}/{botSlug}` | `chatbot.hosted` | `Api\WebsiteWidgetController@hostedPage` |

## /chatbot

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/chatbot/fluxos` | `chatbot.flows.index` | `Tenant\ChatbotFlowController@index` |
| `POST` | `/chatbot/fluxos` | `chatbot.flows.store` | `Tenant\ChatbotFlowController@store` |
| `GET|HEAD` | `/chatbot/fluxos/criar` | `chatbot.flows.create` | `Tenant\ChatbotFlowController@create` |
| `GET|HEAD` | `/chatbot/fluxos/onboarding` | `chatbot.flows.onboarding` | `Tenant\ChatbotFlowController@onboarding` |
| `GET|HEAD` | `/chatbot/fluxos/pipelines` | `chatbot.flows.pipelines` | `Tenant\ChatbotFlowController@getPipelines` |
| `POST` | `/chatbot/fluxos/upload-image` | `chatbot.flows.upload-image` | `Tenant\ChatbotFlowController@uploadImage` |
| `PUT` | `/chatbot/fluxos/{flow}` | `chatbot.flows.update` | `Tenant\ChatbotFlowController@update` |
| `DELETE` | `/chatbot/fluxos/{flow}` | `chatbot.flows.destroy` | `Tenant\ChatbotFlowController@destroy` |
| `GET|HEAD` | `/chatbot/fluxos/{flow}/editar` | `chatbot.flows.edit` | `Tenant\ChatbotFlowController@edit` |
| `PUT` | `/chatbot/fluxos/{flow}/graph` | `chatbot.flows.graph` | `Tenant\ChatbotFlowController@saveGraph` |
| `PUT` | `/chatbot/fluxos/{flow}/graph-react` | `chatbot.flows.graph-react` | `Tenant\ChatbotFlowController@saveGraphReact` |
| `GET|HEAD` | `/chatbot/fluxos/{flow}/resultados` | `chatbot.flows.results` | `Tenant\ChatbotFlowController@results` |
| `POST` | `/chatbot/fluxos/{flow}/toggle` | `chatbot.flows.toggle` | `Tenant\ChatbotFlowController@toggle` |

## /chats

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/chats` | `chats.index` | `Tenant\WhatsappController@index` |
| `GET|HEAD` | `/chats/conversations/{conversation}` | `chats.conversations.show` | `Tenant\WhatsappController@show` |
| `DELETE` | `/chats/conversations/{conversation}` | `chats.conversations.destroy` | `Tenant\WhatsappController@destroy` |
| `PUT` | `/chats/conversations/{conversation}/ai-agent` | `chats.conversations.ai-agent` | `Tenant\WhatsappController@assignAiAgent` |
| `PUT` | `/chats/conversations/{conversation}/assign` | `chats.conversations.assign` | `Tenant\WhatsappController@assign` |
| `PUT` | `/chats/conversations/{conversation}/chatbot-flow` | `chats.conversations.chatbot-flow` | `Tenant\WhatsappController@assignChatbotFlow` |
| `PUT` | `/chats/conversations/{conversation}/contact` | `chats.conversations.contact` | `Tenant\WhatsappController@updateContact` |
| `PUT` | `/chats/conversations/{conversation}/department` | `chats.conversations.department` | `Tenant\WhatsappController@assignDepartment` |
| `PUT` | `/chats/conversations/{conversation}/lead` | `chats.conversations.lead` | `Tenant\WhatsappController@updateLead` |
| `PUT` | `/chats/conversations/{conversation}/link-lead` | `chats.conversations.link-lead` | `Tenant\WhatsappController@linkLead` |
| `POST` | `/chats/conversations/{conversation}/messages` | `chats.messages.store` | `Tenant\WhatsappMessageController@store` |
| `POST` | `/chats/conversations/{conversation}/react` | `chats.messages.react` | `Tenant\WhatsappMessageController@react` |
| `POST` | `/chats/conversations/{conversation}/read` | `chats.conversations.read` | `Tenant\WhatsappController@markRead` |
| `PUT` | `/chats/conversations/{conversation}/status` | `chats.conversations.status` | `Tenant\WhatsappController@updateStatus` |
| `PUT` | `/chats/conversations/{conversation}/unlink-lead` | `chats.conversations.unlink-lead` | `Tenant\WhatsappController@unlinkLead` |
| `PUT` | `/chats/inbox/{channel}/{conversation}/contact` | `chats.inbox.conversations.contact` | `Tenant\WhatsappController@updateConversationContact` |
| `GET|HEAD` | `/chats/instagram-conversations/{conversation}` | `chats.ig-conversations.show` | `Tenant\WhatsappController@showInstagram` |
| `DELETE` | `/chats/instagram-conversations/{conversation}` | `chats.ig-conversations.destroy` | `Tenant\WhatsappController@destroyInstagram` |
| `PUT` | `/chats/instagram-conversations/{conversation}/link-lead` | `chats.ig-conversations.link-lead` | `Tenant\WhatsappController@linkLeadInstagram` |
| `POST` | `/chats/instagram-conversations/{conversation}/messages` | `chats.ig-conversations.messages` | `Tenant\WhatsappController@sendInstagramMessage` |
| `POST` | `/chats/instagram-conversations/{conversation}/read` | `chats.ig-conversations.read` | `Tenant\WhatsappController@markReadInstagram` |
| `PUT` | `/chats/instagram-conversations/{conversation}/unlink-lead` | `chats.ig-conversations.unlink-lead` | `Tenant\WhatsappController@unlinkLeadInstagram` |
| `GET|HEAD` | `/chats/leads/search` | `chats.leads.search` | `Tenant\WhatsappController@searchLeads` |
| `GET|HEAD` | `/chats/poll` | `chats.poll` | `Tenant\WhatsappController@poll` |
| `GET|HEAD` | `/chats/quick-messages` | `chats.quick-messages.index` | `Tenant\QuickMessageController@index` |
| `POST` | `/chats/quick-messages` | `chats.quick-messages.store` | `Tenant\QuickMessageController@store` |
| `PUT` | `/chats/quick-messages/{qm}` | `chats.quick-messages.update` | `Tenant\QuickMessageController@update` |
| `DELETE` | `/chats/quick-messages/{qm}` | `chats.quick-messages.destroy` | `Tenant\QuickMessageController@destroy` |
| `GET|HEAD` | `/chats/website-conversations/{websiteConversation}` | `chats.website-conversations.show` | `Tenant\WhatsappController@showWebsite` |
| `DELETE` | `/chats/website-conversations/{websiteConversation}` | `chats.website-conversations.destroy` | `Tenant\WhatsappController@destroyWebsite` |
| `PUT` | `/chats/website-conversations/{websiteConversation}/link-lead` | `chats.website-conversations.link-lead` | `Tenant\WhatsappController@linkLeadWebsite` |
| `POST` | `/chats/website-conversations/{websiteConversation}/read` | `chats.website-conversations.read` | `Tenant\WhatsappController@markReadWebsite` |
| `PUT` | `/chats/website-conversations/{websiteConversation}/status` | `chats.website-conversations.status` | `Tenant\WhatsappController@updateStatusWebsite` |
| `PUT` | `/chats/website-conversations/{websiteConversation}/unlink-lead` | `chats.website-conversations.unlink-lead` | `Tenant\WhatsappController@unlinkLeadWebsite` |
| `GET|HEAD` | `/chats/{conversation}/analyst-suggestions` | `chats.analyst.index` | `Tenant\AiAnalystController@index` |
| `POST` | `/chats/{conversation}/analyst-suggestions/approve-all` | `chats.analyst.approve-all` | `Tenant\AiAnalystController@approveAll` |
| `POST` | `/chats/{conversation}/analyze` | `chats.analyst.trigger` | `Tenant\AiAnalystController@trigger` |

## /cobranca

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/cobranca/assinar` | `billing.subscribe` | `Tenant\BillingController@subscribe` |
| `POST` | `/cobranca/cancelar` | `billing.cancel` | `Tenant\BillingController@cancel` |
| `GET|HEAD` | `/cobranca/checkout` | `billing.checkout` | `Tenant\BillingController@showCheckout` |
| `POST` | `/cobranca/stripe/assinar` | `billing.stripe.subscribe` | `Tenant\BillingController@stripeSubscribe` |
| `GET|HEAD` | `/cobranca/stripe/cancel` | `billing.stripe.cancel` | `Tenant\BillingController@stripeCancel` |
| `GET|HEAD` | `/cobranca/stripe/portal` | `billing.stripe.portal` | `Tenant\BillingController@stripePortal` |
| `GET|HEAD` | `/cobranca/stripe/success` | `billing.stripe.success` | `Tenant\BillingController@stripeSuccess` |

## /configuracoes

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/configuracoes/agencia-parceira` | `settings.agency.link` | `Tenant\AgencyAccessController@linkCode` |
| `POST` | `/configuracoes/agencia-parceira/desvincular` | `settings.agency.unlink` | `Tenant\AgencyAccessController@unlinkPartner` |
| `POST` | `/configuracoes/agencia-parceira/trocar` | `settings.agency.switch` | `Tenant\AgencyAccessController@switchPartner` |
| `GET|HEAD` | `/configuracoes/api-keys` | `settings.api-keys` | `Tenant\ApiKeyController@index` |
| `POST` | `/configuracoes/api-keys` | `settings.api-keys.store` | `Tenant\ApiKeyController@store` |
| `DELETE` | `/configuracoes/api-keys/{apiKey}` | `settings.api-keys.destroy` | `Tenant\ApiKeyController@destroy` |
| `GET|HEAD` | `/configuracoes/auditoria` | `settings.audit-log` | `Tenant\AuditLogController@index` |
| `GET|HEAD` | `/configuracoes/auditoria/{log}` | `settings.audit-log.show` | `Tenant\AuditLogController@show` |
| `GET|HEAD` | `/configuracoes/automacoes` | `settings.automations` | `Tenant\AutomationController@index` |
| `POST` | `/configuracoes/automacoes` | `settings.automations.store` | `Tenant\AutomationController@store` |
| `GET|HEAD` | `/configuracoes/automacoes/criar` | `settings.automations.create` | `Tenant\AutomationController@create` |
| `POST` | `/configuracoes/automacoes/templates/{slug}/install` | `settings.automations.templates.install` | `Tenant\AutomationController@installTemplate` |
| `POST` | `/configuracoes/automacoes/test-webhook` | `settings.automations.test-webhook` | `Tenant\AutomationController@testWebhook` |
| `PUT` | `/configuracoes/automacoes/{automation}` | `settings.automations.update` | `Tenant\AutomationController@update` |
| `DELETE` | `/configuracoes/automacoes/{automation}` | `settings.automations.destroy` | `Tenant\AutomationController@destroy` |
| `GET|HEAD` | `/configuracoes/automacoes/{automation}/editar` | `settings.automations.edit` | `Tenant\AutomationController@edit` |
| `PATCH` | `/configuracoes/automacoes/{automation}/toggle` | `settings.automations.toggle` | `Tenant\AutomationController@toggle` |
| `GET|HEAD` | `/configuracoes/campos-extras` | `settings.custom-fields` | `Tenant\CustomFieldController@index` |
| `POST` | `/configuracoes/campos-extras` | `settings.custom-fields.store` | `Tenant\CustomFieldController@store` |
| `PUT` | `/configuracoes/campos-extras/{field}` | `settings.custom-fields.update` | `Tenant\CustomFieldController@update` |
| `DELETE` | `/configuracoes/campos-extras/{field}` | `settings.custom-fields.destroy` | `Tenant\CustomFieldController@destroy` |
| `GET|HEAD` | `/configuracoes/cobranca` | `settings.billing` | `Tenant\BillingController@index` |
| `GET|HEAD` | `/configuracoes/departamentos` | `settings.departments` | `Tenant\DepartmentController@index` |
| `POST` | `/configuracoes/departamentos` | `settings.departments.store` | `Tenant\DepartmentController@store` |
| `PUT` | `/configuracoes/departamentos/{department}` | `settings.departments.update` | `Tenant\DepartmentController@update` |
| `DELETE` | `/configuracoes/departamentos/{department}` | `settings.departments.destroy` | `Tenant\DepartmentController@destroy` |
| `GET|HEAD` | `/configuracoes/instagram-automacoes` | `settings.ig-automations.index` | `Tenant\InstagramAutomationController@index` |
| `POST` | `/configuracoes/instagram-automacoes` | `settings.ig-automations.store` | `Tenant\InstagramAutomationController@store` |
| `GET|HEAD` | `/configuracoes/instagram-automacoes/posts` | `settings.ig-automations.posts` | `Tenant\InstagramAutomationController@posts` |
| `PUT` | `/configuracoes/instagram-automacoes/{automation}` | `settings.ig-automations.update` | `Tenant\InstagramAutomationController@update` |
| `DELETE` | `/configuracoes/instagram-automacoes/{automation}` | `settings.ig-automations.destroy` | `Tenant\InstagramAutomationController@destroy` |
| `PATCH` | `/configuracoes/instagram-automacoes/{automation}/toggle` | `settings.ig-automations.toggle` | `Tenant\InstagramAutomationController@toggleActive` |
| `GET|HEAD` | `/configuracoes/integracoes` | `settings.integrations.index` | `Tenant\IntegrationController@index` |
| `DELETE` | `/configuracoes/integracoes/facebook-leadads` | `settings.integrations.facebook-leadads.disconnect` | `Tenant\IntegrationController@disconnectFacebookLeadAds` |
| `GET|HEAD` | `/configuracoes/integracoes/facebook-leadads/callback` | `settings.integrations.facebook-leadads.callback` | `Tenant\IntegrationController@callbackFacebookLeadAds` |
| `POST` | `/configuracoes/integracoes/facebook-leadads/connections` | `settings.integrations.facebook-leadads.connections.store` | `Tenant\IntegrationController@storeFbLeadConnection` |
| `PUT` | `/configuracoes/integracoes/facebook-leadads/connections/{connection}` | `settings.integrations.facebook-leadads.connections.update` | `Tenant\IntegrationController@updateFbLeadConnection` |
| `DELETE` | `/configuracoes/integracoes/facebook-leadads/connections/{connection}` | `settings.integrations.facebook-leadads.connections.destroy` | `Tenant\IntegrationController@destroyFbLeadConnection` |
| `GET|HEAD` | `/configuracoes/integracoes/facebook-leadads/forms` | `settings.integrations.facebook-leadads.forms` | `Tenant\IntegrationController@getFacebookLeadAdsForms` |
| `GET|HEAD` | `/configuracoes/integracoes/facebook-leadads/pages` | `settings.integrations.facebook-leadads.pages` | `Tenant\IntegrationController@getFacebookLeadAdsPages` |
| `GET|HEAD` | `/configuracoes/integracoes/facebook-leadads/redirect` | `settings.integrations.facebook-leadads.redirect` | `Tenant\IntegrationController@redirectFacebookLeadAds` |
| `GET|HEAD` | `/configuracoes/integracoes/facebook-leadads/search-page` | `settings.integrations.facebook-leadads.search-page` | `Tenant\IntegrationController@searchFacebookLeadAdsPage` |
| `GET|HEAD` | `/configuracoes/integracoes/google/callback` | `settings.integrations.google.callback` | `Tenant\IntegrationController@callbackGoogle` |
| `GET|HEAD` | `/configuracoes/integracoes/google/redirect` | `settings.integrations.google.redirect` | `Tenant\IntegrationController@redirectGoogle` |
| `DELETE` | `/configuracoes/integracoes/instagram` | `settings.integrations.instagram.disconnect` | `Tenant\IntegrationController@disconnectInstagram` |
| `GET|HEAD` | `/configuracoes/integracoes/instagram/callback` | `settings.integrations.instagram.callback` | `Tenant\IntegrationController@callbackInstagram` |
| `GET|HEAD` | `/configuracoes/integracoes/instagram/redirect` | `settings.integrations.instagram.redirect` | `Tenant\IntegrationController@redirectInstagram` |
| `POST` | `/configuracoes/integracoes/wa-button` | `settings.integrations.wa-button.store` | `Tenant\IntegrationController@storeWaButton` |
| `PUT` | `/configuracoes/integracoes/wa-button/{waButton}` | `settings.integrations.wa-button.update` | `Tenant\IntegrationController@updateWaButton` |
| `DELETE` | `/configuracoes/integracoes/wa-button/{waButton}` | `settings.integrations.wa-button.destroy` | `Tenant\IntegrationController@destroyWaButton` |
| `GET|HEAD` | `/configuracoes/integracoes/whatsapp-cloud/callback` | `settings.integrations.whatsapp-cloud.callback` | `Tenant\IntegrationController@callbackWhatsappCloud` |
| `POST` | `/configuracoes/integracoes/whatsapp-cloud/exchange` | `settings.integrations.whatsapp-cloud.exchange` | `Tenant\IntegrationController@exchangeWhatsappCloud` |
| `GET|HEAD` | `/configuracoes/integracoes/whatsapp-cloud/redirect` | `settings.integrations.whatsapp-cloud.redirect` | `Tenant\IntegrationController@redirectWhatsappCloud` |
| `DELETE` | `/configuracoes/integracoes/whatsapp-cloud/{instance}` | `settings.integrations.whatsapp-cloud.disconnect` | `Tenant\IntegrationController@disconnectWhatsappCloud` |
| `POST` | `/configuracoes/integracoes/whatsapp/connect` | `settings.integrations.whatsapp.connect` | `Tenant\IntegrationController@connectWhatsapp` |
| `PUT` | `/configuracoes/integracoes/whatsapp/{instance}` | `settings.integrations.whatsapp.update` | `Tenant\IntegrationController@updateWhatsappInstance` |
| `DELETE` | `/configuracoes/integracoes/whatsapp/{instance}` | `settings.integrations.whatsapp.delete` | `Tenant\IntegrationController@deleteWhatsappInstance` |
| `DELETE` | `/configuracoes/integracoes/whatsapp/{instance}/disconnect` | `settings.integrations.whatsapp.disconnect` | `Tenant\IntegrationController@disconnectWhatsapp` |
| `POST` | `/configuracoes/integracoes/whatsapp/{instance}/import` | `settings.integrations.whatsapp.import` | `Tenant\IntegrationController@importHistoryWhatsapp` |
| `GET|HEAD` | `/configuracoes/integracoes/whatsapp/{instance}/import/progress` | `settings.integrations.whatsapp.import.progress` | `Tenant\IntegrationController@importProgress` |
| `POST` | `/configuracoes/integracoes/whatsapp/{instance}/primary` | `settings.integrations.whatsapp.primary` | `Tenant\IntegrationController@setPrimaryWhatsappInstance` |
| `GET|HEAD` | `/configuracoes/integracoes/whatsapp/{instance}/qr` | `settings.integrations.whatsapp.qr` | `Tenant\IntegrationController@getWhatsappQr` |
| `POST` | `/configuracoes/integracoes/whatsapp/{instance}/restart` | `settings.integrations.whatsapp.restart` | `Tenant\IntegrationController@restartWhatsapp` |
| `PUT` | `/configuracoes/integracoes/whatsapp/{instance}/users` | `settings.integrations.whatsapp.users.sync` | `Tenant\IntegrationController@syncWhatsappInstanceUsers` |
| `DELETE` | `/configuracoes/integracoes/{platform}` | `settings.integrations.disconnect` | `Tenant\IntegrationController@disconnect` |
| `GET|HEAD` | `/configuracoes/motivos-perda` | `settings.lost-reasons` | `Tenant\LostSaleReasonController@index` |
| `POST` | `/configuracoes/motivos-perda` | `settings.lost-reasons.store` | `Tenant\LostSaleReasonController@store` |
| `PUT` | `/configuracoes/motivos-perda/{reason}` | `settings.lost-reasons.update` | `Tenant\LostSaleReasonController@update` |
| `DELETE` | `/configuracoes/motivos-perda/{reason}` | `settings.lost-reasons.destroy` | `Tenant\LostSaleReasonController@destroy` |
| `GET|HEAD` | `/configuracoes/notificacoes` | `settings.notifications` | `Tenant\NotificationPreferenceController@index` |
| `PUT` | `/configuracoes/notificacoes` | `settings.notifications.update` | `Tenant\NotificationPreferenceController@update` |
| `GET|HEAD` | `/configuracoes/perfil` | `settings.profile` | `Tenant\ProfileController@index` |
| `PUT` | `/configuracoes/perfil` | `settings.profile.update` | `Tenant\ProfileController@update` |
| `POST` | `/configuracoes/perfil/avatar` | `settings.profile.avatar` | `Tenant\ProfileController@updateAvatar` |
| `PUT` | `/configuracoes/perfil/idioma` | `settings.profile.locale` | `Tenant\ProfileController@updateLocale` |
| `PUT` | `/configuracoes/perfil/senha` | `settings.profile.password` | `Tenant\ProfileController@updatePassword` |
| `GET|HEAD` | `/configuracoes/pipelines` | `settings.pipelines` | `Tenant\PipelineController@index` |
| `POST` | `/configuracoes/pipelines` | `settings.pipelines.store` | `Tenant\PipelineController@store` |
| `PUT` | `/configuracoes/pipelines/{pipeline}` | `settings.pipelines.update` | `Tenant\PipelineController@update` |
| `DELETE` | `/configuracoes/pipelines/{pipeline}` | `settings.pipelines.destroy` | `Tenant\PipelineController@destroy` |
| `POST` | `/configuracoes/pipelines/{pipeline}/stages` | `settings.pipelines.stages.store` | `Tenant\PipelineController@storeStage` |
| `POST` | `/configuracoes/pipelines/{pipeline}/stages/reorder` | `settings.pipelines.stages.reorder` | `Tenant\PipelineController@reorderStages` |
| `PUT` | `/configuracoes/pipelines/{pipeline}/stages/{stage}` | `settings.pipelines.stages.update` | `Tenant\PipelineController@updateStage` |
| `DELETE` | `/configuracoes/pipelines/{pipeline}/stages/{stage}` | `settings.pipelines.stages.destroy` | `Tenant\PipelineController@destroyStage` |
| `GET|HEAD` | `/configuracoes/produtos` | `settings.products` | `Tenant\ProductController@index` |
| `POST` | `/configuracoes/produtos` | `settings.products.store` | `Tenant\ProductController@store` |
| `POST` | `/configuracoes/produtos/categorias` | `settings.products.categories.store` | `Tenant\ProductController@storeCategory` |
| `PUT` | `/configuracoes/produtos/categorias/{category}` | `settings.products.categories.update` | `Tenant\ProductController@updateCategory` |
| `DELETE` | `/configuracoes/produtos/categorias/{category}` | `settings.products.categories.destroy` | `Tenant\ProductController@destroyCategory` |
| `PUT` | `/configuracoes/produtos/{product}` | `settings.products.update` | `Tenant\ProductController@update` |
| `DELETE` | `/configuracoes/produtos/{product}` | `settings.products.destroy` | `Tenant\ProductController@destroy` |
| `POST` | `/configuracoes/produtos/{product}/media` | `settings.products.media.upload` | `Tenant\ProductController@uploadMedia` |
| `DELETE` | `/configuracoes/produtos/{product}/media/{media}` | `settings.products.media.delete` | `Tenant\ProductController@deleteMedia` |
| `GET|HEAD` | `/configuracoes/scoring` | `settings.scoring` | `Tenant\LeadScoringController@index` |
| `POST` | `/configuracoes/scoring` | `settings.scoring.store` | `Tenant\LeadScoringController@store` |
| `PUT` | `/configuracoes/scoring/score-settings` | `settings.scoring.score-settings` | `Tenant\LeadScoringController@updateScoreSettings` |
| `POST` | `/configuracoes/scoring/templates/{slug}/install` | `settings.scoring.templates.install` | `Tenant\LeadScoringController@installTemplate` |
| `PUT` | `/configuracoes/scoring/{rule}` | `settings.scoring.update` | `Tenant\LeadScoringController@update` |
| `DELETE` | `/configuracoes/scoring/{rule}` | `settings.scoring.destroy` | `Tenant\LeadScoringController@destroy` |
| `GET|HEAD` | `/configuracoes/sequencias` | `settings.sequences` | `Tenant\NurtureSequenceController@index` |
| `POST` | `/configuracoes/sequencias` | `settings.sequences.store` | `Tenant\NurtureSequenceController@store` |
| `GET|HEAD` | `/configuracoes/sequencias/criar` | `settings.sequences.create` | `Tenant\NurtureSequenceController@create` |
| `POST` | `/configuracoes/sequencias/templates/{slug}/install` | `settings.sequences.templates.install` | `Tenant\NurtureSequenceController@installTemplate` |
| `PUT` | `/configuracoes/sequencias/{sequence}` | `settings.sequences.update` | `Tenant\NurtureSequenceController@update` |
| `DELETE` | `/configuracoes/sequencias/{sequence}` | `settings.sequences.destroy` | `Tenant\NurtureSequenceController@destroy` |
| `GET|HEAD` | `/configuracoes/sequencias/{sequence}/editar` | `settings.sequences.edit` | `Tenant\NurtureSequenceController@edit` |
| `POST` | `/configuracoes/sequencias/{sequence}/enroll` | `settings.sequences.enroll` | `Tenant\NurtureSequenceController@enroll` |
| `PATCH` | `/configuracoes/sequencias/{sequence}/toggle` | `settings.sequences.toggle` | `Tenant\NurtureSequenceController@toggle` |
| `DELETE` | `/configuracoes/sequencias/{sequence}/unenroll` | `settings.sequences.unenroll` | `Tenant\NurtureSequenceController@unenroll` |
| `GET|HEAD` | `/configuracoes/tags` | `settings.tags` | `Tenant\WhatsappTagController@index` |
| `POST` | `/configuracoes/tags` | `settings.tags.store` | `Tenant\WhatsappTagController@store` |
| `PUT` | `/configuracoes/tags/{tag}` | `settings.tags.update` | `Tenant\WhatsappTagController@update` |
| `DELETE` | `/configuracoes/tags/{tag}` | `settings.tags.destroy` | `Tenant\WhatsappTagController@destroy` |
| `POST` | `/configuracoes/tokens/comprar` | `settings.tokens.purchase` | `Tenant\TokenIncrementController@purchase` |
| `GET|HEAD` | `/configuracoes/usuarios` | `settings.users` | `Tenant\UserController@index` |
| `POST` | `/configuracoes/usuarios` | `settings.users.store` | `Tenant\UserController@store` |
| `PUT` | `/configuracoes/usuarios/{user}` | `settings.users.update` | `Tenant\UserController@update` |
| `DELETE` | `/configuracoes/usuarios/{user}` | `settings.users.destroy` | `Tenant\UserController@destroy` |
| `POST` | `/configuracoes/workspace/logo` | `settings.workspace.logo` | `Tenant\ProfileController@uploadWorkspaceLogo` |

## /conta

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/conta/em-analise` | `account.pending-approval` | `\Illuminate\Routing\ViewController` |
| `GET|HEAD` | `/conta/suspensa` | `account.suspended` | `\Illuminate\Routing\ViewController` |
| `GET|HEAD` | `/conta/trial-expirado` | `trial.expired` | `\Illuminate\Routing\ViewController` |

## /contatos

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/contatos` | `leads.index` | `Tenant\LeadController@index` |
| `POST` | `/contatos` | `leads.store` | `Tenant\LeadController@store` |
| `POST` | `/contatos/custom-fields/upload` | `leads.cf-upload` | `Tenant\LeadController@uploadCustomFieldFile` |
| `POST` | `/contatos/detectar-duplicatas` | `leads.detect-duplicates` | `Tenant\LeadMergeController@detect` |
| `GET|HEAD` | `/contatos/duplicatas` | `leads.duplicates` | `Tenant\LeadMergeController@duplicates` |
| `GET|HEAD` | `/contatos/duplicatas/data` | `leads.duplicates.data` | `Tenant\LeadMergeController@duplicatesData` |
| `POST` | `/contatos/duplicatas/{duplicate}/ignorar` | `leads.duplicates.ignore` | `Tenant\LeadMergeController@ignore` |
| `GET|HEAD` | `/contatos/exportar` | `leads.export` | `Tenant\LeadController@export` |
| `POST` | `/contatos/importar` | `leads.import` | `Tenant\LeadController@import` |
| `GET|HEAD` | `/contatos/{lead}` | `leads.show` | `Tenant\LeadController@show` |
| `PUT` | `/contatos/{lead}` | `leads.update` | `Tenant\LeadController@update` |
| `DELETE` | `/contatos/{lead}` | `leads.destroy` | `Tenant\LeadController@destroy` |
| `POST` | `/contatos/{lead}/anexos` | `leads.attachments.store` | `Tenant\LeadController@uploadAttachment` |
| `DELETE` | `/contatos/{lead}/anexos/{attachment}` | `leads.attachments.destroy` | `Tenant\LeadController@deleteAttachment` |
| `GET|HEAD` | `/contatos/{lead}/contacts` | `leads.contacts.index` | `Tenant\LeadController@leadContacts` |
| `POST` | `/contatos/{lead}/contacts` | `leads.contacts.store` | `Tenant\LeadController@storeContact` |
| `PUT` | `/contatos/{lead}/contacts/{contact}` | `leads.contacts.update` | `Tenant\LeadController@updateContact` |
| `DELETE` | `/contatos/{lead}/contacts/{contact}` | `leads.contacts.destroy` | `Tenant\LeadController@destroyContact` |
| `POST` | `/contatos/{lead}/mensagens-agendadas` | `leads.scheduled.store` | `Tenant\ScheduledMessageController@store` |
| `GET|HEAD` | `/contatos/{lead}/mensagens-agendadas` | `leads.scheduled.index` | `Tenant\ScheduledMessageController@index` |
| `DELETE` | `/contatos/{lead}/mensagens-agendadas/{scheduled}` | `leads.scheduled.destroy` | `Tenant\ScheduledMessageController@destroy` |
| `POST` | `/contatos/{lead}/notas` | `leads.notes.store` | `Tenant\LeadController@addNote` |
| `PUT` | `/contatos/{lead}/notas/{note}` | `leads.notes.update` | `Tenant\LeadController@updateNote` |
| `DELETE` | `/contatos/{lead}/notas/{note}` | `leads.notes.destroy` | `Tenant\LeadController@deleteNote` |
| `GET|HEAD` | `/contatos/{lead}/perfil` | `leads.profile` | `Tenant\LeadController@showPage` |
| `GET|HEAD` | `/contatos/{lead}/produtos` | `leads.products.index` | `Tenant\LeadController@getProducts` |
| `POST` | `/contatos/{lead}/produtos` | `leads.products.store` | `Tenant\LeadController@addProduct` |
| `PUT` | `/contatos/{lead}/produtos/{leadProduct}` | `leads.products.update` | `Tenant\LeadController@updateProduct` |
| `DELETE` | `/contatos/{lead}/produtos/{leadProduct}` | `leads.products.destroy` | `Tenant\LeadController@removeProduct` |
| `GET|HEAD` | `/contatos/{lead}/tarefas` | `leads.tasks.index` | `Tenant\TaskController@forLead` |
| `POST` | `/contatos/{primary}/merge/{secondary}` | `leads.merge` | `Tenant\LeadMergeController@merge` |
| `GET|HEAD` | `/contatos/{primary}/merge/{secondary}/preview` | `leads.merge.preview` | `Tenant\LeadMergeController@preview` |

## /crm

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/crm` | `crm.kanban` | `Tenant\KanbanController@index` |
| `GET|HEAD` | `/crm/exportar` | `crm.export` | `Tenant\KanbanController@export` |
| `POST` | `/crm/importar` | `crm.import` | `Tenant\KanbanController@import` |
| `POST` | `/crm/importar/preview` | `crm.import.preview` | `Tenant\KanbanController@preview` |
| `POST` | `/crm/lead/{lead}/stage` | `crm.lead.stage` | `Tenant\KanbanController@updateStage` |
| `GET|HEAD` | `/crm/poll` | `crm.poll` | `Tenant\KanbanController@poll` |
| `GET|HEAD` | `/crm/template` | `crm.template` | `Tenant\KanbanController@template` |

## /cs

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/cs` | `cs.index` | `Cs\CsDashboardController@index` |
| `GET|HEAD` | `/cs/{tenant}` | `cs.show` | `Cs\CsDashboardController@show` |

## /dashboard

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS` | `/dashboard` | `` | `\Illuminate\Routing\RedirectController` |
| `POST` | `/dashboard/config` | `dashboard.config` | `Tenant\DashboardController@saveConfig` |
| `GET|HEAD` | `/dashboard/leads-chart` | `dashboard.leads-chart` | `Tenant\DashboardController@leadsChart` |

## /forgot-password

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/forgot-password` | `password.request` | `Auth\AuthController@showForgotPassword` |
| `POST` | `/forgot-password` | `password.email` | `Auth\AuthController@sendResetLink` |

## /help-chat

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/help-chat` | `help.chat` | `Tenant\HelpChatController@chat` |
| `POST` | `/help-chat/execute` | `help.execute` | `Tenant\HelpChatController@execute` |

## /ia

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/ia/agentes` | `ai.agents.index` | `Tenant\AiAgentController@index` |
| `POST` | `/ia/agentes` | `ai.agents.store` | `Tenant\AiAgentController@store` |
| `GET|HEAD` | `/ia/agentes/criar` | `ai.agents.create` | `Tenant\AiAgentController@create` |
| `GET|HEAD` | `/ia/agentes/onboarding` | `ai.agents.onboarding` | `Tenant\AiAgentController@onboarding` |
| `GET|HEAD` | `/ia/agentes/voices` | `ai.agents.voices` | `Tenant\AiAgentController@voices` |
| `PUT` | `/ia/agentes/{agent}` | `ai.agents.update` | `Tenant\AiAgentController@update` |
| `DELETE` | `/ia/agentes/{agent}` | `ai.agents.destroy` | `Tenant\AiAgentController@destroy` |
| `GET|HEAD` | `/ia/agentes/{agent}/editar` | `ai.agents.edit` | `Tenant\AiAgentController@edit` |
| `POST` | `/ia/agentes/{agent}/knowledge-files` | `ai.agents.knowledge-files.store` | `Tenant\AiAgentController@uploadKnowledgeFile` |
| `DELETE` | `/ia/agentes/{agent}/knowledge-files/{file}` | `ai.agents.knowledge-files.destroy` | `Tenant\AiAgentController@deleteKnowledgeFile` |
| `POST` | `/ia/agentes/{agent}/media` | `ai.agents.media.store` | `Tenant\AiAgentController@uploadMedia` |
| `DELETE` | `/ia/agentes/{agent}/media/{media}` | `ai.agents.media.destroy` | `Tenant\AiAgentController@deleteMedia` |
| `POST` | `/ia/agentes/{agent}/test-chat` | `ai.agents.test-chat` | `Tenant\AiAgentController@testChat` |
| `POST` | `/ia/agentes/{agent}/toggle` | `ai.agents.toggle` | `Tenant\AiAgentController@toggleActive` |
| `GET|HEAD` | `/ia/sinais` | `ai.intent-signals.list` | `Tenant\AiIntentSignalController@list` |
| `POST` | `/ia/sinais/marcar-todas` | `ai.intent-signals.read-all` | `Tenant\AiIntentSignalController@markAllRead` |
| `GET|HEAD` | `/ia/sinais/nao-lidas/contagem` | `ai.intent-signals.unread-count` | `Tenant\AiIntentSignalController@unreadCount` |
| `POST` | `/ia/sinais/{signal}/lida` | `ai.intent-signals.read` | `Tenant\AiIntentSignalController@markRead` |

## /inicio

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/inicio` | `inicio` | `Tenant\DashboardController@index` |

## /kanban

| Método | URI | Nome | Action |
|---|---|---|---|
| `DELETE` | `/kanban/leads/{lead}` | `leads.kanban-remove` | `Tenant\LeadController@removeFromPipeline` |

## /listas

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/listas` | `lists.index` | `Tenant\LeadListController@index` |
| `POST` | `/listas` | `lists.store` | `Tenant\LeadListController@store` |
| `POST` | `/listas/preview` | `lists.preview` | `Tenant\LeadListController@preview` |
| `GET|HEAD` | `/listas/search-leads` | `lists.search-leads` | `Tenant\LeadListController@searchLeads` |
| `GET|HEAD` | `/listas/{list}` | `lists.show` | `Tenant\LeadListController@show` |
| `PUT` | `/listas/{list}` | `lists.update` | `Tenant\LeadListController@update` |
| `DELETE` | `/listas/{list}` | `lists.destroy` | `Tenant\LeadListController@destroy` |
| `POST` | `/listas/{list}/members` | `lists.members.add` | `Tenant\LeadListController@addMembers` |
| `DELETE` | `/listas/{list}/members/{lead}` | `lists.members.remove` | `Tenant\LeadListController@removeMember` |

## /livewire-709ba50a

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/livewire-709ba50a/livewire.csp.min.js.map` | `` | `Livewire\Mechanisms\FrontendAssets\FrontendAssets@cspMaps` |
| `GET|HEAD` | `/livewire-709ba50a/livewire.js` | `` | `Livewire\Mechanisms\FrontendAssets\FrontendAssets@returnJavaScriptAsFile` |
| `GET|HEAD` | `/livewire-709ba50a/livewire.min.js.map` | `` | `Livewire\Mechanisms\FrontendAssets\FrontendAssets@maps` |
| `GET|HEAD` | `/livewire-709ba50a/preview-file/{filename}` | `livewire.preview-file` | `Livewire\Features\SupportFileUploads\FilePreviewController@handle` |
| `POST` | `/livewire-709ba50a/update` | `default-livewire.update` | `Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate` |
| `POST` | `/livewire-709ba50a/upload-file` | `livewire.upload-file` | `Livewire\Features\SupportFileUploads\FileUploadController@handle` |

## /login

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/login` | `login` | `Auth\AuthController@showLogin` |
| `POST` | `/login` | `login.post` | `Auth\AuthController@login` |

## /logout

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/logout` | `logout` | `Auth\AuthController@logout` |

## /master

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/master` | `master.dashboard` | `Master\DashboardController@index` |
| `GET|HEAD` | `/master/2fa/backup-codes` | `master.2fa.backup-codes` | `Auth\TwoFactorController@showBackupCodes` |
| `POST` | `/master/2fa/backup-codes` | `master.2fa.regenerate-codes` | `Auth\TwoFactorController@regenerateBackupCodes` |
| `POST` | `/master/2fa/disable` | `master.2fa.disable` | `Auth\TwoFactorController@disable` |
| `GET|HEAD` | `/master/2fa/setup` | `master.2fa.setup` | `Auth\TwoFactorController@showSetup` |
| `POST` | `/master/2fa/setup` | `master.2fa.confirm` | `Auth\TwoFactorController@confirmSetup` |
| `GET|HEAD` | `/master/administradores` | `master.admins` | `Master\MasterAdminController@index` |
| `POST` | `/master/administradores` | `master.admins.store` | `Master\MasterAdminController@store` |
| `PUT` | `/master/administradores/{user}` | `master.admins.update` | `Master\MasterAdminController@update` |
| `DELETE` | `/master/administradores/{user}` | `master.admins.destroy` | `Master\MasterAdminController@destroy` |
| `GET|HEAD` | `/master/codigos-agencia` | `master.agency-codes.index` | `Master\PartnerAgencyCodeController@index` |
| `POST` | `/master/codigos-agencia` | `master.agency-codes.store` | `Master\PartnerAgencyCodeController@store` |
| `POST` | `/master/codigos-agencia/gerar` | `master.agency-codes.generate` | `Master\PartnerAgencyCodeController@generate` |
| `PUT` | `/master/codigos-agencia/{partnerAgencyCode}` | `master.agency-codes.update` | `Master\PartnerAgencyCodeController@update` |
| `DELETE` | `/master/codigos-agencia/{partnerAgencyCode}` | `master.agency-codes.destroy` | `Master\PartnerAgencyCodeController@destroy` |
| `GET|HEAD` | `/master/cs-agentes` | `master.cs-agents` | `Master\CsAgentController@index` |
| `POST` | `/master/cs-agentes` | `master.cs-agents.store` | `Master\CsAgentController@store` |
| `PUT` | `/master/cs-agentes/{user}` | `master.cs-agents.update` | `Master\CsAgentController@update` |
| `DELETE` | `/master/cs-agentes/{user}` | `master.cs-agents.destroy` | `Master\CsAgentController@destroy` |
| `GET|HEAD` | `/master/empresas` | `master.tenants` | `Master\TenantController@index` |
| `POST` | `/master/empresas` | `master.tenants.store` | `Master\TenantController@store` |
| `GET|HEAD` | `/master/empresas/{tenant}` | `master.tenants.show` | `Master\TenantController@show` |
| `PUT` | `/master/empresas/{tenant}` | `master.tenants.update` | `Master\TenantController@update` |
| `DELETE` | `/master/empresas/{tenant}` | `master.tenants.destroy` | `Master\TenantController@destroy` |
| `POST` | `/master/empresas/{tenant}/approve-partner` | `master.tenants.approve-partner` | `Master\TenantController@approvePartner` |
| `POST` | `/master/empresas/{tenant}/reject-partner` | `master.tenants.reject-partner` | `Master\TenantController@rejectPartner` |
| `POST` | `/master/empresas/{tenant}/usuarios` | `master.tenants.users.store` | `Master\UserController@store` |
| `PUT` | `/master/empresas/{tenant}/usuarios/{user}` | `master.tenants.users.update` | `Master\UserController@update` |
| `DELETE` | `/master/empresas/{tenant}/usuarios/{user}` | `master.tenants.users.destroy` | `Master\UserController@destroy` |
| `GET|HEAD` | `/master/features` | `master.features` | `Master\FeatureController@index` |
| `PUT` | `/master/features/{feature}/tenants` | `master.features.update-tenants` | `Master\FeatureController@updateTenants` |
| `POST` | `/master/features/{feature}/toggle-global` | `master.features.toggle-global` | `Master\FeatureController@toggleGlobal` |
| `GET|HEAD` | `/master/feedbacks` | `master.feedbacks.index` | `Master\FeedbackController@index` |
| `GET|HEAD` | `/master/feedbacks/{feedback}` | `master.feedbacks.show` | `Master\FeedbackController@show` |
| `PUT` | `/master/feedbacks/{feedback}/status` | `master.feedbacks.status` | `Master\FeedbackController@updateStatus` |
| `GET|HEAD` | `/master/ferramentas` | `master.toolbox` | `Master\ToolboxController@index` |
| `POST` | `/master/ferramentas/{tool}` | `master.toolbox.run` | `Master\ToolboxController@run` |
| `GET|HEAD` | `/master/logs` | `master.logs` | `Master\LogController@index` |
| `GET|HEAD` | `/master/logs/content` | `master.logs.content` | `Master\LogController@content` |
| `GET|HEAD` | `/master/notificacoes` | `master.notifications` | `Master\NotificationController@index` |
| `POST` | `/master/notificacoes` | `master.notifications.store` | `Master\NotificationController@store` |
| `PUT` | `/master/partner-aulas/{lesson}` | `master.partner-lessons.update` | `Master\PartnerCourseController@updateLesson` |
| `DELETE` | `/master/partner-aulas/{lesson}` | `master.partner-lessons.destroy` | `Master\PartnerCourseController@destroyLesson` |
| `GET|HEAD` | `/master/partner-comissoes` | `master.partner-commissions.index` | `Master\PartnerCommissionController@index` |
| `GET|HEAD` | `/master/partner-cursos` | `master.partner-courses.index` | `Master\PartnerCourseController@index` |
| `POST` | `/master/partner-cursos` | `master.partner-courses.store` | `Master\PartnerCourseController@store` |
| `PUT` | `/master/partner-cursos/{course}` | `master.partner-courses.update` | `Master\PartnerCourseController@update` |
| `DELETE` | `/master/partner-cursos/{course}` | `master.partner-courses.destroy` | `Master\PartnerCourseController@destroy` |
| `POST` | `/master/partner-cursos/{course}/aulas` | `master.partner-lessons.store` | `Master\PartnerCourseController@storeLesson` |
| `GET|HEAD` | `/master/partner-ranks` | `master.partner-ranks.index` | `Master\PartnerRankController@index` |
| `POST` | `/master/partner-ranks` | `master.partner-ranks.store` | `Master\PartnerRankController@store` |
| `PUT` | `/master/partner-ranks/{rank}` | `master.partner-ranks.update` | `Master\PartnerRankController@update` |
| `DELETE` | `/master/partner-ranks/{rank}` | `master.partner-ranks.destroy` | `Master\PartnerRankController@destroy` |
| `GET|HEAD` | `/master/partner-recursos` | `master.partner-resources.index` | `Master\PartnerResourceController@index` |
| `POST` | `/master/partner-recursos` | `master.partner-resources.store` | `Master\PartnerResourceController@store` |
| `PUT` | `/master/partner-recursos/{resource}` | `master.partner-resources.update` | `Master\PartnerResourceController@update` |
| `DELETE` | `/master/partner-recursos/{resource}` | `master.partner-resources.destroy` | `Master\PartnerResourceController@destroy` |
| `POST` | `/master/partner-saques/{withdrawal}/aprovar` | `master.partner-withdrawals.approve` | `Master\PartnerCommissionController@approveWithdrawal` |
| `POST` | `/master/partner-saques/{withdrawal}/pago` | `master.partner-withdrawals.paid` | `Master\PartnerCommissionController@markPaid` |
| `POST` | `/master/partner-saques/{withdrawal}/rejeitar` | `master.partner-withdrawals.reject` | `Master\PartnerCommissionController@rejectWithdrawal` |
| `GET|HEAD` | `/master/planos` | `master.plans` | `Master\PlanController@index` |
| `POST` | `/master/planos` | `master.plans.store` | `Master\PlanController@store` |
| `PUT` | `/master/planos/{plan}` | `master.plans.update` | `Master\PlanController@update` |
| `DELETE` | `/master/planos/{plan}` | `master.plans.destroy` | `Master\PlanController@destroy` |
| `GET|HEAD` | `/master/recebimentos` | `master.payments` | `Master\PaymentController@index` |
| `GET|HEAD` | `/master/reengajamento` | `master.reengagement` | `Master\ReengagementController@index` |
| `PUT` | `/master/reengajamento` | `master.reengagement.update` | `Master\ReengagementController@update` |
| `GET|HEAD` | `/master/reengajamento/preview` | `master.reengagement.preview` | `Master\ReengagementController@preview` |
| `POST` | `/master/reengajamento/teste` | `master.reengagement.test` | `Master\ReengagementController@sendTest` |
| `GET|HEAD` | `/master/sistema` | `master.system` | `Master\SystemController@index` |
| `GET|HEAD` | `/master/sistema/stats` | `master.system.stats` | `Master\SystemController@stats` |
| `GET|HEAD` | `/master/token-incrementos` | `master.token-increments` | `Master\TokenIncrementPlanController@index` |
| `POST` | `/master/token-incrementos` | `master.token-increments.store` | `Master\TokenIncrementPlanController@store` |
| `PUT` | `/master/token-incrementos/{tokenIncrementPlan}` | `master.token-increments.update` | `Master\TokenIncrementPlanController@update` |
| `DELETE` | `/master/token-incrementos/{tokenIncrementPlan}` | `master.token-increments.destroy` | `Master\TokenIncrementPlanController@destroy` |
| `GET|HEAD` | `/master/upsell` | `master.upsell` | `Master\UpsellTriggerController@index` |
| `POST` | `/master/upsell` | `master.upsell.store` | `Master\UpsellTriggerController@store` |
| `PUT` | `/master/upsell/{trigger}` | `master.upsell.update` | `Master\UpsellTriggerController@update` |
| `DELETE` | `/master/upsell/{trigger}` | `master.upsell.destroy` | `Master\UpsellTriggerController@destroy` |
| `GET|HEAD` | `/master/upsell/{trigger}/logs` | `master.upsell.logs` | `Master\UpsellTriggerController@logs` |
| `GET|HEAD` | `/master/uso` | `master.usage` | `Master\UsageController@index` |
| `GET|HEAD` | `/master/uso/{tenant}` | `master.usage.show` | `Master\UsageController@show` |

## /metas

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/metas` | `goals.index` | `Tenant\SalesGoalController@index` |
| `POST` | `/metas` | `goals.store` | `Tenant\SalesGoalController@store` |
| `GET|HEAD` | `/metas/historico/{user?}` | `goals.history` | `Tenant\SalesGoalController@history` |
| `PUT` | `/metas/{goal}` | `goals.update` | `Tenant\SalesGoalController@update` |
| `DELETE` | `/metas/{goal}` | `goals.destroy` | `Tenant\SalesGoalController@destroy` |

## /notificacoes

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/notificacoes` | `notifications.index` | `Tenant\NotificationController@index` |
| `POST` | `/notificacoes/marcar-todas` | `notifications.read-all` | `Tenant\NotificationController@markAllRead` |
| `GET|HEAD` | `/notificacoes/master` | `master-notifications.index` | `Tenant\MasterNotificationReadController@index` |
| `GET|HEAD` | `/notificacoes/nao-lidas` | `notifications.unread-count` | `Tenant\NotificationController@unreadCount` |
| `GET|HEAD` | `/notificacoes/recentes` | `notifications.recent` | `Tenant\NotificationController@recent` |
| `POST` | `/notificacoes/{id}/lida` | `notifications.read` | `Tenant\NotificationController@markRead` |

## /nps

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/nps` | `nps.index` | `Tenant\NpsSurveyController@index` |
| `POST` | `/nps` | `nps.store` | `Tenant\NpsSurveyController@store` |
| `PUT` | `/nps/{survey}` | `nps.update` | `Tenant\NpsSurveyController@update` |
| `DELETE` | `/nps/{survey}` | `nps.destroy` | `Tenant\NpsSurveyController@destroy` |
| `POST` | `/nps/{survey}/send` | `nps.send` | `Tenant\NpsSurveyController@sendBulk` |

## /onboarding

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/onboarding` | `onboarding.show` | `Tenant\OnboardingController@show` |
| `POST` | `/onboarding/generate` | `onboarding.generate` | `Tenant\OnboardingController@generate` |
| `GET|HEAD` | `/onboarding/loading` | `onboarding.loading` | `Tenant\OnboardingController@loading` |
| `GET|HEAD` | `/onboarding/progress` | `onboarding.progress` | `Tenant\OnboardingController@progress` |
| `GET|HEAD` | `/onboarding/result` | `onboarding.result` | `Tenant\OnboardingController@result` |
| `POST` | `/onboarding/retry` | `onboarding.retry` | `Tenant\OnboardingController@retry` |
| `POST` | `/onboarding/skip` | `onboarding.skip` | `Tenant\OnboardingController@skip` |

## /parceiro

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/parceiro` | `partner.dashboard` | `Partner\PartnerDashboardController@index` |
| `POST` | `/parceiro/aulas/{lesson}/concluir` | `partner.lessons.complete` | `Partner\PartnerCourseController@completeLesson` |
| `GET|HEAD` | `/parceiro/cursos` | `partner.courses.index` | `Partner\PartnerCourseController@index` |
| `POST` | `/parceiro/cursos/{course}/certificado` | `partner.courses.certificate` | `Partner\PartnerCourseController@issueCertificate` |
| `GET|HEAD` | `/parceiro/cursos/{slug}` | `partner.courses.show` | `Partner\PartnerCourseController@show` |
| `GET|HEAD` | `/parceiro/recursos` | `partner.resources.index` | `Partner\PartnerResourceController@index` |
| `GET|HEAD` | `/parceiro/recursos/{slug}` | `partner.resources.show` | `Partner\PartnerResourceController@show` |
| `POST` | `/parceiro/saque` | `partner.withdrawal.store` | `Partner\PartnerWithdrawalController@store` |

## /parceiros

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/parceiros` | `agency.register` | `Auth\AgencyRegisterController@show` |
| `POST` | `/parceiros` | `agency.register.store` | `Auth\AgencyRegisterController@store` |

## /pesquisa

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/pesquisa/{slug}` | `survey.slug` | `SurveyPublicController@showBySlug` |

## /politica-de-privacidade

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/politica-de-privacidade` | `privacy` | `\Illuminate\Routing\ViewController` |

## /push-subscriptions

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/push-subscriptions` | `push.store` | `Tenant\PushSubscriptionController@store` |
| `DELETE` | `/push-subscriptions` | `push.destroy` | `Tenant\PushSubscriptionController@destroy` |

## /register

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/register` | `register` | `Auth\AuthController@showRegister` |
| `POST` | `/register` | `register.post` | `Auth\AuthController@register` |

## /relatorios

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/relatorios` | `reports.index` | `Tenant\ReportController@index` |
| `GET|HEAD` | `/relatorios/pdf` | `reports.pdf` | `Tenant\ReportController@exportPdf` |

## /resend

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/resend/webhook` | `resend.webhook` | `Resend\Laravel\Http\Controllers\WebhookController@handleWebhook` |

## /reset-password

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/reset-password` | `password.update` | `Auth\AuthController@resetPassword` |
| `GET|HEAD` | `/reset-password/{token}` | `password.reset` | `Auth\AuthController@showResetPassword` |

## /s

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/s/{uuid}` | `survey.show` | `SurveyPublicController@showByUuid` |
| `POST` | `/s/{uuid}` | `survey.answer` | `SurveyPublicController@answer` |

## /sanctum

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` |

## /sugestoes

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/sugestoes` | `feedback.create` | `Tenant\FeedbackController@create` |
| `POST` | `/sugestoes` | `feedback.store` | `Tenant\FeedbackController@store` |

## /tarefas

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/tarefas` | `tasks.index` | `Tenant\TaskController@index` |
| `POST` | `/tarefas` | `tasks.store` | `Tenant\TaskController@store` |
| `GET|HEAD` | `/tarefas/buscar-leads` | `tasks.search-leads` | `Tenant\TaskController@searchLeads` |
| `GET|HEAD` | `/tarefas/data` | `tasks.data` | `Tenant\TaskController@data` |
| `PUT` | `/tarefas/{task}` | `tasks.update` | `Tenant\TaskController@update` |
| `DELETE` | `/tarefas/{task}` | `tasks.destroy` | `Tenant\TaskController@destroy` |
| `PATCH` | `/tarefas/{task}/toggle` | `tasks.toggle` | `Tenant\TaskController@toggleStatus` |

## /termos-de-uso

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/termos-de-uso` | `terms` | `\Illuminate\Routing\ViewController` |

## /tour

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/tour/complete` | `tour.complete` | `Tenant\TourController@complete` |
| `POST` | `/tour/reset` | `tour.reset` | `Tenant\TourController@reset` |

## /upsell

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/upsell/{log}/click` | `upsell.click` | `Tenant\UpsellBannerController@click` |
| `POST` | `/upsell/{log}/dismiss` | `upsell.dismiss` | `Tenant\UpsellBannerController@dismiss` |

## /verify-email

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/verify-email/{token}` | `verify.email` | `Auth\AuthController@verifyEmail` |

## /wa

| Método | URI | Nome | Action |
|---|---|---|---|
| `GET|HEAD` | `/wa/{token}` | `wa.redirect` | `Api\WebsiteWidgetController@waRedirect` |

## /webhook

| Método | URI | Nome | Action |
|---|---|---|---|
| `POST` | `/webhook/whatsapp` | `whatsapp.webhook` | `WhatsappWebhookController@handle` |

