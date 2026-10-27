<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'customer_name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('customer_name', 120)->nullable();
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('order_items', 'item_name_snapshot')) {
                    $table->string('item_name_snapshot', 100)->nullable();
                }

                if (! Schema::hasColumn('order_items', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->nullable();
                }
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->increments('payment_id');
                $table->unsignedInteger('order_id');
                $table->string('payment_method', 20);
                $table->string('gcash_ref_num', 50)->nullable();
                $table->decimal('amount_tethered', 10, 2);
                $table->decimal('change_due', 10, 2)->default(0);
                $table->string('transaction_status', 20)->default('completed');
                $table->timestampTz('created_at')->nullable();
                $table->foreign('order_id')->references('order_id')->on('orders')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'transaction_status')) {
                    $table->string('transaction_status', 20)->default('completed');
                }

                if (! Schema::hasColumn('payments', 'created_at')) {
                    $table->timestampTz('created_at')->nullable();
                }
            });

            if (! Schema::hasIndex('payments', ['order_id'], 'unique')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->unique('order_id', 'payments_order_id_unique');
                });
            }
        }

        if (! Schema::hasTable('add_ons')) {
            Schema::create('add_ons', function (Blueprint $table) {
                $table->increments('add_on_id');
                $table->string('add_on_name', 100);
                $table->decimal('price', 10, 2);
                $table->boolean('is_available')->default(true);
            });
        }

        if (! Schema::hasTable('menu_item_add_ons')) {
            Schema::create('menu_item_add_ons', function (Blueprint $table) {
                $table->increments('menu_item_add_on_id');
                $table->unsignedInteger('menu_item_id');
                $table->unsignedInteger('add_on_id');
                $table->unique(['menu_item_id', 'add_on_id'], 'menu_item_add_ons_item_addon_unique');
                $table->foreign('menu_item_id')->references('menu_item_id')->on('menu_items')->cascadeOnDelete();
                $table->foreign('add_on_id')->references('add_on_id')->on('add_ons')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('order_item_add_ons')) {
            Schema::create('order_item_add_ons', function (Blueprint $table) {
                $table->increments('order_item_add_on_id');
                $table->unsignedInteger('order_item_id');
                $table->unsignedInteger('add_on_id')->nullable();
                $table->string('add_on_name_snapshot', 100);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total', 10, 2);
                $table->index('order_item_id', 'order_item_add_ons_order_item_index');
                $table->foreign('order_item_id')->references('order_item_id')->on('order_items')->cascadeOnDelete();
                $table->foreign('add_on_id')->references('add_on_id')->on('add_ons')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('transaction_requests')) {
            Schema::create('transaction_requests', function (Blueprint $table) {
                $table->bigIncrements('transaction_request_id');
                $table->unsignedInteger('payment_id');
                $table->unsignedInteger('requested_by');
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->string('request_type', 20);
                $table->string('reason', 500);
                $table->string('status', 20)->default('pending');
                $table->string('decision_reason', 500)->nullable();
                $table->timestampTz('requested_at');
                $table->timestampTz('reviewed_at')->nullable();
                $table->index(['status', 'requested_at'], 'transaction_requests_status_requested_index');
                $table->index('payment_id', 'transaction_requests_payment_index');
                $table->foreign('payment_id')->references('payment_id')->on('payments')->restrictOnDelete();
                $table->foreign('requested_by')->references('user_id')->on('users')->restrictOnDelete();
                $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_requests');
        Schema::dropIfExists('order_item_add_ons');
        Schema::dropIfExists('menu_item_add_ons');
        Schema::dropIfExists('add_ons');

        if (Schema::hasTable('payments')) {
            if (Schema::hasIndex('payments', 'payments_order_id_unique')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropUnique('payments_order_id_unique');
                });
            }

            Schema::table('payments', function (Blueprint $table) {
                foreach (['transaction_status', 'created_at'] as $column) {
                    if (Schema::hasColumn('payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                foreach (['item_name_snapshot', 'unit_price'] as $column) {
                    if (Schema::hasColumn('order_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('customer_name');
            });
        }
    }
};
