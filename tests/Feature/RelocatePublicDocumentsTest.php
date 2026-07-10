<?php

use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Storage;

test('public documents are copied, verified, and removed from the public disk', function () {
    Storage::fake('public');
    Storage::fake(PrivateFiles::DISK);

    $path = 'project-documents/example.txt';
    Storage::disk('public')->put($path, 'project content');

    $this->artisan('files:relocate-private')
        ->expectsOutputToContain('Moved 1 file(s); 0 failed.')
        ->assertSuccessful();

    Storage::disk('public')->assertMissing($path);
    Storage::disk(PrivateFiles::DISK)->assertExists($path, 'project content');
});

test('public source is retained when an existing private destination differs', function () {
    Storage::fake('public');
    Storage::fake(PrivateFiles::DISK);

    $path = 'project-documents/example.txt';
    Storage::disk('public')->put($path, 'authoritative source');
    Storage::disk(PrivateFiles::DISK)->put($path, 'different');

    $this->artisan('files:relocate-private')
        ->expectsOutputToContain('not moved (destination differs)')
        ->assertFailed();

    Storage::disk('public')->assertExists($path, 'authoritative source');
    Storage::disk(PrivateFiles::DISK)->assertExists($path, 'different');
});
