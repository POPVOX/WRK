<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleCalendarService $calendarService
    ) {}

    /**
     * Redirect to Google OAuth.
     */
    public function redirect()
    {
        return redirect($this->calendarService->getAuthUrl());
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('dashboard')
                ->with('error', 'Google Calendar connection was cancelled.');
        }

        // Verify the state parameter for CSRF protection
        $state = $request->get('state');
        if (! $this->calendarService->verifyState($state)) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid state parameter. Please try again.');
        }

        $code = $request->get('code');

        if (! $code) {
            return redirect()->route('dashboard')
                ->with('error', 'No authorization code received from Google.');
        }

        try {
            $this->calendarService->handleCallback($code, Auth::user());

            // Check if user came from onboarding
            if (! Auth::user()->profile_completed_at) {
                return redirect()->route('onboarding', ['calendarConnected' => 1]);
            }

            return redirect()->route('dashboard')
                ->with('success', 'Google Calendar connected successfully!');
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: '.$e->getMessage());

            return redirect()->route('dashboard')
                ->with('error', 'Failed to connect Google Calendar. Please try again.');
        }
    }

    /**
     * Disconnect Google Calendar.
     */
    public function disconnect()
    {
        $this->calendarService->disconnect(Auth::user());

        return redirect()->route('dashboard')
            ->with('success', 'Google Calendar disconnected.');
    }
}
