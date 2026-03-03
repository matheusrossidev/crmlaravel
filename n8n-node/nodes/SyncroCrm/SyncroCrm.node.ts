import {
	IExecuteFunctions,
	ILoadOptionsFunctions,
	INodeExecutionData,
	INodePropertyOptions,
	INodeType,
	INodeTypeDescription,
	NodeOperationError,
} from 'n8n-workflow';

export class SyncroCrm implements INodeType {
	description: INodeTypeDescription = {
		displayName: 'Syncro CRM',
		name: 'syncroCrm',
		icon: 'file:syncro.svg',
		group: ['transform'],
		version: 1,
		subtitle: '={{$parameter["operation"] + ": " + $parameter["resource"]}}',
		description: 'Crie leads, atualize etapas e gerencie campanhas no Syncro CRM',
		defaults: { name: 'Syncro CRM' },
		inputs: ['main'] as any,
		outputs: ['main'] as any,
		credentials: [
			{
				name: 'syncroCrmApi',
				required: true,
			},
		],
		properties: [
			// ── Recurso ─────────────────────────────────────────────────────────
			{
				displayName: 'Recurso',
				name: 'resource',
				type: 'options',
				noDataExpression: true,
				options: [
					{ name: 'Lead', value: 'lead' },
					{ name: 'Campanha', value: 'campaign' },
					{ name: 'Pipeline', value: 'pipeline' },
				],
				default: 'lead',
			},

			// ── Operação — Lead ─────────────────────────────────────────────────
			{
				displayName: 'Operação',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: { show: { resource: ['lead'] } },
				options: [
					{
						name: 'Criar',
						value: 'create',
						description: 'Cria um novo lead',
						action: 'Criar lead',
					},
					{
						name: 'Buscar',
						value: 'get',
						description: 'Busca os dados de um lead pelo ID',
						action: 'Buscar lead',
					},
					{
						name: 'Atualizar Etapa',
						value: 'updateStage',
						description: 'Move o lead para outra etapa do funil',
						action: 'Atualizar etapa do lead',
					},
					{
						name: 'Marcar como Ganho',
						value: 'won',
						description: 'Marca o lead como venda ganha',
						action: 'Marcar lead como ganho',
					},
					{
						name: 'Marcar como Perdido',
						value: 'lost',
						description: 'Marca o lead como venda perdida',
						action: 'Marcar lead como perdido',
					},
					{
						name: 'Deletar',
						value: 'delete',
						description: 'Remove o lead permanentemente',
						action: 'Deletar lead',
					},
				],
				default: 'create',
			},

			// ── Operação — Campanha ─────────────────────────────────────────────
			{
				displayName: 'Operação',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: { show: { resource: ['campaign'] } },
				options: [
					{
						name: 'Listar',
						value: 'list',
						description: 'Lista todas as campanhas',
						action: 'Listar campanhas',
					},
					{
						name: 'Criar',
						value: 'create',
						description: 'Cria uma nova campanha',
						action: 'Criar campanha',
					},
					{
						name: 'Atualizar',
						value: 'update',
						description: 'Atualiza os dados de uma campanha',
						action: 'Atualizar campanha',
					},
					{
						name: 'Deletar',
						value: 'delete',
						description: 'Remove uma campanha',
						action: 'Deletar campanha',
					},
				],
				default: 'list',
			},

			// ── Operação — Pipeline ─────────────────────────────────────────────
			{
				displayName: 'Operação',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: { show: { resource: ['pipeline'] } },
				options: [
					{
						name: 'Listar',
						value: 'list',
						description: 'Lista todos os funis com etapas e motivos de perda',
						action: 'Listar pipelines',
					},
				],
				default: 'list',
			},

			// ── Campos — Lead ID (operações que precisam do ID) ─────────────────
			{
				displayName: 'Lead ID',
				name: 'leadId',
				type: 'string',
				required: true,
				displayOptions: {
					show: {
						resource: ['lead'],
						operation: ['get', 'updateStage', 'won', 'lost', 'delete'],
					},
				},
				default: '',
				description: 'ID do lead no Syncro CRM',
			},

			// ── Campos — Lead > Criar ───────────────────────────────────────────
			{
				displayName: 'Nome',
				name: 'name',
				type: 'string',
				required: true,
				displayOptions: { show: { resource: ['lead'], operation: ['create'] } },
				default: '',
				description: 'Nome completo do lead',
			},
			{
				displayName: 'Pipeline',
				name: 'pipelineId',
				type: 'options',
				required: true,
				typeOptions: { loadOptionsMethod: 'getPipelines' },
				displayOptions: {
					show: { resource: ['lead'], operation: ['create', 'updateStage'] },
				},
				default: '',
				description: 'Funil de vendas onde o lead será criado',
			},
			{
				displayName: 'Etapa',
				name: 'stageId',
				type: 'options',
				required: true,
				typeOptions: { loadOptionsMethod: 'getStages' },
				displayOptions: {
					show: { resource: ['lead'], operation: ['create', 'updateStage'] },
				},
				default: '',
				description: 'Etapa do funil onde o lead será colocado',
			},

			// ── Campos — Lead > Ganho ───────────────────────────────────────────
			{
				displayName: 'Etapa de Ganho',
				name: 'wonStageId',
				type: 'options',
				required: true,
				typeOptions: { loadOptionsMethod: 'getWonStages' },
				displayOptions: { show: { resource: ['lead'], operation: ['won'] } },
				default: '',
				description: 'Etapa marcada como "Ganho" no funil',
			},
			{
				displayName: 'Valor da Venda (R$)',
				name: 'wonValue',
				type: 'number',
				displayOptions: { show: { resource: ['lead'], operation: ['won'] } },
				default: 0,
				description: 'Valor da venda (opcional, sobrescreve o valor do lead)',
			},

			// ── Campos — Lead > Perdido ─────────────────────────────────────────
			{
				displayName: 'Etapa de Perda',
				name: 'lostStageId',
				type: 'options',
				required: true,
				typeOptions: { loadOptionsMethod: 'getLostStages' },
				displayOptions: { show: { resource: ['lead'], operation: ['lost'] } },
				default: '',
				description: 'Etapa marcada como "Perdido" no funil',
			},
			{
				displayName: 'Motivo de Perda',
				name: 'lostReasonId',
				type: 'options',
				typeOptions: { loadOptionsMethod: 'getLostReasons' },
				displayOptions: { show: { resource: ['lead'], operation: ['lost'] } },
				default: '',
				description: 'Motivo pelo qual o lead foi perdido (opcional)',
			},

			// ── Campos adicionais — Lead > Criar ────────────────────────────────
			{
				displayName: 'Campos Adicionais',
				name: 'additionalFields',
				type: 'collection',
				placeholder: 'Adicionar campo',
				displayOptions: { show: { resource: ['lead'], operation: ['create'] } },
				default: {},
				options: [
					{
						displayName: 'Telefone',
						name: 'phone',
						type: 'string',
						default: '',
					},
					{
						displayName: 'E-mail',
						name: 'email',
						type: 'string',
						default: '',
					},
					{
						displayName: 'Valor (R$)',
						name: 'value',
						type: 'number',
						default: 0,
					},
					{
						displayName: 'Origem',
						name: 'source',
						type: 'string',
						default: '',
						description: 'Ex: site, indicação, instagram',
					},
					{
						displayName: 'Notas',
						name: 'notes',
						type: 'string',
						typeOptions: { rows: 3 },
						default: '',
					},
					{
						displayName: 'Campanha',
						name: 'campaignId',
						type: 'options',
						typeOptions: { loadOptionsMethod: 'getCampaigns' },
						default: '',
					},
					{
						displayName: 'UTM Source',
						name: 'utmSource',
						type: 'string',
						default: '',
						description: 'Ex: facebook, google',
					},
					{
						displayName: 'UTM Medium',
						name: 'utmMedium',
						type: 'string',
						default: '',
						description: 'Ex: cpc, email',
					},
					{
						displayName: 'UTM Campaign',
						name: 'utmCampaign',
						type: 'string',
						default: '',
						description: 'Nome da campanha UTM',
					},
					{
						displayName: 'UTM Term',
						name: 'utmTerm',
						type: 'string',
						default: '',
					},
					{
						displayName: 'UTM Content',
						name: 'utmContent',
						type: 'string',
						default: '',
					},
					{
						displayName: 'Campos Personalizados (JSON)',
						name: 'customFieldsJson',
						type: 'string',
						typeOptions: { rows: 4 },
						default: '{}',
						description: 'Objeto JSON com campos personalizados. Ex: {"idade":25,"interesse":"produto_x"}',
					},
				],
			},

			// ── Campos — Campanha ID ────────────────────────────────────────────
			{
				displayName: 'Campaign ID',
				name: 'campaignId',
				type: 'string',
				required: true,
				displayOptions: {
					show: { resource: ['campaign'], operation: ['update', 'delete'] },
				},
				default: '',
				description: 'ID da campanha no Syncro CRM',
			},

			// ── Campos — Campanha > Criar ───────────────────────────────────────
			{
				displayName: 'Nome da Campanha',
				name: 'campaignName',
				type: 'string',
				required: true,
				displayOptions: { show: { resource: ['campaign'], operation: ['create'] } },
				default: '',
			},
			{
				displayName: 'Campos da Campanha',
				name: 'campaignFields',
				type: 'collection',
				placeholder: 'Adicionar campo',
				displayOptions: {
					show: { resource: ['campaign'], operation: ['create', 'update'] },
				},
				default: {},
				options: [
					{
						displayName: 'Status',
						name: 'status',
						type: 'options',
						options: [
							{ name: 'Ativa', value: 'active' },
							{ name: 'Pausada', value: 'paused' },
							{ name: 'Arquivada', value: 'archived' },
						],
						default: 'active',
					},
					{
						displayName: 'Tipo',
						name: 'type',
						type: 'options',
						options: [
							{ name: 'Manual', value: 'manual' },
							{ name: 'Facebook Ads', value: 'facebook' },
							{ name: 'Google Ads', value: 'google' },
						],
						default: 'manual',
					},
					{
						displayName: 'UTM Source',
						name: 'utmSource',
						type: 'string',
						default: '',
					},
					{
						displayName: 'UTM Medium',
						name: 'utmMedium',
						type: 'string',
						default: '',
					},
					{
						displayName: 'UTM Campaign',
						name: 'utmCampaign',
						type: 'string',
						default: '',
					},
					{
						displayName: 'URL de Destino',
						name: 'destinationUrl',
						type: 'string',
						default: '',
						description: 'URL da landing page onde o link UTM será utilizado',
					},
					{
						displayName: 'Orçamento Diário (R$)',
						name: 'budgetDaily',
						type: 'number',
						default: 0,
					},
					{
						displayName: 'Objetivo',
						name: 'objective',
						type: 'string',
						default: '',
						description: 'Ex: leads, sales, awareness',
					},
				],
			},
		],
	};

