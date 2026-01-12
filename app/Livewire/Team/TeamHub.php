<?php

namespace App\Livewire\Team;

use App\Models\TeamMessage;
use App\Models\TeamMessageReaction;
use App\Models\TeamResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TeamHub extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $activeTab = 'team';

    public string $newMessage = '';

    public $messageScreenshot = null;

    public string $aiQuery = '';

    public string $aiResponse = '';

    public string $lastQuestion = '';

    public bool $isQuerying = false;

    public array $chatHistory = [];

    // Reply state
    public ?int $replyingTo = null;

    public string $replyContent = '';

    public $replyScreenshot = null;

    // Resource form
    public bool $showResourceForm = false;

    public ?int $editingResourceId = null;

    public string $resourceTitle = '';

    public string $resourceDescription = '';

    public string $resourceCategory = 'resource';

    public string $resourceUrl = '';

    public string $resourceIcon = '📄';

    // Add Team Member form
    public bool $showAddMemberForm = false;

    public string $memberName = '';

    public string $memberEmail = '';

    public string $memberTitle = '';

    public string $memberRole = 'staff';

    protected $queryString = ['activeTab'];

    protected $rules = [
        'newMessage' => 'required|min:2|max:2000',
        'messageScreenshot' => 'nullable|image|max:5120',
        'replyContent' => 'required|min:2|max:2000',
        'replyScreenshot' => 'nullable|image|max:5120',
        'aiQuery' => 'required|min:2|max:1000',
        'resourceTitle' => 'required|min:2|max:255',
        'resourceDescription' => 'nullable|max:1000',
        'resourceCategory' => 'required|in:policy,resource,howto,template',
        'resourceUrl' => 'nullable|url|max:500',
    ];

    // Available reactions
    public array $availableReactions = ['👍', '❤️', '🎉', '😂', '🤔', '👀'];

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function postMessage(): void
    {
        $this->validate([
            'newMessage' => $this->rules['newMessage'],
            'messageScreenshot' => $this->rules['messageScreenshot'],
        ]);

        $screenshotPath = null;
        if ($this->messageScreenshot) {
            $screenshotPath = $this->messageScreenshot->store('team-messages', 'public');
        }

        TeamMessage::create([
            'user_id' => Auth::id(),
            'content' => $this->newMessage,
            'screenshot_path' => $screenshotPath,
            'is_pinned' => false,
            'is_announcement' => false,
        ]);

        $this->newMessage = '';
        $this->messageScreenshot = null;
        $this->dispatch('message-posted');
    }

    public function startReply(int $messageId): void
    {
        $this->replyingTo = $messageId;
        $this->replyContent = '';
        $this->replyScreenshot = null;
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
        $this->replyContent = '';
        $this->replyScreenshot = null;
    }

    public function postReply(): void
    {
        $this->validate([
            'replyContent' => $this->rules['replyContent'],
            'replyScreenshot' => $this->rules['replyScreenshot'],
        ]);

        $screenshotPath = null;
        if ($this->replyScreenshot) {
            $screenshotPath = $this->replyScreenshot->store('team-messages', 'public');
        }

        TeamMessage::create([
            'user_id' => Auth::id(),
            'parent_id' => $this->replyingTo,
            'content' => $this->replyContent,
            'screenshot_path' => $screenshotPath,
            'is_pinned' => false,
            'is_announcement' => false,
        ]);

        $this->cancelReply();
        $this->dispatch('reply-posted');
    }

    public function toggleReaction(int $messageId, string $emoji): void
    {
        $existing = TeamMessageReaction::where('team_message_id', $messageId)
            ->where('user_id', Auth::id())
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            TeamMessageReaction::create([
                'team_message_id' => $messageId,
                'user_id' => Auth::id(),
                'emoji' => $emoji,
            ]);
        }
    }

    public function deleteMessage(int $messageId): void
    {
        $message = TeamMessage::find($messageId);
        if ($message && ($message->user_id === Auth::id() || Auth::user()->isAdmin())) {
            // Delete screenshot if exists
            if ($message->screenshot_path) {
                Storage::disk('public')->delete($message->screenshot_path);
            }
            $message->delete();
        }
    }

    public function togglePin(int $messageId): void
    {
        $message = TeamMessage::find($messageId);
        if ($message) {
            $message->update(['is_pinned' => ! $message->is_pinned]);
        }
    }

    public function queryHandbook(): void
    {
        $this->validate(['aiQuery' => $this->rules['aiQuery']]);
        $this->isQuerying = true;

        try {
            // Load handbook content
            $handbookPath = storage_path('app/documents/staff_handbook.md');
            $handbookContent = '';

            if (file_exists($handbookPath)) {
                $handbookContent = file_get_contents($handbookPath);
            }

            if (empty($handbookContent)) {
                $this->aiResponse = "I couldn't find the staff handbook. Please ensure it's been uploaded.";
                $this->isQuerying = false;

                return;
            }

            // Add to chat history
            $this->chatHistory[] = [
                'role' => 'user',
                'content' => $this->aiQuery,
            ];

            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 2000,
                'system' => $this->getHandbookSystemPrompt($handbookContent),
                'messages' => [
                    ['role' => 'user', 'content' => $this->aiQuery],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->aiResponse = $data['content'][0]['text'] ?? 'No response generated.';

                $this->chatHistory[] = [
                    'role' => 'assistant',
                    'content' => $this->aiResponse,
                ];
            } else {
                $this->aiResponse = 'Error querying the handbook. Please try again.';
            }
        } catch (\Exception $e) {
            $this->aiResponse = 'Error: '.$e->getMessage();
        }

        $this->lastQuestion = $this->aiQuery;
        $this->aiQuery = '';
        $this->isQuerying = false;
    }

    protected function getHandbookSystemPrompt(string $handbookContent): string
    {
        return <<<PROMPT
You are a helpful assistant for the POPVOX Foundation staff team. You have access to the staff handbook and can answer questions about team policies, procedures, culture, and work approaches.

Here is the staff handbook content:

<handbook>
{$handbookContent}
</handbook>

Guidelines:
- Answer questions based on the handbook content.
- Be friendly, concise, and helpful.
- If the answer isn't in the handbook, say so clearly.
- For questions about specific policies, quote relevant sections when helpful.
- Use a conversational but professional tone appropriate for colleagues.
- If asked about something outside the handbook scope, politely redirect to the appropriate resource or person.
PROMPT;
    }

    public function clearChat(): void
    {
        $this->chatHistory = [];
        $this->aiResponse = '';
        $this->lastQuestion = '';
    }

    public function saveResource(): void
    {
        $this->validate([
            'resourceTitle' => $this->rules['resourceTitle'],
            'resourceDescription' => $this->rules['resourceDescription'],
            'resourceCategory' => $this->rules['resourceCategory'],
            'resourceUrl' => $this->rules['resourceUrl'],
        ]);

        $data = [
            'title' => $this->resourceTitle,
            'description' => $this->resourceDescription,
            'category' => $this->resourceCategory,
            'url' => $this->resourceUrl,
            'icon' => $this->resourceIcon,
        ];

        if ($this->editingResourceId) {
            $resource = TeamResource::find($this->editingResourceId);
            if ($resource) {
                $resource->update($data);
            }
        } else {
            $data['created_by'] = Auth::id();
            TeamResource::create($data);
        }

        $this->resetResourceForm();
        $this->dispatch('resource-saved');
    }

    public function editResource(int $resourceId): void
    {
        $resource = TeamResource::find($resourceId);
        if ($resource) {
            $this->editingResourceId = $resourceId;
            $this->resourceTitle = $resource->title;
            $this->resourceDescription = $resource->description ?? '';
            $this->resourceCategory = $resource->category;
            $this->resourceUrl = $resource->url ?? '';
            $this->resourceIcon = $resource->icon ?? '📄';
            $this->showResourceForm = true;
        }
    }

    public function resetResourceForm(): void
    {
        $this->showResourceForm = false;
        $this->editingResourceId = null;
        $this->resourceTitle = '';
        $this->resourceDescription = '';
        $this->resourceCategory = 'resource';
        $this->resourceUrl = '';
        $this->resourceIcon = '📄';
    }

    public function deleteResource(int $resourceId): void
    {
        TeamResource::find($resourceId)?->delete();
    }

    public function saveMember(): void
    {
        $this->validate([
            'memberName' => 'required|min:2|max:255',
            'memberEmail' => 'required|email|unique:users,email',
            'memberTitle' => 'nullable|max:255',
            'memberRole' => 'required|in:admin,management,staff',
        ]);

        User::create([
            'name' => $this->memberName,
            'email' => $this->memberEmail,
            'password' => bcrypt(str()->random(16)), // Random password - they'll need to reset
            'title' => $this->memberTitle,
            'role' => $this->memberRole,
            'is_visible' => true,
        ]);

        $this->resetMemberForm();
        session()->flash('message', 'Team member added successfully. They will need to reset their password to log in.');
    }

    public function resetMemberForm(): void
    {
        $this->showAddMemberForm = false;
        $this->memberName = '';
        $this->memberEmail = '';
        $this->memberTitle = '';
        $this->memberRole = 'staff';
    }

    public function moveResourceUp(int $resourceId): void
    {
        $resource = TeamResource::find($resourceId);
        if (! $resource) {
            return;
        }

        $above = TeamResource::where('category', $resource->category)
            ->where('sort_order', '<', $resource->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($above) {
            $tempOrder = $resource->sort_order;
            $resource->sort_order = $above->sort_order;
            $above->sort_order = $tempOrder;
            $resource->save();
            $above->save();
        }
    }

    public function moveResourceDown(int $resourceId): void
    {
        $resource = TeamResource::find($resourceId);
        if (! $resource) {
            return;
        }

        $below = TeamResource::where('category', $resource->category)
            ->where('sort_order', '>', $resource->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($below) {
            $tempOrder = $resource->sort_order;
            $resource->sort_order = $below->sort_order;
            $below->sort_order = $tempOrder;
            $resource->save();
            $below->save();
        }
    }

    public function getTeamMapData()
    {
        $cityCoordinates = [
            'Washington, DC' => [38.9072, -77.0369],
            'New York, NY' => [40.7128, -74.0060],
            'Boston, MA' => [42.3601, -71.0589],
            'Philadelphia, PA' => [39.9526, -75.1652],
            'Atlanta, GA' => [33.7490, -84.3880],
            'Miami, FL' => [25.7617, -80.1918],
            'Cleveland, OH' => [41.4993, -81.6944],
            'Detroit, MI' => [42.3314, -83.0458],
            'Chicago, IL' => [41.8781, -87.6298],
            'Dallas, TX' => [32.7767, -96.7970],
            'Houston, TX' => [29.7604, -95.3698],
            'Austin, TX' => [30.2672, -97.7431],
            'Denver, CO' => [39.7392, -104.9903],
            'Boise, ID' => [43.6150, -116.2023],
            'Phoenix, AZ' => [33.4484, -112.0740],
            'Salt Lake City, UT' => [40.7608, -111.8910],
            'Las Vegas, NV' => [36.1699, -115.1398],
            'Los Angeles, CA' => [34.0522, -118.2437],
            'San Francisco, CA' => [37.7749, -122.4194],
            'San Jose, CA' => [37.3382, -121.8863],
            'San Diego, CA' => [32.7157, -117.1611],
            'Seattle, WA' => [47.6062, -122.3321],
            'Portland, OR' => [45.5152, -122.6784],
            'Redwood City, CA' => [37.4852, -122.2364],
            'Palo Alto, CA' => [37.4419, -122.1430],
            'Oakland, CA' => [37.8044, -122.2712],
            'Sacramento, CA' => [38.5816, -121.4944],
            'Nashville, TN' => [36.1627, -86.7816],
            'Jackson, TN' => [35.6145, -88.8139],
            'Huron, TN' => [35.6603, -88.4000],
            'Memphis, TN' => [35.1495, -90.0490],
            'Charlotte, NC' => [35.2271, -80.8431],
            'Baltimore, MD' => [39.2904, -76.6122],
            'Pittsburgh, PA' => [40.4406, -79.9959],
            'London, UK' => [51.5074, -0.1278],
            'Paris, France' => [48.8566, 2.3522],
            'Berlin, Germany' => [52.5200, 13.4050],
            'Amsterdam, Netherlands' => [52.3676, 4.9041],
            'Rome, Italy' => [41.9028, 12.4964],
            'Madrid, Spain' => [40.4168, -3.7038],
            'Toronto, Canada' => [43.6532, -79.3832],
            'Vancouver, Canada' => [49.2827, -123.1207],
            'São Paulo, Brazil' => [-23.5505, -46.6333],
            'Sao Paulo, Brazil' => [-23.5505, -46.6333],
            'Rio de Janeiro, Brazil' => [-22.9068, -43.1729],
            'Buenos Aires, Argentina' => [-34.6037, -58.3816],
            'Mexico City, Mexico' => [19.4326, -99.1332],
            'Tokyo, Japan' => [35.6762, 139.6503],
            'Sydney, Australia' => [-33.8688, 151.2093],
            'Singapore' => [1.3521, 103.8198],
            'Dubai, UAE' => [25.2048, 55.2708],
        ];

        $teamMembers = User::where('is_visible', true)->whereNotNull('location')->get();
        $mapData = [];

        foreach ($teamMembers as $member) {
            $location = $member->location;
            $coords = $cityCoordinates[$location] ?? null;

            if (! $coords) {
                foreach ($cityCoordinates as $city => $coordinates) {
                    if (stripos($location, explode(',', $city)[0]) !== false) {
                        $coords = $coordinates;
                        break;
                    }
                }
            }

            if ($coords) {
                $mapData[] = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'title' => $member->title,
                    'location' => $location,
                    'photo_url' => $member->photo_url,
                    'lat' => $coords[0],
                    'lng' => $coords[1],
                ];
            }
        }

        return $mapData;
    }

    public function render()
    {
        $teamMembers = User::where('is_visible', true)->orderBy('name')->get();

        // Only get parent messages (not replies)
        $messages = TeamMessage::with(['user', 'replies.user', 'replies.reactions.user', 'reactions.user'])
            ->whereNull('parent_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(20);

        $resources = TeamResource::orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        $pinnedMessages = TeamMessage::with('user')
            ->whereNull('parent_id')
            ->where('is_pinned', true)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.team.team-hub', [
            'teamMembers' => $teamMembers,
            'messages' => $messages,
            'resources' => $resources,
            'pinnedMessages' => $pinnedMessages,
            'resourceCategories' => TeamResource::CATEGORIES,
            'teamMapData' => $this->getTeamMapData(),
        ]);
    }
}
