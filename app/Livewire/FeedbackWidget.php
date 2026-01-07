<?php

namespace App\Livewire;

use App\Jobs\AnalyzeFeedback;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class FeedbackWidget extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public bool $isMinimized = true;

    public string $feedbackType = 'general';

    public string $category = '';

    public string $message = '';

    public $screenshot = null;

    public string $pageUrl = '';

    public string $pageTitle = '';

    public string $pageRoute = '';

    // Browser metadata (set via JS)
    public string $userAgent = '';

    public string $screenResolution = '';

    public string $viewportSize = '';

    // Success state
    public bool $submitted = false;

    public function mount()
    {
        $this->pageUrl = request()->fullUrl();
        $this->pageRoute = request()->route()?->getName() ?? '';
    }

    public function open()
    {
        $this->isOpen = true;
        $this->isMinimized = false;
        $this->submitted = false;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->reset(['feedbackType', 'category', 'message', 'screenshot', 'submitted']);
    }

    public function minimize()
    {
        $this->isMinimized = true;
    }

    public function expand()
    {
        $this->isMinimized = false;
    }

    public function updatedScreenshot()
    {
        $this->validateOnly('screenshot', [
            'screenshot' => 'nullable|image|max:5120', // 5MB max
        ]);
    }

    public function submit()
    {
        $this->validate([
            'message' => 'required|min:10|max:5000',
            'feedbackType' => 'required|in:bug,suggestion,compliment,question,general',
            'category' => 'nullable|in:ui,performance,feature,content,navigation,other',
            'screenshot' => 'nullable|image|max:5120',
        ]);

        $screenshotPath = null;
        if ($this->screenshot) {
            $screenshotPath = $this->screenshot->store('feedback-screenshots', 'public');
        }

        // Parse user agent
        $browserInfo = $this->parseUserAgent($this->userAgent);

        $feedback = Feedback::create([
            'user_id' => Auth::id(),
            'page_url' => $this->pageUrl,
            'page_title' => $this->pageTitle,
            'page_route' => $this->pageRoute,
            'feedback_type' => $this->feedbackType,
            'category' => $this->category ?: null,
            'message' => $this->message,
            'screenshot_path' => $screenshotPath,
            'user_agent' => $this->userAgent,
            'browser' => $browserInfo['browser'],
            'browser_version' => $browserInfo['version'],
            'os' => $browserInfo['os'],
            'device_type' => $browserInfo['device'],
            'screen_resolution' => $this->screenResolution,
            'viewport_size' => $this->viewportSize,
            'status' => 'new',
        ]);

        // Queue AI analysis
        if (config('ai.enabled')) {
            AnalyzeFeedback::dispatch($feedback->id);
        }

        $this->submitted = true;
        $this->reset(['feedbackType', 'category', 'message', 'screenshot']);

        $this->dispatch('notify', type: 'success', message: 'Thank you for your feedback!');
    }

    protected function parseUserAgent(string $userAgent): array
    {
        $browser = 'Unknown';
        $version = '';
        $os = 'Unknown';
        $device = 'desktop';

        // Detect browser
        if (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Firefox';
            $version = $matches[1];
        } elseif (preg_match('/Edg\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Edge';
            $version = $matches[1];
        } elseif (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Chrome';
            $version = $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches) && ! str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
            if (preg_match('/Version\/([0-9.]+)/', $userAgent, $vMatches)) {
                $version = $vMatches[1];
            }
        }

        // Detect OS
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iOS') || str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        }

        // Detect device type
        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) {
            $device = 'mobile';
        } elseif (str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad')) {
            $device = 'tablet';
        }

        return [
            'browser' => $browser,
            'version' => $version,
            'os' => $os,
            'device' => $device,
        ];
    }

    public function render()
    {
        return view('livewire.feedback-widget');
    }
}
