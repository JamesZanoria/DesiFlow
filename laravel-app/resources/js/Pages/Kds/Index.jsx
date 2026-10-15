import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';

const STATUS_CONFIG = {
    pending: {
        label: 'New',
        dot: 'bg-blue-500',
        border: 'border-blue-500',
        nextStatus: 'preparing',
        buttonLabel: 'Start preparing',
        buttonStyle: 'bg-slate-900 text-white hover:bg-slate-700',
    },
    preparing: {
        label: 'Preparing',
        dot: 'bg-amber-500',
        border: 'border-amber-500',
        nextStatus: 'ready',
        buttonLabel: 'Mark ready',
        buttonStyle: 'bg-slate-900 text-white hover:bg-slate-700',
    },
    ready: {
        label: 'Ready',
        dot: 'bg-green-500',
        border: 'border-green-500',
        nextStatus: 'preparing',
        buttonLabel: 'Reopen ticket',
        buttonStyle: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
    },
};

const KdsTicket = ({ order, status }) => {
    const config = STATUS_CONFIG[status];
    const [isUpdating, setIsUpdating] = useState(false);

    const handleStatusUpdate = async () => {
        setIsUpdating(true);
        try {
            const response = await fetch(route('kds.orders.status', order.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ status: config.nextStatus }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Could not update order status.');
            }

            // Trigger Inertia reload to update the board globally
            router.reload({ only: ['columns'] });
        } catch (error) {
            alert(error.message);
        } finally {
            setIsUpdating(false);
        }
    };

    return (
        <article className={`rounded-lg border-l-4 ${config.border} border-y border-r border-slate-200 p-3 bg-white shadow-sm transition-all hover:shadow-md`}>
            <div className="flex items-start justify-between gap-2">
                <div>
                    <h3 className="font-bold text-slate-900">{order.order_number || `Order #${order.id}`}</h3>
                    <p className="mt-0.5 text-xs text-slate-500">
                        {order.order_type === 'takeout' ? 'Takeout' : (order.table_number ? `Table ${order.table_number}` : 'Dine-in')}
                        {' · ' + new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </p>
                </div>
                {order.code && (
                    <span className="rounded bg-slate-100 px-2 py-1 font-mono text-sm font-bold tracking-wider text-slate-700">
                        {order.code}
                    </span>
                )}
            </div>

            <ul className="mt-3 space-y-1 border-t border-slate-100 pt-2 text-sm text-slate-700">
                {order.order_items.map((item, idx) => (
                    <li key={idx} className="flex justify-between gap-1">
                        <span>{item.quantity} × {item.menu_item?.item_name ?? 'Item'}</span>
                    </li>
                ))}
            </ul>

            <button
                onClick={handleStatusUpdate}
                disabled={isUpdating}
                className={`mt-3 w-full rounded-lg px-3 py-2 text-sm font-semibold transition-all active:scale-95 ${config.buttonStyle} ${isUpdating ? 'opacity-50 cursor-not-allowed' : ''}`}
            >
                {isUpdating ? 'Updating...' : config.buttonLabel}
            </button>
        </article>
    );
};

export default function Index({ columns }) {
    const [lastUpdated, setLastUpdated] = useState(new Date().toLocaleTimeString());

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['columns'] });
            setLastUpdated(new Date().toLocaleTimeString());
        }, 10000); // Refresh every 10 seconds

        return () => clearInterval(interval);
    }, []);

    return (
        <div className="p-6 bg-slate-50 min-h-screen">
            <Head title="Kitchen Display" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div className="flex-1">
                    <h1 className="text-2xl font-bold text-slate-900">Kitchen Display System</h1>
                    <p className="mt-1 text-sm text-slate-500">Update each ticket as the kitchen works on it.</p>
                </div>
                <div className="flex items-center gap-4">
                    <span className="text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                        Live refresh: {lastUpdated}
                    </span>
                    <button
                        onClick={() => router.post(route('logout'))}
                        className="flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-red-600 transition-all active:scale-95 shadow-sm"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                        Logout
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                {Object.entries(columns).map(([status, orders]) => (
                    <section key={status} className="flex min-h-64 flex-col rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <header className="flex items-center justify-between border-b border-slate-200 px-4 py-3 bg-white">
                            <h2 className="flex items-center gap-2 font-semibold text-slate-800">
                                <span className={`h-2.5 w-2.5 rounded-full ${STATUS_CONFIG[status].dot}`}></span>
                                {STATUS_CONFIG[status].label}
                            </h2>
                            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                {orders.length}
                            </span>
                        </header>
                        <div className="flex-1 space-y-4 p-4 bg-slate-50/50">
                            {orders.length > 0 ? (
                                orders.map(order => (
                                    <KdsTicket key={order.id} order={order} status={status} />
                                ))
                            ) : (
                                <p className="py-12 text-center text-sm text-slate-400 italic">
                                    No orders in this stage.
                                </p>
                            )}
                        </div>
                    </section>
                ))}
            </div>
        </div>
    );
}
