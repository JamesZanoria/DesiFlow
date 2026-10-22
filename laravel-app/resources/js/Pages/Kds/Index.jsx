import React, { useCallback, useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';

const STATUS_CONFIG = {
    pending: {
        label: 'New',
        description: 'Orders waiting to be started',
        dot: 'bg-blue-500',
        badge: 'bg-blue-50 text-blue-700',
        border: 'border-blue-500',
        nextStatus: 'preparing',
        buttonLabel: 'Start preparing',
        buttonStyle: 'bg-amber-500 text-white hover:bg-amber-400 focus:ring-amber-200',
    },
    preparing: {
        label: 'Preparing',
        description: 'Orders being prepared',
        dot: 'bg-amber-500',
        badge: 'bg-amber-50 text-amber-700',
        border: 'border-amber-500',
        nextStatus: 'ready',
        buttonLabel: 'Mark ready',
        buttonStyle: 'bg-amber-500 text-white hover:bg-amber-400 focus:ring-amber-200',
    },
    ready: {
        label: 'Ready',
        description: 'Orders ready to serve',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-50 text-emerald-700',
        border: 'border-emerald-500',
        nextStatus: 'preparing',
        buttonLabel: 'Reopen ticket',
        buttonStyle: 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-200',
    },
};

function KdsTicket({ order, status, statusUrlTemplate, onError }) {
    const config = STATUS_CONFIG[status];
    const [isUpdating, setIsUpdating] = useState(false);

    const handleStatusUpdate = async () => {
        setIsUpdating(true);
        onError('');

        try {
            const statusUrl = statusUrlTemplate.replace('__ORDER_ID__', encodeURIComponent(order.order_id));
            const response = await fetch(statusUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ status: config.nextStatus }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Could not update order status.');
            }

            router.reload({ only: ['columns'] });
        } catch (error) {
            onError(error.message || 'Could not update order status.');
        } finally {
            setIsUpdating(false);
        }
    };

    return (
        <article className={`overflow-hidden rounded-xl border border-slate-200 border-l-[6px] ${config.border} bg-white shadow-sm transition hover:shadow-md`}>
            <div className="p-4 sm:p-5">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h3 className="truncate text-lg font-extrabold text-slate-900">{order.order_number || `Order #${order.order_id}`}</h3>
                        <p className="mt-1 text-sm font-medium text-slate-600">
                            {order.order_type === 'takeout' ? 'Takeout' : (order.table_number ? `Table ${order.table_number}` : 'Dine-in')}
                            {' · ' + new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </p>
                    </div>
                    {order.code && (
                        <span className="shrink-0 rounded-lg bg-amber-50 px-3 py-2 font-mono text-lg font-extrabold tracking-wider text-amber-800">
                            {order.code}
                        </span>
                    )}
                </div>

                <ul className="mt-4 space-y-3 border-t border-slate-100 pt-4 text-base text-slate-800">
                    {order.order_items.map((item) => (
                        <li key={item.order_item_id || `${item.menu_item_id}-${item.quantity}`}>
                            <div className="flex justify-between gap-3">
                                <span><span className="font-extrabold text-slate-950">{item.quantity} ×</span> {item.item_name_snapshot || item.menu_item?.item_name || 'Item'}</span>
                            </div>
                            {item.add_ons?.map((addOn) => (
                                <span key={addOn.order_item_add_on_id || addOn.add_on_name_snapshot} className="mt-1 block pl-6 text-sm font-medium text-slate-600">
                                    + {addOn.add_on_name_snapshot}
                                </span>
                            ))}
                        </li>
                    ))}
                </ul>

                <button
                    type="button"
                    onClick={handleStatusUpdate}
                    disabled={isUpdating}
                    className={`mt-5 w-full rounded-lg px-4 py-3 text-base font-extrabold transition focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-60 ${config.buttonStyle}`}
                >
                    {isUpdating ? 'Updating…' : config.buttonLabel}
                </button>
            </div>
        </article>
    );
}

export default function Index({ columns, logoutUrl, statusUrlTemplate }) {
    const [lastUpdated, setLastUpdated] = useState(new Date());
    const [error, setError] = useState('');
    const totalOrders = Object.values(columns).reduce((total, orders) => total + orders.length, 0);

    const refreshBoard = useCallback(() => {
        router.reload({
            only: ['columns'],
            onSuccess: () => {
                setLastUpdated(new Date());
                setError('');
            },
            onError: () => setError('Could not refresh the kitchen board. Please try again.'),
        });
    }, []);

    useEffect(() => {
        const interval = window.setInterval(refreshBoard, 10000);
        return () => window.clearInterval(interval);
    }, [refreshBoard]);

    return (
        <div className="flex min-h-screen flex-col overflow-x-hidden bg-slate-100 font-sans text-slate-900 xl:h-screen xl:min-h-0 xl:overflow-hidden">
            <Head title="Kitchen Display" />

            <header className="shrink-0 border-b border-slate-800 bg-slate-900 text-slate-200 shadow-lg shadow-slate-900/10">
                <div className="flex min-h-16 items-center justify-between gap-4 px-4 py-2 sm:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-xs font-extrabold text-white shadow-lg shadow-amber-900/20">DZ</span>
                        <div className="min-w-0">
                            <p className="truncate text-lg font-extrabold text-white sm:text-xl">Kitchen Display</p>
                            <p className="hidden text-xs font-medium text-slate-400 sm:block">Desi Zaika · {totalOrders} active {totalOrders === 1 ? 'order' : 'orders'}</p>
                        </div>
                    </div>
                    <form method="POST" action={logoutUrl}>
                        <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]').content} />
                        <button
                            type="submit"
                            className="rounded-lg border border-slate-700 px-3 py-2 text-sm font-bold text-slate-200 transition hover:border-red-400/50 hover:bg-red-500/10 hover:text-red-300"
                        >
                            Sign out
                        </button>
                    </form>
                </div>
            </header>

            <main className="flex w-full flex-1 flex-col px-3 py-3 sm:px-5 sm:py-4 xl:min-h-0">
                <div className="mb-3 flex shrink-0 flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <span className="font-bold text-slate-800">
                            {new Date().toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' })}
                        </span>
                        <span className="text-slate-500">
                            Updated {lastUpdated.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
                        </span>
                        <span className="font-semibold text-slate-700">
                            {totalOrders} active {totalOrders === 1 ? 'order' : 'orders'}
                        </span>
                    </div>
                    <button
                        type="button"
                        onClick={refreshBoard}
                        className="rounded-lg bg-amber-500 px-4 py-2 text-sm font-extrabold text-white shadow-sm transition hover:bg-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-200"
                    >
                        Refresh board
                    </button>
                </div>

                {error && (
                    <div role="alert" className="mb-3 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                        {error}
                    </div>
                )}

                <div className="grid flex-1 grid-cols-1 gap-3 md:grid-cols-2 xl:min-h-0 xl:grid-cols-3">
                    {Object.entries(STATUS_CONFIG).map(([status, config]) => {
                        const orders = columns[status] || [];

                        return (
                            <section key={status} className="flex min-h-[28rem] flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-md xl:min-h-0">
                                <header className="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:px-5">
                                    <div className="flex items-center gap-3">
                                        <span className={`h-3.5 w-3.5 rounded-full ${config.dot}`} />
                                        <div>
                                            <h2 className="text-lg font-extrabold text-slate-950">{config.label}</h2>
                                            <p className="mt-0.5 text-sm font-medium text-slate-600">{config.description}</p>
                                        </div>
                                    </div>
                                    <span className={`rounded-full px-3 py-1.5 text-sm font-extrabold ${config.badge}`}>
                                        {orders.length}
                                    </span>
                                </header>
                                <div className="flex-1 space-y-3 overflow-y-auto bg-slate-100 p-3 sm:p-4">
                                    {orders.length > 0 ? (
                                        orders.map((order) => (
                                            <KdsTicket
                                                key={order.order_id}
                                                order={order}
                                                status={status}
                                                statusUrlTemplate={statusUrlTemplate}
                                                onError={setError}
                                            />
                                        ))
                                    ) : (
                                        <div className="flex min-h-52 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-4 text-center">
                                            <span className="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="m9 12 2 2 4-4m6 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                            </span>
                                            <p className="text-base font-bold text-slate-700">All clear</p>
                                            <p className="mt-1 text-sm text-slate-500">No orders in this stage.</p>
                                        </div>
                                    )}
                                </div>
                            </section>
                        );
                    })}
                </div>
            </main>
        </div>
    );
}
