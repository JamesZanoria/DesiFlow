<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmartAssistantService
{
    public function __construct()
    {
        // No longer requiring API keys for rule-based intelligence
    }

    /**
     * Main entry point to process a user message.
     * Uses a rule-based keyword mapping to dispatch tools.
     */
    public function handleMessage(string $message, ?string $conversationId = null, ?string $imageUrl = null): array
    {
        $input = strtolower($message);
        $resolvedEntity = null;
        $answer = "";
        $insights = null;

        // 1. Image Analysis Path (Priority)
        if ($imageUrl) {
            $result = $this->toolAnalyzeImage($imageUrl);
            $answer = $result['text'];
            $insights = $result['data'] ?? null;
        } else {
            // 2. Rule-Based Intent Mapping
            if (str_contains($input, 'stock') || str_contains($input, 'inventory') || str_contains($input, 'amount of')) {
                if (str_contains($input, 'low') || str_contains($input, 'critical')) {
                    $result = $this->toolGetLowStockItems();
                    $answer = $result['text'];
                    $insights = $result['data'] ?? null;
                } else {
                    // Try to extract ingredient name from message
                    $ingredientName = $this->extractIngredientName($message);
                    if ($ingredientName) {
                        $result = $this->toolGetStockLevel($ingredientName);
                        $answer = $result['text'];
                        $insights = $result['data'] ?? null;
                        $resolvedEntity = $result['entity'];
                    } else {
                        $answer = "I can check stock levels for you. Please specify an ingredient (e.g., 'How much Rice do we have?').";
                    }
                }
            } elseif (str_contains($input, 'revenue') || str_contains($input, 'earnings') || str_contains($input, 'money') || str_contains($input, 'sales')) {
                $result = $this->toolGetDailyRevenue();
                $answer = $result['text'];
                $insights = $result['data'] ?? null;
            } elseif (str_contains($input, 'available') || str_contains($input, 'availability') || str_contains($input, 'can i sell')) {
                $itemName = $this->extractMenuItemName($message);
                if ($itemName) {
                    $result = $this->toolCheckMenuAvailability($itemName);
                    $answer = $result['text'];
                    $insights = $result['data'] ?? null;
                    $resolvedEntity = $result['entity'];
                } else {
                    $answer = "I can check if a dish is available. Please specify the menu item name.";
                }
            } elseif (str_contains($input, 'staff') || str_contains($input, 'employee') || str_contains($input, 'who works')) {
                $result = $this->toolGetStaffList();
                $answer = $result['text'];
                $insights = $result['data'] ?? null;
            } elseif (str_contains($input, 'expiry') || str_contains($input, 'expired') || str_contains($input, 'spoilage')) {
                $result = $this->toolCheckExpiries();
                $answer = $result['text'];
                $insights = $result['data'] ?? null;
            } elseif (str_contains($input, 'hello') || str_contains($input, 'hi') || str_contains($input, 'hey')) {
                $answer = "Hello! I am DZAI, your Business Intelligence Assistant. I can help you with stock levels, daily revenue, menu availability, and staff lists. What would you like to check?";
            } else {
                $answer = "I'm sorry, I don't understand that request. I specialize in Desi Zaika's business data. You can ask me about stock levels, daily revenue, menu availability, or staff lists.";
            }
        }

        // 2. Persist Conversation
        if (!$conversationId) {
            $conversation = \App\Models\AssistantConversation::create([
                'user_id' => Auth::id(),
                'title' => $this->generateRuleBasedTitle($message, $imageUrl),
            ]);
            $conversationId = $conversation->id;
        }

        \App\Models\AssistantMessage::create([
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $message ?: ($imageUrl ? "Uploaded an image" : "Empty message"),
        ]);
        \App\Models\AssistantMessage::create([
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'content' => $answer,
        ]);

        return [
            'answer' => $answer,
            'insights' => $insights,
            'resolved_entity' => $resolvedEntity,
            'conversation_id' => $conversationId,
        ];
    }

    private function extractIngredientName(string $message): ?string
    {
        $ingredients = Ingredient::pluck('ingredient_name')->toArray();
        foreach ($ingredients as $name) {
            if (stripos($message, $name) !== false) return $name;
        }
        return null;
    }

    private function extractMenuItemName(string $message): ?string
    {
        $items = MenuItem::pluck('item_name')->toArray();
        foreach ($items as $name) {
            if (stripos($message, $name) !== false) return $name;
        }
        return null;
    }

    private function generateRuleBasedTitle(string $message, ?string $imageUrl = null): string
    {
        if ($imageUrl) return 'Image Analysis';
        if (str_contains(strtolower($message), 'stock')) return 'Stock Analysis';
        if (str_contains(strtolower($message), 'revenue')) return 'Revenue Report';
        if (str_contains(strtolower($message), 'available')) return 'Availability Check';
        if (str_contains(strtolower($message), 'staff')) return 'Staff Inquiry';
        return 'General Inquiry';
    }

    // --- Internal Tools ---

    private function toolAnalyzeImage(?string $url): array
    {
        return [
            'text' => "I have analyzed the uploaded image. Since I am currently in rule-based mode, I can see that you've uploaded a file, but I cannot 'see' the contents yet.",
            'data' => [
                'type' => 'alert',
                'content' => 'Image analysis feature is currently in simulation mode.'
            ]
        ];
    }

    private function toolGetStockLevel(string $name): array
    {
        $ing = Ingredient::where('ingredient_name', 'ILIKE', $name)->first();
        if (!$ing) return ['text' => "Ingredient '$name' not found.", 'data' => null, 'entity' => null];

        return [
            'text' => "Current stock of {$ing->ingredient_name} is " . number_format($ing->current_stock, 2) . " {$ing->unit_of_measurement} (Threshold: {$ing->restock_threshold}).",
            'data' => [
                'type' => 'kpi',
                'label' => $ing->ingredient_name,
                'value' => number_format($ing->current_stock, 2),
                'unit' => 'unit',
                'threshold' => $ing->restock_threshold,
                'status' => $ing->isLowStock() ? 'critical' : 'healthy'
            ],
            'entity' => $ing->ingredient_name
        ];
    }

    private function toolGetLowStockItems(): array
    {
        $low = Ingredient::withCurrentStock()->get()->filter(fn($i) => $i->isLowStock());
        if ($low->isEmpty()) return ['text' => "All ingredients are currently above their restock thresholds.", 'data' => null];

        $list = $low->map(fn($i) => "• {$i->ingredient_name}: " . number_format($i->current_stock, 2) . " {$i->unit_of_measurement} (Threshold: {$i->restock_threshold})")->implode("\n");

        return [
            'text' => "Low stock items:\n" . $list,
            'data' => [
                'type' => 'table',
                'label' => 'Critical Stock Levels',
                'rows' => $low->map(fn($i) => [
                    'name' => $i->ingredient_name,
                    'stock' => number_format($i->current_stock, 2),
                    'unit' => $i->unit_of_measurement,
                    'threshold' => $i->restock_threshold
                ])->toArray()
            ]
        ];
    }

    private function toolCheckMenuAvailability(string $name): array
    {
        $item = MenuItem::where('item_name', 'ILIKE', $name)->first();
        if (!$item) return ['text' => "Menu item '$name' not found.", 'data' => null, 'entity' => null];

        $details = $item->getAvailabilityDetails();
        if ($details['is_available']) {
            return [
                'text' => "✅ {$item->item_name} is available.",
                'data' => [
                    'type' => 'status',
                    'label' => $item->item_name,
                    'status' => 'Available',
                    'color' => 'green'
                ],
                'entity' => $item->item_name
            ];
        }

        $reasons = [];
        foreach ($details['failing_ingredients'] as $fail) {
            $unit = $fail['unit'] ?? '';
            $reasons[] = "• {$fail['name']}: required {$fail['required']} {$unit}, usable {$fail['available']}";
        }

        return [
            'text' => "❌ {$item->item_name} is unavailable because:\n" . implode("\n", $reasons),
            'data' => [
                'type' => 'alert',
                'label' => $item->item_name,
                'content' => 'Insufficient Ingredients',
                'color' => 'red'
            ],
            'entity' => $item->item_name
        ];
    }

    private function toolGetDailyRevenue(): array
    {
        if (Auth::user()->role !== 'admin') {
            return ['text' => "Unauthorized: Financial data is restricted to administrators.", 'data' => null];
        }

        $total = Order::where('status', 'paid')->whereDate('created_at', today())->sum('total_amount');
        $count = Order::where('status', 'paid')->whereDate('created_at', today())->count();

        return [
            'text' => "Today's total revenue is ₱" . number_format($total, 2) . " from $count paid orders.",
            'data' => [
                'type' => 'kpi',
                'label' => 'Today\'s Revenue',
                'value' => '₱' . number_format($total, 2),
                'count' => $count . ' Orders',
                'trend' => 'neutral'
            ]
        ];
    }

    private function toolGetStaffList(): array
    {
        $staff = User::whereIn('role', ['staff', 'admin'])->get();
        $list = $staff->map(fn($u) => "• {$u->name} ({ucfirst($u->role)})")->implode("\n");

        return [
            'text' => "Current active staff:\n" . $list,
            'data' => [
                'type' => 'table',
                'label' => 'Active Staff',
                'rows' => $staff->map(fn($u) => [
                    'name' => $u->name,
                    'role' => ucfirst($u->role)
                ])->toArray()
            ]
        ];
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
            $expiringList = $expiring->map(fn($i) => "• {$i->ingredient_name}: " . number_format($i->expiringSoonQuantity(), 2) . " {$i->unit_of_measurement}")->implode("\n");
            $res .= "🟡 Expiring Soon:\n" . $expiringList;
        }

        $data = null;
        if (!$expired->isEmpty() || !$expiring->isEmpty()) {
            $data = [
                'type' => 'alert',
                'label' => 'Inventory Health',
                'content' => "Found " . $expired->count() . " expired and " . $expiring->count() . " expiring soon ingredients.",
                'color' => 'red'
            ];
        }

        return [
            'text' => $res ?: "No ingredients are expired or expiring soon.",
            'data' => $data
        ];
    }
}
