<?php

namespace App\Livewire\Admin;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Notifications\WorkspaceAlert;
use App\Services\Notifications\WorkspaceNotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notifications Admin')]
class NotificationsManager extends Component
{
    public string $templateSearch = '';

    public ?int $selectedTemplateId = null;

    public string $audience = 'all_staff';

    public array $selectedUserIds = [];

    public string $sendKind = 'manual_notice';

    public string $sendCategory = 'general';

    public string $sendLevel = 'info';

    public string $sendTitle = '';

    public string $sendBody = '';

    public string $sendActionLabel = '';

    public string $sendActionUrl = '';

    public string $newTemplateName = '';

    public string $newTemplateKind = 'manual_notice';

    public string $newTemplateCategory = 'general';

    public string $newTemplateLevel = 'info';

    public string $newTemplateTitle = '';

    public string $newTemplateBody = '';

    public string $newTemplateActionLabel = '';

    public string $newTemplateActionUrl = '';

    public bool $newTemplateIsActive = true;

    public function mount(): void
    {
        abort_unless(Auth::user()?->isManagement(), 403);
    }

    protected function sendRules(): array
    {
        return [
            'audience' => 'required|in:all_staff,management,admins,specific_users',
            'selectedUserIds' => 'array',
            'selectedUserIds.*' => 'integer|exists:users,id',
            'sendKind' => 'required|string|max:64',
            'sendCategory' => 'required|in:general,project,travel,meeting,funding,team,inbox,system',
            'sendLevel' => 'required|in:info,success,warning,urgent',
            'sendTitle' => 'required|string|max:255',
            'sendBody' => 'required|string|max:3000',
            'sendActionLabel' => 'nullable|string|max:80',
            'sendActionUrl' => 'nullable|string|max:1024',
        ];
    }

    protected function templateRules(): array
    {
        return [
            'newTemplateName' => 'required|string|max:120',
            'newTemplateKind' => 'required|string|max:64',
            'newTemplateCategory' => 'required|in:general,project,travel,meeting,funding,team,inbox,system',
            'newTemplateLevel' => 'required|in:info,success,warning,urgent',
            'newTemplateTitle' => 'required|string|max:255',
            'newTemplateBody' => 'required|string|max:3000',
            'newTemplateActionLabel' => 'nullable|string|max:80',
            'newTemplateActionUrl' => 'nullable|string|max:1024',
            'newTemplateIsActive' => 'boolean',
        ];
    }

    public function applyTemplate(int $templateId): void
    {
        $template = NotificationTemplate::query()->find($templateId);
        if (! $template) {
            $this->dispatch('notify', type: 'error', message: 'Template not found.');

            return;
        }

        $this->selectedTemplateId = $template->id;
        $this->sendKind = $template->kind;
        $this->sendCategory = $template->category;
        $this->sendLevel = $template->default_level;
        $this->sendTitle = $template->title_template;
        $this->sendBody = $template->body_template;
        $this->sendActionLabel = $template->default_action_label ?? '';
        $this->sendActionUrl = $template->default_action_url ?? '';
    }

    public function sendNow(WorkspaceNotificationService $notifications): void
    {
        $this->validate($this->sendRules());

        if ($this->audience === 'specific_users' && empty($this->selectedUserIds)) {
            $this->addError('selectedUserIds', 'Select at least one recipient.');

            return;
        }

        $recipients = $notifications->resolveAudience($this->audience, $this->selectedUserIds);
        if ($recipients->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'No recipients matched this audience.');

            return;
        }

        $actionUrl = trim($this->sendActionUrl);
        if ($actionUrl !== '' && str_starts_with($actionUrl, '/')) {
            $actionUrl = url($actionUrl);
        }

        $sent = $notifications->sendToUsers(
            $recipients,
            trim($this->sendKind),
            trim($this->sendTitle),
            trim($this->sendBody),
            [
                'category' => $this->sendCategory,
                'level' => $this->sendLevel,
                'action_label' => trim($this->sendActionLabel) !== '' ? trim($this->sendActionLabel) : null,
                'action_url' => $actionUrl !== '' ? $actionUrl : null,
                'actor' => Auth::user(),
                'manual' => true,
                'meta' => [
                    'template_id' => $this->selectedTemplateId,
                    'audience' => $this->audience,
                ],
            ],
        );

