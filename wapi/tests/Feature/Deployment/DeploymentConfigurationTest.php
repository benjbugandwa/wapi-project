<?php

declare(strict_types=1);

namespace Tests\Feature\Deployment;

use App\DTO\UploadDocumentData;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('railway deployment files are present and reference production commands', function () {
    $root = base_path();

    expect(file_exists($root.'/railway.json'))->toBeTrue()
        ->and(file_exists($root.'/nixpacks.toml'))->toBeTrue()
        ->and(file_exists($root.'/Procfile'))->toBeTrue()
        ->and(file_exists($root.'/scripts/railway-build.sh'))->toBeTrue()
        ->and(file_exists($root.'/scripts/railway-start.sh'))->toBeTrue()
        ->and(file_get_contents($root.'/railway.json'))->toContain('/up')
        ->and(file_get_contents($root.'/Procfile'))->toContain('queue:work')
        ->and(file_get_contents($root.'/Procfile'))->toContain('schedule:work')
        ->and(file_get_contents($root.'/nixpacks.toml'))->toContain('pdo_pgsql')
        ->and(file_get_contents($root.'/nixpacks.toml'))->toContain('nodejs_20');
});

test('production env example documents required deployment variables', function () {
    $envExample = file_get_contents(base_path('.env.example'));

    expect($envExample)->toContain('APP_ENV=production')
        ->and($envExample)->toContain('APP_DEBUG=false')
        ->and($envExample)->toContain('FILESYSTEM_DISK=railway')
        ->and($envExample)->toContain('DOCUMENTS_FILESYSTEM_DISK=railway')
        ->and($envExample)->toContain('QUEUE_CONNECTION=redis')
        ->and($envExample)->toContain('TWILIO_VALIDATE_WEBHOOK_SIGNATURE=true')
        ->and($envExample)->toContain('GOOGLE_CALENDAR_CLIENT_ID=')
        ->and($envExample)->toContain('MICROSOFT_GRAPH_CLIENT_ID=');
});

test('health endpoint is available for railway checks', function () {
    $this->get('/up')->assertOk();
});

test('document storage disk is configurable for persistent railway volume', function () {
    Storage::fake('deployment-documents');
    config()->set('wapi.documents.disk', 'deployment-documents');

    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $client = Client::factory()->for($cabinet)->create();

    $document = app(DocumentService::class)->upload(new UploadDocumentData(
        owner: $client,
        file: UploadedFile::fake()->create('contrat.pdf', 128, 'application/pdf'),
        categorie: 'contrat',
        estConfidentiel: true,
    ), $user);

    Storage::disk('deployment-documents')->assertExists($document->chemin_acces);
});

test('security headers include livewire compatible content security policy', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toBeString()
        ->and($csp)->toContain("'unsafe-eval'")
        ->and($csp)->toContain('https://fonts.bunny.net');
});

test('database pgsql configuration supports railway database url and pg environment variables', function () {
    putenv('DATABASE_URL=postgres://user:pass@ep-test.railway.internal:5432/railway');

    $config = require base_path('config/database.php');
    expect($config['connections']['pgsql']['url'])->toBe('postgres://user:pass@ep-test.railway.internal:5432/railway');

    putenv('DATABASE_URL');
});
