<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSnapshot;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmartAssistantController extends Controller{
    // Show the Smart Assistant chat interface.
    public function index(){
        return view('admin.assistant.index');
    }

    // Accept a plain-language question, match it against known Desi Zaika question types, and return a direct answer.
    // Anything that doesn't match a known pattern gets a polite decline instead of a guess.
    // This assistant only knows about Desi Zaika's own data, nothing else.
    public function ask(Request $request){
        // Deliberately not $request->validate() — that relies on Laravel's
        // automatic expectsJson() detection to decide redirect-vs-422, which
        // was found unreliable elsewhere in this app (see MenuController) and
        // would surface here as the chat's fetch() call receiving an HTML
        // redirect instead of JSON, breaking response.json() outright rather
        // than showing a validation message.
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Please enter a question.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator);
        }

        $validated = $validator->validated();
        $question = strtolower($validated['question']);
        $answer = $this->matchQuestion($question);

        return response()->json([
            'question' => $validated['question'],
            'answer' => $answer,
        ]);
    }

    // Route the incoming question to the right handler based on keyword matching.
    // No third-party NLP service is used.
    private function matchQuestion(string $question): string{
        if(str_contains($question, 'stock') || str_contains($question, 'inventory')){
            return $this->answerCurrentStock();
        }

        if(str_contains($question, 'earning') || str_contains($question, 'revenue') || str_contains($question, 'sales today')){
            return $this->answerDailyEarnings();
        }

        if(str_contains($question, 'best selling') || str_contains($question, 'top seller') || str_contains($question, 'dish performance') || str_contains($question, 'best dish')){
            return $this->answerDishPerformance();
        }

        if(str_contains($question, 'reorder') || str_contains($question, 'restock')){
            return $this->answerReorderRecommendations();
        }

        return "I'm sorry, I can only answer questions about Desi Zaika Snack House's own operations, things like current stock, today's earnings, dish performance, or reorder recommendations. Could you rephrase your question around one of those?";
    }

    // Shared source of truth for "which ingredients need attention right now" —
    // both answerCurrentStock() and answerReorderRecommendations() were computing
    // this identically before, just formatting the result differently.
    private function lowStockIngredients(){
        return Ingredient::withCurrentStock()
            ->get()
            ->filter(fn ($ingredient) => $ingredient->isLowStock());
    }

    // List ingredients at or below their restock threshold, with quantities.
    private function answerCurrentStock(): string{
        $lowStock = $this->lowStockIngredients();

        if($lowStock->isEmpty()){
            return 'All ingredients are currently above their restock threshold. Stock levels look healthy.';
        }

        $list = $lowStock->map(fn ($i) => "{$i->ingredient_name} ({$i->current_stock} {$i->unit_of_measurement} left)")
            ->implode(', ');

        return "These ingredients are at or below their restock threshold: {$list}.";
    }

    // Sum of paid orders placed today.
    private function answerDailyEarnings(): string{
        $total = Order::where('status', 'paid')
            ->whereDate('created_at', today())
            ->sum('total_amount');

        return 'Total earnings today so far: ₱' . number_format($total, 2) . '.';
    }

    // Best-selling dish today, by quantity sold.
    private function answerDishPerformance(): string{
        $topDish = OrderItem::whereHas('order', function ($query) {
            $query->whereDate('created_at', today());
        })
            ->selectRaw('menu_item_id, SUM(quantity) as units_sold, SUM(total) as revenue')
            ->groupBy('menu_item_id')
            ->orderByDesc('units_sold')
            ->with('menuItem')
            ->first();

        if(! $topDish){
            return 'No orders have been placed yet today.';
        }

        return "Based on today's data, {$topDish->menuItem->item_name} is the top seller with {$topDish->units_sold} units sold, generating ₱" . number_format($topDish->revenue, 2) . '.';
    }

    // These are assumptions only, derived directly from current stock vs. restock_threshold.
    private function answerReorderRecommendations(): string{
        $needsReorder = $this->lowStockIngredients();

        if($needsReorder->isEmpty()){
            return 'Nothing needs reordering right now, all ingredients are above their restock threshold.';
        }

        $list = $needsReorder->map(fn ($i) => $i->ingredient_name)->implode(', ');

        return "I'd recommend reordering: {$list}.";
    }
}