        $this->dispatch('notify', type: 'success', message: "Notification sent to {$sent} recipient(s).");
    }

    public function saveTemplate(): void
    {
        $this->validate($this->templateRules());

        NotificationTemplate::create([
            'name' => trim($this->newTemplateName),
            'kind' => trim($this->newTemplateKind),
            'category' => $this->newTemplateCategory,
            'title_template' => trim($this->newTemplateTitle),
            'body_template' => trim($this->newTemplateBody),
            'default_level' => $this->newTemplateLevel,
            'default_action_label' => trim($this->newTemplateActionLabel) !== '' ? trim($this->newTemplateActionLabel) : null,
            'default_action_url' => trim($this->newTemplateActionUrl) !== '' ? trim($this->newTemplateActionUrl) : null,
            'is_active' => $this->newTemplateIsActive,
            'created_by' => Auth::id(),
        ]);

        $this->reset([
            'newTemplateName',
            'newTemplateKind',
            'newTemplateCategory',
            'newTemplateLevel',
            'newTemplateTitle',
            'newTemplateBody',
            'newTemplateActionLabel',
            'newTemplateActionUrl',
            'newTemplateIsActive',
        ]);

        $this->newTemplateKind = 'manual_notice';
        $this->newTemplateCategory = 'general';
        $this->newTemplateLevel = 'info';
        $this->newTemplateIsActive = true;

        $this->dispatch('notify', type: 'success', message: 'Template saved.');
    }

    public function toggleTemplateActive(int $templateId): void
    {
        $template = NotificationTemplate::query()->find($templateId);
        if (! $template) {
            return;
        }

        $template->update(['is_active' => ! $template->is_active]);
        $this->dispatch('notify', type: 'success', message: 'Template status updated.');
    }

    public function deleteTemplate(int $templateId): void
    {
        $template = NotificationTemplate::query()->find($templateId);
        if (! $template) {
            return;
        }

        $template->delete();
        if ($this->selectedTemplateId === $templateId) {
            $this->selectedTemplateId = null;
        }

        $this->dispatch('notify', type: 'success', message: 'Template deleted.');
    }

    public function render()
    {
        $templates = NotificationTemplate::query()
            ->when(
                trim($this->templateSearch) !== '',
                function ($query) {
                    $needle = '%'.mb_strtolower(trim($this->templateSearch)).'%';

                    $query->where(function ($inner) use ($needle) {
                        $inner->whereRaw('LOWER(name) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(title_template) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(body_template) LIKE ?', [$needle]);
                    });
                }
            )
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->where('is_visible', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'access_level', 'is_admin']);

        $recent = DatabaseNotification::query()
            ->where('type', WorkspaceAlert::class)
            ->latest()
            ->limit(40)
            ->get()
            ->filter(fn (DatabaseNotification $item) => (bool) ($item->data['manual'] ?? false))
            ->take(15)
            ->values();

        $userNames = $users->pluck('name', 'id');
        $recentManualNotifications = $recent->map(function (DatabaseNotification $item) use ($userNames) {
            return [
                'id' => $item->id,
                'title' => (string) ($item->data['title'] ?? 'Notification'),
                'body' => (string) ($item->data['body'] ?? ''),
                'recipient' => $userNames[$item->notifiable_id] ?? "User #{$item->notifiable_id}",
                'time_label' => optional($item->created_at)->diffForHumans() ?? '',
            ];
        });

        return view('livewire.admin.notifications-manager', [
            'templates' => $templates,
            'users' => $users,
            'recentManualNotifications' => $recentManualNotifications,
            'audiences' => [
                'all_staff' => 'All staff',
                'management' => 'Management',
                'admins' => 'Admins',
                'specific_users' => 'Specific users',
            ],
            'categories' => [
                'general' => 'General',
                'project' => 'Project',
                'travel' => 'Travel',
                'meeting' => 'Meeting',
                'funding' => 'Funding',
                'team' => 'Team',
                'inbox' => 'Inbox',
                'system' => 'System',
            ],
            'levels' => [
                'info' => 'Info',
                'success' => 'Positive',
                'warning' => 'Action Needed',
                'urgent' => 'Urgent',
            ],
        ]);
    }
}

