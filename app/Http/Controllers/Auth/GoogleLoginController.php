<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SyncCalendarEvents;
use App\Jobs\SyncGmailMessages;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleLoginController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->withErrors([
                'form.email' => 'Google sign-in is not configured yet.',
            ]);
        }

        $state = Str::random(40);
        $request->session()->put('google_login_oauth_state', $state);

        $client = $this->googleClient();
        $client->setState($state);

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $sessionState = (string) $request->session()->pull('google_login_oauth_state', '');

        if ($state === '' || $sessionState === '' || ! hash_equals($sessionState, $state)) {
            return redirect()->route('login')->withErrors([
                'form.email' => 'Google sign-in session expired. Please try again.',
            ]);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('login')->withErrors([
                'form.email' => 'Google sign-in was cancelled.',
            ]);
        }

        $client = $this->googleClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (! empty($token['error'])) {
            return redirect()->route('login')->withErrors([
                'form.email' => 'Google sign-in failed. Please try again.',
            ]);
        }

        $email = null;
        $emailVerified = false;

        $idToken = $token['id_token'] ?? null;
        $payload = is_string($idToken) ? $client->verifyIdToken($idToken) : false;

        if (is_array($payload)) {
            $email = $payload['email'] ?? null;
            $emailVerified = (bool) ($payload['email_verified'] ?? false);
        } else {
            $client->setAccessToken($token);
            $oauth2 = new GoogleOauth2($client);
            $googleUser = $oauth2->userinfo->get();
            $email = $googleUser->getEmail();
            $emailVerified = (bool) $googleUser->getVerifiedEmail();
        }

        if (! is_string($email) || $email === '') {
            return redirect()->route('login')->withErrors([
                'form.email' => 'Google account email was not available.',
            ]);
        }

        $user = User::whereRaw('LOWER(email) = LOWER(?)', [$email])->first();
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'form.email' => "No WRK account exists for {$email}.",
            ]);
        }

        if ($emailVerified && $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        // Persist Google workspace tokens during sign-in so calendar/gmail are connected automatically.
        if (! empty($token['access_token'])) {
            $user->forceFill([
                'google_access_token' => $token['access_token'],
                'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                'google_token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        // Trigger background sync automatically at login.
        SyncCalendarEvents::dispatch($user);
        $gmailQueue = (string) (config('queue.gmail_queue') ?: 'default');
        SyncGmailMessages::dispatch($user, 90, 300)->onQueue($gmailQueue);

        return redirect()->intended(route('dashboard'));
    }

    protected function googleClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId((string) config('services.google.client_id'));
        $client->setClientSecret((string) config('services.google.client_secret'));
        $client->setRedirectUri((string) config('services.google.login_redirect_uri'));

        foreach ((array) config('services.google.workspace_scopes', []) as $scope) {
            if (is_string($scope) && trim($scope) !== '') {
                $client->addScope($scope);
            }
        }

        $client->addScope('openid');
        $client->addScope('email');
        $client->addScope('profile');
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->setPrompt('consent');

        return $client;
    }
}
