<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicUserAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_avatar_returns_404(): void
    {
        $this->get(route('public.user-avatar', ['filename' => 'does-not-exist.png']))
            ->assertNotFound();
    }

    public function test_invalid_extension_returns_404(): void
    {
        $this->get(route('public.user-avatar', ['filename' => 'evil.exe']))
            ->assertNotFound();
    }

    public function test_rejects_filename_without_stem(): void
    {
        $this->get(route('public.user-avatar', ['filename' => '.png']))
            ->assertNotFound();
    }

    public function test_path_segments_normalized_to_basename(): void
    {
        Storage::disk('local')->put('public/users/onlybasename.png', 'x');

        $this->get(route('public.user-avatar', ['filename' => 'fake/../onlybasename.png']))
            ->assertOk();
    }

    public function test_serves_existing_file_from_storage(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        Storage::disk('local')->put('public/users/test_avatar_fixture.png', $png);

        $this->get(route('public.user-avatar', ['filename' => 'test_avatar_fixture.png']))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_user_avatar_url_points_at_named_route(): void
    {
        $user = User::create([
            'name' => 'Avatar URL',
            'email' => uniqid('av', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
            'user_img' => 'u_fixture123.png',
        ]);

        Storage::disk('local')->put('public/users/u_fixture123.png', 'x');

        $url = $user->avatarUrl();
        $this->assertStringContainsString('files/user-avatars', $url);
        $this->assertStringContainsString('u_fixture123.png', $url);
    }
}