	methods = {
		loadOptions: {
			async getPipelines(this: ILoadOptionsFunctions): Promise<INodePropertyOptions[]> {
				const creds = await this.getCredentials('syncroCrmApi');
				const res = (await this.helpers.request({
					method: 'GET',
					url: `${creds.baseUrl}/api/v1/pipelines`,
					headers: { 'X-API-Key': creds.apiKey, 'Accept': 'application/json' },
					json: true,
				})) as any;
				return (res.pipelines || []).map((p: any) => ({
					name: p.name as string,
					value: String(p.id),
				}));
			},

			async getStages(this: ILoadOptionsFunctions): Promise<INodePropertyOptions[]> {
				const creds = await this.getCredentials('syncroCrmApi');
				const res = (await this.helpers.request({
					method: 'GET',
					url: `${creds.baseUrl}/api/v1/pipelines`,
					headers: { 'X-API-Key': creds.apiKey, 'Accept': 'application/json' },
					json: true,
				})) as any;
				const stages: INodePropertyOptions[] = [];
				for (const p of res.pipelines || []) {
					for (const s of p.stages || []) {
						stages.push({
							name: `${p.name} → ${s.name}`,
							value: String(s.id),
						});
					}
				}
				return stages;
			},

			async getWonStages(this: ILoadOptionsFunctions): Promise<INodePropertyOptions[]> {
				const creds = await this.getCredentials('syncroCrmApi');
				const res = (await this.helpers.request({
					method: 'GET',
					url: `${creds.baseUrl}/api/v1/pipelines`,
					headers: { 'X-API-Key': creds.apiKey, 'Accept': 'application/json' },
					json: true,
				})) as any;
				const stages: INodePropertyOptions[] = [];
				for (const p of res.pipelines || []) {
					for (const s of p.stages || []) {
						if (s.is_won) {
							stages.push({ name: `${p.name} → ${s.name}`, value: String(s.id) });
						}
					}
				}
				return stages;
			},

			async getLostStages(this: ILoadOptionsFunctions): Promise<INodePropertyOptions[]> {
				const creds = await this.getCredentials('syncroCrmApi');
				const res = (await this.helpers.request({
					method: 'GET',
					url: `${creds.baseUrl}/api/v1/pipelines`,
					headers: { 'X-API-Key': creds.apiKey, 'Accept': 'application/json' },
					json: true,
				})) as any;
				const stages: INodePropertyOptions[] = [];
				for (const p of res.pipelines || []) {
					for (const s of p.stages || []) {
						if (s.is_lost) {
							stages.push({ name: `${p.name} → ${s.name}`, value: String(s.id) });
						}
					}
				}
				return stages;
			},

			async getLostReasons(this: ILoadOptionsFunctions): Promise<INodePropertyOptions[]> {
				const creds = await this.getCredentials('syncroCrmApi');
				const res = (await this.helpers.request({
					method: 'GET',
					url: `${creds.baseUrl}/api/v1/pipelines`,
					headers: { 'X-API-Key': creds.apiKey, 'Accept': 'application/json' },
					json: true,
				})) as any;
				const reasons: INodePropertyOptions[] = [{ name: '(nenhum)', value: '' }];
				for (const r of res.lost_reasons || []) {
					reasons.push({ name: r.name as string, value: String(r.id) });
				}
				return reasons;
			},

			async getCampaigns(this: ILoadOptionsFunctions): Promise<INodePropertyOptions[]> {
				const creds = await this.getCredentials('syncroCrmApi');
				const res = (await this.helpers.request({
					method: 'GET',
					url: `${creds.baseUrl}/api/v1/campaigns`,
					headers: { 'X-API-Key': creds.apiKey, 'Accept': 'application/json' },
					json: true,
				})) as any;
				const list: INodePropertyOptions[] = [{ name: '(nenhuma)', value: '' }];
				for (const c of res.campaigns || []) {
					list.push({ name: c.name as string, value: String(c.id) });
				}
				return list;
			},
		},
	};

