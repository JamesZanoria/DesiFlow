<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    /**
     * Fetch all available menu items for the customer ordering dashboard.
     */
    public function index(): JsonResponse
    {
        $menuItems = MenuItem::where('is_available', true)
            ->select(['menu_item_id as id', 'item_name as name', 'category', 'price', 'image_url as image', 'description'])
            ->get()
            ->map(function ($item) {
                $cat = strtolower(trim($item->category));
                
                // Map singular DB categories to plural frontend tabs
                if ($cat === 'drink') {
                    $cat = 'drinks';
                    }
                    $item->category = $cat;
                    $item->status = 'available';
                    return $item;
                });
        
        return response()->json($menuItems);
    }
}