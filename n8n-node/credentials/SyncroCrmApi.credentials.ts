import { ICredentialType, INodeProperties } from 'n8n-workflow';

export class SyncroCrmApi implements ICredentialType {
	name = 'syncroCrmApi';
	displayName = 'Syncro CRM API';
	documentationUrl = 'https://app.syncro.chat';

	properties: INodeProperties[] = [
		{
			displayName: 'Base URL',
			name: 'baseUrl',
			type: 'string',
			default: 'https://app.syncro.chat',
			required: true,
			description: 'URL base do seu CRM (ex: https://app.syncro.chat)',
		},
		{
			displayName: 'API Key',
			name: 'apiKey',
			type: 'string',
			typeOptions: { password: true },
			default: '',
			required: true,
			description: 'Gere sua API Key em Configurações > API Keys no CRM',
		},
	];

	authenticate = {
		type: 'generic' as const,
		properties: {
			headers: {
				'X-API-Key': '={{$credentials.apiKey}}',
			},
		},
	};
}
