<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityMisconfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_php_upload_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $disallowedFile = UploadedFile::fake()->createWithContent(
            'script.php',
            'disallowed PHP upload'
        );

        $response = $this->actingAs($user)->post(route('files.upload'), [
            'file' => $disallowedFile,
        ]);

        $response->assertRedirect()->assertSessionHasErrors();
        $this->assertSame('File type not allowed', session('errors')->first());
        $this->assertDatabaseCount('files', 0);
        Storage::disk('local')->assertDirectoryEmpty("docs/users/{$user->id}");
    }

    public function test_valid_file_is_stored_privately_with_a_generated_uid(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $document = UploadedFile::fake()->create('report.pdf', 10, 'application/pdf');

        $response = $this->actingAs($user)->post(route('files.upload'), [
            'file' => $document,
        ]);

        $response->assertRedirect()->assertSessionHas('message', 'Upload successful');
        $record = File::sole();
        $this->assertSame('report.pdf', $record->name);
        $this->assertNotSame('report.pdf', $record->uid);
        Storage::disk('local')->assertExists("docs/users/{$user->id}/{$record->uid}");
        Storage::disk('public')->assertMissing("docs/users/{$user->id}/{$record->uid}");
    }

    public function test_user_cannot_download_another_users_private_file(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = File::create([
            'name' => 'private.pdf',
            'uid' => 'private-file.pdf',
            'user_id' => $owner->id,
        ]);
        Storage::disk('local')->put("docs/users/{$owner->id}/{$record->uid}", 'secret');

        $this->actingAs($otherUser)
            ->get(route('download.private', $record->uid))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('download.private', $record->uid))
            ->assertOk()
            ->assertDownload($record->uid);
    }
}
