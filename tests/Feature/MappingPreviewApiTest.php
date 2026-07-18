<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('imports');

    Storage::disk('imports')->put('preview.csv', implode("\n", [
        'name,status,email',
        'Alice,active,alice@example.com',
        'Bob,inactive,bob@example.com',
    ]));
});

it('returns header and sample preview for mapping ui', function (): void {
    $response = $this->postJson('/dataflow/mapping-preview', [
        'disk' => 'imports',
        'path' => 'preview.csv',
        'format' => 'csv',
        'sample_size' => 2,
    ]);

    $response->assertOk();
    $response->assertJsonPath('headers.0', 'name');
    $response->assertJsonPath('sample.0.name', 'Alice');
});
