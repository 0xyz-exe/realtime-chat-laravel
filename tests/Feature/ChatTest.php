<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_reuse_and_exchange_persisted_messages(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->actingAs($alice)->post(route('chat.create'), ['user_id' => $bob->id])->assertRedirect();
        $conversation = Conversation::firstOrFail();
        $this->assertCount(2, $conversation->users);
        $this->actingAs($bob)->post(route('chat.create'), ['user_id' => $alice->id])->assertRedirect(route('chat.show', $conversation));
        $this->assertDatabaseCount('conversations', 1);
        $this->actingAs($alice)->postJson(route('chat.send', $conversation), ['body' => '  Hello <b>world</b>  '])->assertCreated();
        $message = Message::firstOrFail();
        $this->assertSame('Hello <b>world</b>', $message->body);
        $this->actingAs($bob)->getJson(route('chat.messages', $conversation))->assertOk()->assertJsonPath('0.body', 'Hello <b>world</b>')->assertJsonPath('0.mine', false);
        $this->actingAs($bob)->get(route('chat.show', $conversation))->assertOk()->assertSee('Hello &lt;b&gt;world&lt;/b&gt;', false);
    }

    public function test_non_members_cannot_read_or_send_messages(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = Conversation::create(['is_group' => false]);
        $conversation->users()->attach([$alice->id, $bob->id]);
        $this->actingAs($outsider)->get(route('chat.show', $conversation))->assertNotFound();
        $this->actingAs($outsider)->getJson(route('chat.messages', $conversation))->assertNotFound();
        $this->actingAs($outsider)->postJson(route('chat.send', $conversation), ['body' => 'No'])->assertNotFound();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_message_size_is_validated_and_users_cannot_start_self_chat(): void
    {
        $alice = User::factory()->create();
        $this->actingAs($alice)->post(route('chat.create'), ['user_id' => $alice->id])->assertUnprocessable();
        $bob = User::factory()->create();
        $conversation = Conversation::create(['is_group' => false]);
        $conversation->users()->attach([$alice->id, $bob->id]);
        $this->actingAs($alice)->postJson(route('chat.send', $conversation), ['body' => str_repeat('x', 4001)])->assertUnprocessable()->assertJsonValidationErrors('body');
    }
}
