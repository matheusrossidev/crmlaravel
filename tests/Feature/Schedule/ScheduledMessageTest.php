<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use Tests\TestCase;

class ScheduledMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    public function test_can_create_scheduled_message(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->postJson("/contatos/{$lead->id}/mensagens-agendadas", [
            'type'    => 'text',
            'body'    => 'Olá! Tudo bem?',
            'send_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('scheduled_messages', [
            'tenant_id' => $this->tenant->id,
            'lead_id'   => $lead->id,
            'body'      => 'Olá! Tudo bem?',
            'status'    => 'pending',
        ]);
    }

    public function test_can_delete_scheduled_message(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $msg = ScheduledMessage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id'   => $lead->id,
        ]);

        $response = $this->deleteJson("/contatos/{$lead->id}/mensagens-agendadas/{$msg->id}");

        $response->assertSuccessful();

        // May hard delete or cancel (status change)
        $msg->refresh();
        $this->assertTrue(
            !$msg->exists || $msg->status === 'cancelled',
            'Message should be deleted or cancelled'
        );
    }

    public function test_scheduled_message_factory_defaults(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $msg = ScheduledMessage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id'   => $lead->id,
        ]);

        $this->assertEquals('pending', $msg->status);
        $this->assertEquals('text', $msg->type);
        $this->assertNotNull($msg->body);
        $this->assertNotNull($msg->send_at);
    }

    public function test_overdue_messages_have_past_send_at(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $msg = ScheduledMessage::factory()->overdue()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id'   => $lead->id,
        ]);

        $this->assertTrue($msg->send_at->isPast());
        $this->assertEquals('pending', $msg->status);
    }

    public function test_sent_messages_have_sent_status(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $msg = ScheduledMessage::factory()->sent()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id'   => $lead->id,
        ]);

        $this->assertEquals('sent', $msg->status);
        $this->assertNotNull($msg->sent_at);
    }
}