	async execute(this: IExecuteFunctions): Promise<INodeExecutionData[][]> {
		const items = this.getInputData();
		const returnData: INodeExecutionData[] = [];
		const creds = await this.getCredentials('syncroCrmApi');
		const base = (creds.baseUrl as string).replace(/\/$/, '');
		const headers: Record<string, string> = {
			'X-API-Key': creds.apiKey as string,
			'Accept': 'application/json',
			'Content-Type': 'application/json',
		};

		for (let i = 0; i < items.length; i++) {
			const resource = this.getNodeParameter('resource', i) as string;
			const operation = this.getNodeParameter('operation', i) as string;

			let method = 'GET';
			let url = '';
			let body: Record<string, unknown> | undefined;

			// ── LEAD ────────────────────────────────────────────────────────────
			if (resource === 'lead') {
				if (operation === 'create') {
					const af = this.getNodeParameter('additionalFields', i, {}) as Record<string, any>;
					body = {
						name: this.getNodeParameter('name', i) as string,
						pipeline_id: this.getNodeParameter('pipelineId', i),
						stage_id: this.getNodeParameter('stageId', i),
					};
					if (af.phone) body.phone = af.phone;
					if (af.email) body.email = af.email;
					if (af.value) body.value = af.value;
					if (af.source) body.source = af.source;
					if (af.notes) body.notes = af.notes;
					if (af.campaignId) body.campaign_id = af.campaignId;
					if (af.utmSource) body.utm_source = af.utmSource;
					if (af.utmMedium) body.utm_medium = af.utmMedium;
					if (af.utmCampaign) body.utm_campaign = af.utmCampaign;
					if (af.utmTerm) body.utm_term = af.utmTerm;
					if (af.utmContent) body.utm_content = af.utmContent;
					if (af.customFieldsJson && af.customFieldsJson !== '{}') {
						try {
							body.custom_fields = JSON.parse(af.customFieldsJson as string);
						} catch {
							throw new NodeOperationError(
								this.getNode(),
								'Campos Personalizados (JSON) inválido. Verifique a sintaxe do JSON.',
								{ itemIndex: i },
							);
						}
					}
					method = 'POST';
					url = `${base}/api/v1/leads`;
				} else if (operation === 'get') {
					method = 'GET';
					url = `${base}/api/v1/leads/${this.getNodeParameter('leadId', i)}`;
				} else if (operation === 'updateStage') {
					method = 'PUT';
					url = `${base}/api/v1/leads/${this.getNodeParameter('leadId', i)}/stage`;
					body = {
						pipeline_id: this.getNodeParameter('pipelineId', i),
						stage_id: this.getNodeParameter('stageId', i),
					};
				} else if (operation === 'won') {
					method = 'PUT';
					url = `${base}/api/v1/leads/${this.getNodeParameter('leadId', i)}/won`;
					const wonValue = this.getNodeParameter('wonValue', i, 0) as number;
					body = { stage_id: this.getNodeParameter('wonStageId', i) };
					if (wonValue > 0) body.value = wonValue;
				} else if (operation === 'lost') {
					method = 'PUT';
					url = `${base}/api/v1/leads/${this.getNodeParameter('leadId', i)}/lost`;
					const reasonId = this.getNodeParameter('lostReasonId', i, '') as string;
					body = { stage_id: this.getNodeParameter('lostStageId', i) };
					if (reasonId) body.reason_id = reasonId;
				} else if (operation === 'delete') {
					method = 'DELETE';
					url = `${base}/api/v1/leads/${this.getNodeParameter('leadId', i)}`;
				}
			}

			// ── CAMPAIGN ────────────────────────────────────────────────────────
			else if (resource === 'campaign') {
				if (operation === 'list') {
					method = 'GET';
					url = `${base}/api/v1/campaigns`;
				} else if (operation === 'create') {
					const cf = this.getNodeParameter('campaignFields', i, {}) as Record<string, any>;
					body = { name: this.getNodeParameter('campaignName', i) as string };
					if (cf.status) body.status = cf.status;
					if (cf.type) body.type = cf.type;
					if (cf.utmSource) body.utm_source = cf.utmSource;
					if (cf.utmMedium) body.utm_medium = cf.utmMedium;
					if (cf.utmCampaign) body.utm_campaign = cf.utmCampaign;
					if (cf.destinationUrl) body.destination_url = cf.destinationUrl;
					if (cf.budgetDaily) body.budget_daily = cf.budgetDaily;
					if (cf.objective) body.objective = cf.objective;
					method = 'POST';
					url = `${base}/api/v1/campaigns`;
				} else if (operation === 'update') {
					const cf = this.getNodeParameter('campaignFields', i, {}) as Record<string, any>;
					body = {};
					if (cf.status) body.status = cf.status;
					if (cf.type) body.type = cf.type;
					if (cf.utmSource) body.utm_source = cf.utmSource;
					if (cf.utmMedium) body.utm_medium = cf.utmMedium;
					if (cf.utmCampaign) body.utm_campaign = cf.utmCampaign;
					if (cf.destinationUrl) body.destination_url = cf.destinationUrl;
					if (cf.budgetDaily) body.budget_daily = cf.budgetDaily;
					if (cf.objective) body.objective = cf.objective;
					method = 'PUT';
					url = `${base}/api/v1/campaigns/${this.getNodeParameter('campaignId', i)}`;
				} else if (operation === 'delete') {
					method = 'DELETE';
					url = `${base}/api/v1/campaigns/${this.getNodeParameter('campaignId', i)}`;
				}
			}

			// ── PIPELINE ────────────────────────────────────────────────────────
			else if (resource === 'pipeline') {
				method = 'GET';
				url = `${base}/api/v1/pipelines`;
			}

			// ── Fazer requisição ────────────────────────────────────────────────
			const requestOptions: any = { method, url, headers, json: true };
			if (body && method !== 'GET') requestOptions.body = body;

			const response = (await this.helpers.request(requestOptions)) as any;

			// Expandir arrays em múltiplos itens de saída
			if (Array.isArray(response?.campaigns)) {
				for (const c of response.campaigns) {
					returnData.push({ json: c });
				}
			} else if (Array.isArray(response?.pipelines)) {
				for (const p of response.pipelines) {
					returnData.push({ json: p });
				}
			} else {
				returnData.push({ json: response });
			}
		}

		return [returnData];
	}
}
