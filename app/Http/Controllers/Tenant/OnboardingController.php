<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\LostSaleReason;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\WhatsappTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if ($tenant && $tenant->onboarding_completed_at !== null) {
            return redirect()->route('dashboard');
        }

        return view('tenant.onboarding.index', [
            'tenant' => $tenant,
            'user'   => auth()->user(),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:150',
            'niche'        => 'required|string|max:80',
            'logo'         => 'nullable|image|max:2048',
            'avatar'       => 'nullable|image|max:2048',
        ]);

        $user   = auth()->user();
        $tenant = $user->tenant;

        // 1. Atualizar nome da empresa
        $tenant->update(['name' => $data['company_name']]);

        // 2. Upload logo
        if ($request->hasFile('logo')) {
            $file     = $request->file('logo');
            $filename = $tenant->id . '.' . $file->extension();
            Storage::disk('public')->putFileAs('workspace-logos', $file, $filename);
            $tenant->update(['logo' => Storage::disk('public')->url('workspace-logos/' . $filename)]);
        }

        // 3. Upload avatar do usuário
        if ($request->hasFile('avatar')) {
            $file     = $request->file('avatar');
            $filename = $user->id . '.' . $file->extension();
            Storage::disk('public')->putFileAs('avatars', $file, $filename);
            $user->update(['avatar' => Storage::disk('public')->url('avatars/' . $filename)]);
        }

        // 4. Criar pipeline + etapas + tags + motivos com base no nicho
        $this->seedNicheData($tenant, $data['niche']);

        // 5. Marcar onboarding como concluído
        $tenant->update(['onboarding_completed_at' => now()]);

        return response()->json(['success' => true, 'redirect' => route('dashboard')]);
    }

    private function seedNicheData(Tenant $tenant, string $niche): void
    {
        $templates = $this->getNicheTemplates();
        $template  = $templates[$niche] ?? $templates['outro'];

        // Pipeline com etapas
        $pipeline = Pipeline::create([
            'tenant_id'  => $tenant->id,
            'name'       => $template['pipeline_name'],
            'color'      => '#3B82F6',
            'is_default' => true,
            'sort_order' => 1,
        ]);

        foreach ($template['stages'] as $i => $stage) {
            PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                'name'        => $stage['name'],
                'color'       => $stage['color'],
                'position'    => $i + 1,
                'is_won'      => $stage['is_won']  ?? false,
                'is_lost'     => $stage['is_lost'] ?? false,
            ]);
        }

        // Tags WhatsApp
        $tagColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        foreach ($template['tags'] as $i => $tagName) {
            WhatsappTag::create([
                'tenant_id'  => $tenant->id,
                'name'       => $tagName,
                'color'      => $tagColors[$i % count($tagColors)],
                'sort_order' => $i + 1,
            ]);
        }

        // Motivos de perda
        foreach ($template['loss_reasons'] as $i => $reason) {
            LostSaleReason::create([
                'tenant_id'  => $tenant->id,
                'name'       => $reason,
                'sort_order' => $i + 1,
                'is_active'  => true,
            ]);
        }
    }

    private function getNicheTemplates(): array
    {
        return [
            'imobiliario' => [
                'pipeline_name' => 'Funil Imobiliário',
                'stages'        => [
                    ['name' => 'Novo Lead',        'color' => '#6B7280'],
                    ['name' => 'Visita Agendada',  'color' => '#3B82F6'],
                    ['name' => 'Proposta Enviada', 'color' => '#F59E0B'],
                    ['name' => 'Negociação',       'color' => '#8B5CF6'],
                    ['name' => 'Fechado',          'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Perdido',          'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Comprador', 'Locatário', 'Investidor', 'Urgente', 'Alto Padrão'],
                'loss_reasons' => ['Preço alto', 'Não encontrou o imóvel ideal', 'Financiamento negado', 'Comprou com outro corretor', 'Sem interesse'],
            ],
            'estetica' => [
                'pipeline_name' => 'Agendamentos',
                'stages'        => [
                    ['name' => 'Lead Novo',          'color' => '#6B7280'],
                    ['name' => 'Consulta Agendada',  'color' => '#3B82F6'],
                    ['name' => 'Consulta Realizada', 'color' => '#F59E0B'],
                    ['name' => 'Proposta Enviada',   'color' => '#8B5CF6'],
                    ['name' => 'Fechado',            'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Perdido',            'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Facial', 'Corporal', 'Capilar', 'Retorno', 'Novo Cliente'],
                'loss_reasons' => ['Preço alto', 'Sem tempo', 'Optou por outra clínica', 'Não respondeu', 'Mudou de ideia'],
            ],
            'educacao' => [
                'pipeline_name' => 'Matrículas',
                'stages'        => [
                    ['name' => 'Interessado',        'color' => '#6B7280'],
                    ['name' => 'Apresentação Feita', 'color' => '#3B82F6'],
                    ['name' => 'Proposta Enviada',   'color' => '#F59E0B'],
                    ['name' => 'Matriculado',        'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Desistiu',           'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Curso Online', 'Presencial', 'Bolsa', 'Graduação', 'Pós-Graduação'],
                'loss_reasons' => ['Preço alto', 'Sem tempo', 'Optou por outro curso', 'Não respondeu', 'Não passou no processo'],
            ],
            'saude' => [
                'pipeline_name' => 'Pacientes',
                'stages'        => [
                    ['name' => 'Primeiro Contato',       'color' => '#6B7280'],
                    ['name' => 'Consulta Agendada',      'color' => '#3B82F6'],
                    ['name' => 'Avaliação Realizada',    'color' => '#F59E0B'],
                    ['name' => 'Proposta de Tratamento', 'color' => '#8B5CF6'],
                    ['name' => 'Em Tratamento',          'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Perdido',                'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Plano de Saúde', 'Particular', 'Urgente', 'Retorno', 'Novo Paciente'],
                'loss_reasons' => ['Plano não aceito', 'Preço alto', 'Optou por outra clínica', 'Não respondeu', 'Falta de transporte'],
            ],
            'varejo' => [
                'pipeline_name' => 'Vendas',
                'stages'        => [
                    ['name' => 'Carrinho Abandonado',  'color' => '#6B7280'],
                    ['name' => 'Interesse Confirmado', 'color' => '#3B82F6'],
                    ['name' => 'Pedido Realizado',     'color' => '#F59E0B'],
                    ['name' => 'Em Processamento',     'color' => '#8B5CF6'],
                    ['name' => 'Entregue',             'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Devolvido',            'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Cliente Novo', 'Recorrente', 'VIP', 'Atacado', 'Promoção'],
                'loss_reasons' => ['Preço alto', 'Frete caro', 'Produto indisponível', 'Optou por concorrente', 'Desistiu no checkout'],
            ],
            'b2b' => [
                'pipeline_name' => 'Oportunidades B2B',
                'stages'        => [
                    ['name' => 'Prospecção',   'color' => '#6B7280'],
                    ['name' => 'Qualificação', 'color' => '#3B82F6'],
                    ['name' => 'Proposta',     'color' => '#F59E0B'],
                    ['name' => 'Negociação',   'color' => '#8B5CF6'],
                    ['name' => 'Fechado',      'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Perdido',      'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Pequena Empresa', 'Média Empresa', 'Grande Empresa', 'Urgente', 'Parceria'],
                'loss_reasons' => ['Preço alto', 'Sem orçamento', 'Optou por concorrente', 'Projeto cancelado', 'Timing errado'],
            ],
            'tecnologia' => [
                'pipeline_name' => 'Demo e Vendas SaaS',
                'stages'        => [
                    ['name' => 'Lead Novo',      'color' => '#6B7280'],
                    ['name' => 'Demo Agendada',  'color' => '#3B82F6'],
                    ['name' => 'Demo Realizada', 'color' => '#F59E0B'],
                    ['name' => 'Trial Ativo',    'color' => '#8B5CF6'],
                    ['name' => 'Fechado',        'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Churned',        'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Startup', 'Corporativo', 'Trial', 'Demo Solicitada', 'Enterprise'],
                'loss_reasons' => ['Sem budget', 'Optou por concorrente', 'Feature não disponível', 'Projeto pausado', 'Saiu do trial sem converter'],
            ],
            'outro' => [
                'pipeline_name' => 'Funil de Vendas',
                'stages'        => [
                    ['name' => 'Novo Lead',        'color' => '#6B7280'],
                    ['name' => 'Em Contato',       'color' => '#3B82F6'],
                    ['name' => 'Proposta Enviada', 'color' => '#F59E0B'],
                    ['name' => 'Negociação',       'color' => '#8B5CF6'],
                    ['name' => 'Fechado',          'color' => '#10B981', 'is_won'  => true],
                    ['name' => 'Perdido',          'color' => '#EF4444', 'is_lost' => true],
                ],
                'tags'         => ['Quente', 'Morno', 'Frio', 'Prioritário', 'Retorno'],
                'loss_reasons' => ['Preço alto', 'Sem interesse', 'Sem retorno', 'Optou por concorrente', 'Timing errado'],
            ],
        ];
    }
}
