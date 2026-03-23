<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCommunicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeTenantUser(string $email, ?string $tenantRole = null): User
    {
        return User::create([
            'name' => 'Tenant '.$email,
            'email' => $email,
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
            'tenant_role' => $tenantRole,
        ]);
    }

    public function test_guest_is_redirected_from_messages(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_super_admin_cannot_access_messages(): void
    {
        $super = User::create([
            'name' => 'Platform',
            'email' => uniqid('sa', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => true,
            'mobile' => '0',
            'status' => '1',
        ]);

        $this->actingAs($super)
            ->get(route('messages.index'))
            ->assertForbidden();
    }

    public function test_tenant_can_view_messages_inbox(): void
    {
        $user = $this->makeTenantUser(uniqid('u', true).'@example.test');

        $this->actingAs($user)
            ->get(route('messages.index'))
            ->assertOk();
    }

    public function test_tenant_can_send_message_and_view_thread(): void
    {
        $alice = $this->makeTenantUser(uniqid('alice', true).'@example.test');
        $bob = $this->makeTenantUser(uniqid('bob', true).'@example.test');

        $this->actingAs($alice)
            ->post(route('messages.store'), [
                'recipient_id' => $bob->id,
                'body' => 'Hello from Alice',
            ])
            ->assertRedirect(route('messages.show', $bob));

        $this->assertDatabaseHas('direct_messages', [
            'sender_id' => $alice->id,
            'recipient_id' => $bob->id,
        ]);

        $this->actingAs($alice)
            ->get(route('messages.show', $bob))
            ->assertOk()
            ->assertSee('Hello from Alice', false);
    }

    public function test_mark_all_messages_read_returns_ok_json(): void
    {
        $alice = $this->makeTenantUser(uniqid('a', true).'@example.test');
        $bob = $this->makeTenantUser(uniqid('b', true).'@example.test');

        DirectMessage::create([
            'company_id' => $alice->company_id,
            'sender_id' => $bob->id,
            'recipient_id' => $alice->id,
            'body' => 'Unread ping',
            'read_at' => null,
        ]);

        $this->actingAs($alice)
            ->postJson(route('messages.mark-all-read'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $dm = DirectMessage::query()->where('recipient_id', $alice->id)->first();
        $this->assertNotNull($dm);
        $this->assertNotNull($dm->read_at);
    }

    public function test_tenant_can_view_notifications_index(): void
    {
        $user = $this->makeTenantUser(uniqid('n', true).'@example.test');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();
    }

    public function test_cashier_cannot_create_announcement(): void
    {
        $user = $this->makeTenantUser(uniqid('c', true).'@example.test', 'cashier');

        $this->actingAs($user)
            ->get(route('notifications.create'))
            ->assertForbidden();
    }

    public function test_tenant_admin_can_create_announcement(): void
    {
        $admin = $this->makeTenantUser(uniqid('adm', true).'@example.test', 'tenant_admin');

        $this->actingAs($admin)
            ->get(route('notifications.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('notifications.store'), [
                'scope' => 'tenant',
                'title' => 'All-hands',
                'body' => 'Please read this update.',
            ])
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('announcements', [
            'author_id' => $admin->id,
            'title' => 'All-hands',
        ]);
    }

    public function test_mark_all_announcements_read_returns_ok_json(): void
    {
        $reader = $this->makeTenantUser(uniqid('r', true).'@example.test', 'cashier');
        $author = $this->makeTenantUser(uniqid('auth', true).'@example.test', 'tenant_admin');

        $ann = Announcement::create([
            'company_id' => $reader->company_id,
            'site_id' => null,
            'author_id' => $author->id,
            'title' => 'FYI',
            'body' => 'Body text',
        ]);

        $this->assertNotNull($ann->id);

        $this->actingAs($reader)
            ->postJson(route('notifications.mark-all-read'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_header_composer_runs_without_error_for_tenant(): void
    {
        $user = $this->makeTenantUser(uniqid('hdr', true).'@example.test');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
