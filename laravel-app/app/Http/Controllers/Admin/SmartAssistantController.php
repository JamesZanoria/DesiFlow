<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmartAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\Concerns\HandlesProtectedImageUploads;

class SmartAssistantController extends Controller
{
    use HandlesProtectedImageUploads;

    protected $assistantService;

    public function __construct(SmartAssistantService $assistantService)
    {
        $this->assistantService = $assistantService;
    }

    public function index(Request $request)
    {
        $userId = Auth::id();
        $conversations = \App\Models\AssistantConversation::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $activeConversationId = $request->query('conversation_id');
        $messages = [];

        if ($activeConversationId) {
            $messages = \App\Models\AssistantMessage::where('conversation_id', $activeConversationId)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('admin.assistant.index', [
            'conversations' => $conversations,
            'activeConversationId' => $activeConversationId,
            'messages' => $messages,
        ]);
    }

    public function ask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:500',
            'conversation_id' => 'nullable|integer',
            'context_entity' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'answer' => 'Invalid request. Please check your input.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$request->has('message') && !$request->hasFile('image')) {
            return response()->json([
                'answer' => 'Please enter a message or upload an image.',
            ], 422);
        }

        $message = $request->input('message', '');
        $conversationId = $request->input('conversation_id');
        $imageUrl = null;

        if ($request->hasFile('image')) {
            $imageUrl = $this->uploadProtectedImage($request->file('image'), 'assistant_uploads');
        }

        $result = $this->assistantService->handleMessage($message, $conversationId, $imageUrl);

        return response()->json([
            'answer' => $result['answer'],
            'insights' => $result['insights'] ?? null,
            'resolved_entity' => $result['resolved_entity'] ?? null,
            'conversation_id' => $result['conversation_id'] ?? null,
        ]);
    }
}
