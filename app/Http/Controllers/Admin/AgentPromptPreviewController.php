<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\Agents\PromptAssemblyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentPromptPreviewController extends Controller
{
    public function __invoke(Request $request, Agent $agent, PromptAssemblyService $promptAssemblyService): JsonResponse
    {
        return response()->json([
            'data' => $promptAssemblyService->assembleForAgent($agent, $request->user(), [
                'source' => 'admin.prompt_preview_endpoint',
            ]),
        ]);
    }
}
