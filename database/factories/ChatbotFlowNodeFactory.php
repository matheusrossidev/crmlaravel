<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChatbotFlow;
use App\Models\ChatbotFlowNode;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatbotFlowNode>
 */
class ChatbotFlowNodeFactory extends Factory
{
    protected $model = ChatbotFlowNode::class;

    public function definition(): array
    {
        return [
            'flow_id'   => ChatbotFlow::factory(),
            'tenant_id' => Tenant::factory(),
            'type'      => 'message',
            'label'     => 'Mensagem',
            'config'    => ['text' => 'Olá! Como posso ajudar?'],
            'canvas_x'  => 100,
            'canvas_y'  => 100,
            'is_start'  => false,
        ];
    }

    public function start(): static
    {
        return $this->state(['is_start' => true]);
    }

    public function input(array $branches = []): static
    {
        return $this->state([
            'type'   => 'input',
            'label'  => 'Pergunta',
            'config' => [
                'text'     => 'Escolha uma opção:',
                'branches' => $branches ?: [
                    ['label' => 'Vendas', 'value' => 'vendas'],
                    ['label' => 'Suporte', 'value' => 'suporte'],
                ],
            ],
        ]);
    }

    public function action(string $actionType = 'change_stage'): static
    {
        return $this->state([
            'type'   => 'action',
            'label'  => 'Ação',
            'config' => ['action_type' => $actionType],
        ]);
    }

    public function end(): static
    {
        return $this->state([
            'type'   => 'end',
            'label'  => 'Fim',
            'config' => ['text' => 'Obrigado pelo contato!'],
        ]);
    }
}
