<?php

namespace App\Http\Controllers;

use App\Services\Outreach\OutreachTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class OutreachTrackingController extends Controller
{
    public function open(string $token, OutreachTrackingService $tracking): Response
    {
        $tracking->recordOpen($token);
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');

        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function click(string $token, Request $request, OutreachTrackingService $tracking): RedirectResponse
    {
        try {
            $destination = $tracking->recordClick($token, (string) $request->query('url'));
        } catch (RuntimeException) {
            abort(400, 'Invalid tracking destination.');
        }

        return redirect()->away($destination);
    }
}
