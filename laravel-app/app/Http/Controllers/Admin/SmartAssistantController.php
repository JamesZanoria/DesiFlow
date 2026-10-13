<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmartAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmartAssistantController extends Controller
{
    protected $assistantService;

    public function __construct(SmartAssistantService $assistantService)
    {
        $this->assistantService = $assistantService;
    }

    public function ask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
            'context_entity' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'answer' => 'Please enter a message.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = $request->input('message');
        $contextEntity = $request->input('context_entity');

        $result = $this->assistantService->handleMessage($message, $contextEntity);

        return response()->json([
            'answer' => $result['answer'],
            'resolved_entity' => $result['resolved_entity'],
        ]);
    }
}
