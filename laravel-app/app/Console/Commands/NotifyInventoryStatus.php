<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class NotifyInventoryStatus extends Command
{
    protected $signature = 'inventory:notify';
    protected $description = 'Scan inventory and create notifications for low stock, expired, or out-of-stock items';

    public function handle(NotificationService $notificationService)
    {
        $this->info('Scanning inventory for alertable statuses...');
        $notificationService->checkAndNotifyInventoryStatus();
        $this->info('Inventory scan complete. Notifications have been updated.');
    }
}
