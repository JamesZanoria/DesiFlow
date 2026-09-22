<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartAssistantService
{
    protected $apiKey;
    protected $model;
    protected $systemPrompt;

    public function __construct()
    {
        $this->apiKey = config('assistant.api_key');
        $this->model = config('assistant.model');
        $this->systemPrompt = config('assistant.system_prompt');
    }

    /**
     * Main entry point to process a user message.
     * Now uses an AI Agentic Loop with Tool Use.
     */
    public function handleMessage(string $message, ?string $lastEntity = null): array
    {
        if (!$this->apiKey) {
            return [
                'answer' => "I'm sorry, but my AI brain is currently offline (API key missing). Please contact the admin.",
                'resolved_entity' => null,
            ];
        }

        $conversationHistory = $this->getConversationHistory(Auth::id());
        $conversationHistory[] = ['role' => 'user', 'content' => $message];

        try {
            $response = $this->executeAgentLoop($conversationHistory);
            $answer = $response['answer'];
            $entity = $response['resolved_entity'];

            // Save the final interaction to history
            $this->saveToHistory(Auth::id(), $message, $answer);

            return [
                'answer' => $answer,
                'resolved_entity' => $entity,
            ];
        } catch (\Exception $e) {
            Log::error("SmartAssistant Error: " . $e->getMessage());
            return [
                'answer' => "I encountered a slight hiccup while thinking. Could you please try asking that again?",
                'resolved_entity' => null,
            ];
        }
    }

    /**
     * The core Agentic Loop: Reasoning -> Tool Call -> Observation -> Final Answer.
     */
    private function executeAgentLoop(array $history): array
    {
        $currentHistory = $history;
        $resolvedEntity = null;

        // Maximum 3 iterations to prevent infinite loops
        for ($i = 0; $i < 3; $i++) {
            $response = $this->callLLM($currentHistory);

            if (!isset($response['tool_use'])) {
                return [
                    'answer' => $response['content'],
                    'resolved_entity' => $resolvedEntity,
                ];
            }

            $toolCall = $response['tool_use'];
            $toolName = $toolCall['name'];
            $args = $toolCall['input'] ?? [];

            // Execute the tool and get the observation
            $observation = $this->executeTool($toolName, $args);

            // Update entity if the tool returned one
            if (isset($observation['entity'])) {
                $resolvedEntity = $observation['entity'];
            }

            // Feed the observation back to the LLM
            $currentHistory[] = [
                'role' => 'assistant',
                'content' => $response['content'],
                'tool_use' => $toolCall
            ];
            $currentHistory[] = [
                'role' => 'user', // Anthropic uses 'user' role for tool results
                'content' => [
                    [
                        'type' => 'tool_result',
                        'tool_use_id' => $toolCall['id'],
                        'content' => $observation['result'],
                    ]
                ],
            ];
        }

        return [
            'answer' => "I'm having trouble gathering all the information. Could you rephrase your request?",
            'resolved_entity' => $resolvedEntity,
        ];
    }

    /**
     * Interface with the Anthropic API.
     */
    private function callLLM(array $messages): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 1024,
            'system' => $this->systemPrompt,
            'messages' => $messages,
            'tools' => $this->getToolDefinitions(),
        ]);

        if ($response->failed()) {
            throw new \Exception("LLM API Error: " . $response->body());
        }

        $data = $response->json();
        $contentBlock = $data['content'][0];

        $result = [
            'content' => '',
            'tool_use' => null,
        ];

        foreach ($data['content'] as $block) {
            if ($block['type'] === 'text') {
                $result['content'] .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $result['tool_use'] = [
                    'id' => $block['id'],
                    'name' => $block['name'],
                    'input' => $block['input'],
                ];
            }
        }

        return $result;
    }

    /**
     * Dispatcher for tool calls.
     */
    private function executeTool(string $name, array $args): array
    {
        switch ($name) {
            case 'get_stock_level':
                return $this->toolGetStockLevel($args['ingredient_name'] ?? '');
            case 'get_low_stock_items':
                return $this->toolGetLowStockItems();
            case 'check_menu_availability':
                return $this->toolCheckMenuAvailability($args['item_name'] ?? '');
            case 'get_daily_revenue':
                return $this->toolGetDailyRevenue();
            case 'get_staff_list':
                return $this->toolGetStaffList();
            case 'check_expiries':
                return $this->toolCheckExpiries();
            case 'search_business_kb':
                return $this->toolSearchBusinessKb($args['query'] ?? '');
            default:
                return ['result' => "Error: Tool $name not found.", 'entity' => null];
        }
    }

    // --- Tool Implementations ---

    private function toolGetStockLevel(string $name): array
    {
        $ing = Ingredient::where('ingredient_name', 'ILIKE', $name)->first();
        if (!$ing) return ['result' => "Ingredient '$name' not found.", 'entity' => null];

        return [
            'result' => "Current stock of {$ing->ingredient_name} is " . number_format($ing->current_stock, 2) . " {$ing->unit_of_measurement} (Threshold: {$ing->restock_threshold}).",
            'entity' => $ing->ingredient_name
        ];
    }

    private function toolGetLowStockItems(): array
    {
        $low = Ingredient::withCurrentStock()->get()->filter(fn($i) => $i->isLowStock());
        if ($low->isEmpty()) return ['result' => "All ingredients are currently above their restock thresholds.", 'entity' => null];

        $list = $low->map(fn($i) => "• {$i->ingredient_name}: " . number_format($i->current_stock, 2) . " {$i->unit_of_measurement} (Threshold: {$i->restock_threshold})")->implode("\n");
        return ['result' => "Low stock items:\n" . $list, 'entity' => null];
    }

    private function toolCheckMenuAvailability(string $name): array
    {
        $item = MenuItem::where('item_name', 'ILIKE', $name)->first();
        if (!$item) return ['result' => "Menu item '$name' not found.", 'entity' => null];

        $details = $item->getAvailabilityDetails();
        if ($details['is_available']) {
            return ['result' => "✅ {$item->item_name} is available.", 'entity' => $item->item_name];
        }

        $reasons = [];
        foreach ($details['failing_ingredients'] as $fail) {
            $unit = $fail['unit'] ?? '';
            $reasons[] = "• {$fail['name']}: required {$fail['required']} {$unit}, usable {$fail['available']}";
        }

        return [
            'result' => "❌ {$item->item_name} is unavailable because:\n" . implode("\n", $reasons),
            'entity' => $item->item_name
        ];
    }

    private function toolGetDailyRevenue(): array
    {
        if (Auth::user()->role !== 'admin') {
            return ['result' => "Unauthorized: Financial data is restricted to administrators.", 'entity' => null];
        }

        $total = Order::where('status', 'paid')->whereDate('created_at', today())->sum('total_amount');
        $count = Order::where('status', 'paid')->whereDate('created_at', today())->count();

        return [
            'result' => "Today's total revenue is ₱" . number_format($total, 2) . " from $count paid orders.",
            'entity' => null
        ];
    }

    private function toolGetStaffList(): array
    {
        $staff = User::whereIn('role', ['staff', 'admin'])->get();
        $list = $staff->map(fn($u) => "• {$u->name} ({ucfirst($u->role)})")->implode("\n");
        return ['result' => "Current active staff:\n" . $list, 'entity' => null];
    }

    private function toolCheckExpiries(): array
    {
        $expired = Ingredient::all()->filter(fn($i) => $i->expiredQuantity() > 0);
        $expiring = Ingredient::all()->filter(fn($i) => $i->expiringSoonQuantity() > 0);

        $res = "";
        if (!$expired->isEmpty()) {
            $res .= "🔴 Expired:\n" . $expired->map(fn($i) => "• {$i->ingredient_name}: " . number_format($i->expiredQuantity(), 2) . " {$i->unit_of_measurement}")->implode("\n") . "\n\n";
        }
        if (!$expiring->isEmpty()) {
            $res .= "🟡 Expiring Soon:\n" . $expiring->map(fn($i) => "• {$i->expiringSoonQuantity() > 0 ? $i->ingredient_name : ''}: " . number_format($i->expiringSoonQuantity(), 2) . " {$i->unit_of_measurement}")->implode("\n");
        }

        return ['result' => $res ?: "No ingredients are expired or expiring soon.", 'entity' => null];
    }

    private function toolSearchBusinessKb(string $query): array
    {
        // For now, we use the knowledge base provided in the system prompt.
        // This tool exists so the LLM can explicitly 'search' if it feels it needs to.
        return [
            'result' => "The business knowledge base is integrated into my core instructions. Please ask me specifically about policies or procedures.",
            'entity' => null
        ];
    }

    private function getToolDefinitions(): array
    {
        return [
            [
                'name' => 'get_stock_level',
                'description' => 'Get the current stock level of a specific ingredient.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ingredient_name' => ['type' => 'string', 'description' => 'The name of the ingredient (e.g., "Rice", "Onions")'],
                    ],
                    'required' => ['ingredient_name'],
                ],
            ],
            [
                'name' => 'get_low_stock_items',
                'description' => 'Get a list of all ingredients currently below their restock threshold.',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
            [
                'name' => 'check_menu_availability',
                'description' => 'Check if a specific menu item is available for sale.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'item_name' => ['type' => 'string', 'description' => 'The name of the menu item'],
                    ],
                    'required' => ['item_name'],
                ],
            ],
            [
                'name' => 'get_daily_revenue',
                'description' => 'Get total revenue and order count for today. (Admin only)',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
            [
                'name' => 'get_staff_list',
                'description' => 'Get a list of all active staff and their roles.',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
            [
                'name' => 'check_expiries',
                'description' => 'Check for expired or expiring ingredients.',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
            [
                'name' => 'search_business_kb',
                'description' => 'Search for operational policies and procedures.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'The query to search for in the knowledge base'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    private function getConversationHistory(int $userId): array
    {
        // Simple history implementation using Laravel Cache for 30 minutes
        return \Illuminate\Support\Facades\Cache::get("assistant_history_{$userId}", []);
    }

    private function saveToHistory(int $userId, string $userMessage, string $assistantAnswer): void
    {
        $history = $this->getConversationHistory($userId);
        $history[] = ['role' => 'user', 'content' => $userMessage];
        $history[] = ['role' => 'assistant', 'content' => $assistantAnswer];

        // Keep only last 10 exchanges to avoid token bloat
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        \Illuminate\Support\Facades\Cache::put("assistant_history_{$userId}", $history, now()->addMinutes(30));
    }
}
