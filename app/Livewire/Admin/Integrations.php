<?php

namespace App\Livewire\Admin;

use App\Models\BoxItem;
use App\Models\BoxWebhookEvent;
use App\Services\Box\BoxClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Integrations')]
class Integrations extends Component
{
    public array $box = [];

    public string $generatedAt = '';

    public function mount(): void
    {
        $this->refreshHealth();
    }

    public function refreshHealth(): void
    {
        $this->box = $this->loadBoxHealth();
        $this->generatedAt = now()->format('M j, Y g:i A');
    }

    protected function loadBoxHealth(): array
    {
        $client = app(BoxClient::class);

        $rootFolderId = trim((string) config('services.box.root_folder_id', ''));
        $projectsFolderId = trim((string) config('services.box.projects_folder_id', $rootFolderId));

        $authConfigured = false;
        $authError = null;

        try {
            $authConfigured = $client->isConfigured();
        } catch (\Throwable $exception) {
            $authError = $exception->getMessage();
        }

        $rootFolder = [
            'id' => $rootFolderId,
            'name' => null,
            'reachable' => null,
            'error' => null,
        ];

        $projectsFolder = [
            'id' => $projectsFolderId,
            'name' => null,
            'reachable' => null,
            'error' => null,
        ];

        if ($rootFolderId === '') {
            $rootFolder['error'] = 'BOX_ROOT_FOLDER_ID is empty.';
        }
        if ($projectsFolderId === '') {
            $projectsFolder['error'] = 'BOX_PROJECTS_FOLDER_ID is empty.';
        }

        $authHealthy = false;

        if ($authConfigured && $rootFolderId !== '') {
            try {
                $payload = $client->getFolder($rootFolderId);
                $rootFolder['name'] = trim((string) ($payload['name'] ?? ''));
                $rootFolder['reachable'] = true;
                $authHealthy = true;
            } catch (\Throwable $exception) {
                $rootFolder['reachable'] = false;
                $rootFolder['error'] = $exception->getMessage();
            }
        }

        if ($authConfigured && $projectsFolderId !== '') {
            if ($projectsFolderId === $rootFolderId && $rootFolder['reachable'] === true) {
                $projectsFolder['name'] = $rootFolder['name'];
                $projectsFolder['reachable'] = true;
            } else {
                try {
                    $payload = $client->getFolder($projectsFolderId);
                    $projectsFolder['name'] = trim((string) ($payload['name'] ?? ''));
                    $projectsFolder['reachable'] = true;
                    $authHealthy = true;
                } catch (\Throwable $exception) {
                    $projectsFolder['reachable'] = false;
                    $projectsFolder['error'] = $exception->getMessage();
                }
            }
        }

        if (! $authConfigured && $authError === null) {
            $authError = 'Box authentication is not configured.';
        } elseif ($authConfigured && ! $authHealthy && $authError === null) {
            $authError = 'Box authentication is configured, but folder access checks failed.';
        }

        $sync = [
            'tables_ready' => Schema::hasTable('box_items'),
            'total_items' => null,
            'file_items' => null,
            'folder_items' => null,
            'trashed_items' => null,
            'last_synced_at' => null,
            'last_synced_human' => null,
        ];

        if ($sync['tables_ready']) {
            try {
                $sync['total_items'] = BoxItem::query()->count();
                $sync['file_items'] = BoxItem::query()->where('box_item_type', 'file')->count();
                $sync['folder_items'] = BoxItem::query()->where('box_item_type', 'folder')->count();
                $sync['trashed_items'] = BoxItem::query()->whereNotNull('trashed_at')->count();
                $lastSynced = BoxItem::query()->max('last_synced_at');
                if ($lastSynced) {
                    $last = Carbon::parse((string) $lastSynced);
                    $sync['last_synced_at'] = $last->format('M j, Y g:i A');
                    $sync['last_synced_human'] = $last->diffForHumans();
                }
            } catch (\Throwable $exception) {
                $sync['tables_ready'] = false;
            }
        }

        $webhook = [
            'table_ready' => Schema::hasTable('box_webhook_events'),
            'event_count' => null,
            'last_event_at' => null,
            'last_event_human' => null,
            'last_status' => null,
            'last_trigger' => null,
            'last_delivery_id' => null,
            'last_source' => null,
            'last_processed_at' => null,
            'last_error_message' => null,
        ];

        if ($webhook['table_ready']) {
            try {
                $webhook['event_count'] = BoxWebhookEvent::query()->count();
                $event = BoxWebhookEvent::query()
                    ->latest('created_at')
                    ->first([
                        'delivery_id',
                        'trigger',
                        'source_type',
                        'source_id',
                        'status',
                        'error_message',
                        'processed_at',
                        'created_at',
                    ]);

                if ($event) {
                    $webhook['last_status'] = $event->status;
                    $webhook['last_trigger'] = $event->trigger;
                    $webhook['last_delivery_id'] = $event->delivery_id;
                    $webhook['last_source'] = trim((string) ($event->source_type.' '.$event->source_id));
                    $webhook['last_error_message'] = $event->error_message;

                    if ($event->created_at) {
                        $webhook['last_event_at'] = $event->created_at->format('M j, Y g:i A');
                        $webhook['last_event_human'] = $event->created_at->diffForHumans();
                    }
                    if ($event->processed_at) {
                        $webhook['last_processed_at'] = $event->processed_at->format('M j, Y g:i A');
                    }
                }
            } catch (\Throwable $exception) {
                $webhook['table_ready'] = false;
            }
        }

        return [
            'auth_configured' => $authConfigured,
            'auth_healthy' => $authHealthy,
            'auth_error' => $authError,
            'root_folder' => $rootFolder,
            'projects_folder' => $projectsFolder,
            'sync' => $sync,
            'webhook' => $webhook,
        ];
    }

    public function render()
    {
        return view('livewire.admin.integrations');
    }
}

