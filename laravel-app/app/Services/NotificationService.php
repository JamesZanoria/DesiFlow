<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Ingredient;
use App\Models\User;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Create a notification for a specific user or all admins.
     */
    public function notify(array $data, $userId = null)
    {
        $userId = $userId ?: Auth::id();

        if (!$userId) {
            return null;
        }

        // Prevent duplicate notifications for the same type, title, and message within the last 24 hours
        $exists = Notification::where('user_id', $userId)
            ->where('type', $data['type'])
            ->where('title', $data['title'])
            ->where('message', $data['message'])
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($exists) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'icon' => $data['icon'] ?? 'info',
            'level' => $data['level'] ?? 'info',
            'action_url' => $data['action_url'] ?? null,
        ]);
    }

    /**
     * Notify all admin users.
     */
    public function notifyAllAdmins(array $data)
    {
        $admins = \App\Models\User::where('role', 'admin')->pluck('user_id');
        $notifications = [];

        foreach ($admins as $id) {
            $notifications[] = $this->notify($data, $id);
        }

        return $notifications;
    }

    /**
     * Notify all admin users about low stock.
     */
    public function checkAndNotifyLowStock(int $ingredientId)
    {
        $this->checkAndNotifyInventoryStatus($ingredientId);
    }

    /**
     * Specifically handle inventory status notifications.
     * Covers: Out of Stock, Expired, Expiring Soon, Low Stock.
     */
    public function checkAndNotifyInventoryStatus(?int $ingredientId = null)
    {
        $query = Ingredient::withCurrentStock()->withLastDeliveryAt();

        if ($ingredientId) {
            $query->where('ingredient_id', $ingredientId);
        }

        $ingredients = $query->get();

        foreach ($ingredients as $ingredient) {
            $status = $ingredient->stockStatus();
            $data = null;

            switch ($status) {
                case 'out_of_stock':
                    $data = [
                        'type' => 'inventory',
                        'title' => "Out of Stock: {$ingredient->ingredient_name}",
                        'message' => "This item is completely out of stock.",
                        'icon' => 'error',
                        'level' => 'critical',
                        'action_url' => route('admin.inventory.index'),
                    ];
                    break;
                case 'expired':
                    $data = [
                        'type' => 'inventory',
                        'title' => "Expired: {$ingredient->ingredient_name}",
                        'message' => "Stock has expired and must be wasted.",
                        'icon' => 'error',
                        'level' => 'critical',
                        'action_url' => route('admin.inventory.index'),
                    ];
                    break;
                case 'expiring_soon':
                    $data = [
                        'type' => 'inventory',
                        'title' => "Expiring Soon: {$ingredient->ingredient_name}",
                        'message' => "Stock will expire within the warning window.",
                        'icon' => 'warning',
                        'level' => 'warning',
                        'action_url' => route('admin.inventory.index'),
                    ];
                    break;
                case 'low_stock':
                    $data = [
                        'type' => 'inventory',
                        'title' => "Low Stock: {$ingredient->ingredient_name}",
                        'message' => number_format($ingredient->current_stock ?? 0, 2) . ' ' . $ingredient->unit_of_measurement . ' left',
                        'icon' => 'warning',
                        'level' => 'warning',
                        'action_url' => route('admin.inventory.index'),
                    ];
                    break;
            }

            if ($data) {
                $this->notifyAllAdmins($data);
            }
        }
    }

    /**
     * Notify menu availability changes.
     */
    public function notifyMenuAvailability(MenuItem $item, bool $available)
    {
        $this->notifyAllAdmins([
            'type' => 'menu',
            'title' => $available ? "Item Available: {$item->item_name}" : "Item Unavailable: {$item->item_name}",
            'message' => $available
                ? "Ingredients have been restored. Item is now active."
                : "Insufficient or expired inventory. Item is now unavailable.",
            'icon' => $available ? 'check' : 'warning',
            'level' => $available ? 'info' : 'warning',
            'action_url' => route('admin.menu.index'),
        ]);
    }

    /**
     * Notify staff account events.
     */
    public function notifyStaffEvent(string $action, User $user)
    {
        $messages = [
            'created' => "New staff account created: {$user->name} ({$user->role})",
            'deleted' => "Staff account removed: {$user->name}",
        ];

        $this->notifyAllAdmins([
            'type' => 'staff',
            'title' => "Staff Account Update",
            'message' => $messages[$action] ?? "Staff account {$user->name} was modified.",
            'icon' => $action === 'deleted' ? 'warning' : 'info',
            'level' => $action === 'deleted' ? 'warning' : 'info',
            'action_url' => route('admin.staff.index'),
        ]);
    }
}
