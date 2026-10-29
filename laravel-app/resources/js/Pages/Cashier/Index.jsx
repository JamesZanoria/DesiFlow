import React from 'react';
import { Head, useForm, router } from '@inertiajs/react';

const OrderCard = ({ order, type }) => {
    const { data, setData, post, processing, errors, reset } = useForm({
        reason: '',
    });

    const handleReject = (e) => {
        e.preventDefault();
        post(route('cashier.orders.reject', order.id), {
            onSuccess: () => reset('reason'),
        });
    };

    const isUnconfirmed = type === 'unapproved';

    return (
        <article className={`rounded-xl border p-4 shadow-sm bg-white ${isUnconfirmed ? 'border-amber-200' : 'border-gray-200'}`}>
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 className="font-bold text-slate-900">{order.order_number}</h3>
                    <p className="text-sm text-slate-500">
                        {order.order_type === 'takeout' ? 'Takeout / Pickup' : `Dine-in, table ${order.table_number}`}
                        {' · ' + new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </p>
                </div>
                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                    isUnconfirmed ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'
                }`}>
                    {isUnconfirmed ? 'Unconfirmed' : 'Kitchen queue'}
                </span>
            </div>

            <ul className="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm text-slate-700">
                {order.order_items.map((item, idx) => (
                    <li key={idx} className="flex justify-between gap-3">
                        <span>{item.quantity} × {item.menu_item?.item_name ?? 'Item'}</span>
                        <span>₱{parseFloat(item.total).toFixed(2)}</span>
                    </li>
                ))}
            </ul>

            <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 font-semibold text-slate-900">
                <span>Total</span>
                <span>₱{parseFloat(order.total_amount).toFixed(2)}</span>
            </div>

            {isUnconfirmed && (
                <form onSubmit={handleReject} className="mt-4 flex flex-col gap-2 sm:flex-row">
                    <label className="sr-only" htmlFor={`reason-${order.id}`}>Reason for rejection</label>
                    <input
                        id={`reason-${order.id}`}
                        name="reason"
                        value={data.reason}
                        onChange={e => setData('reason', e.target.value)}
                        maxLength={150}
                        required
                        placeholder="Reason for rejection"
                        className="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-200 focus:border-amber-500 outline-none transition-all"
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 transition-colors disabled:opacity-50"
                    >
                        {processing ? 'Rejecting...' : 'Reject'}
                    </button>
                </form>
            )}
        </article>
    );
};

export default function Index({ unapprovedOrders, pendingOrders }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
    });

    const handleApprove = (e) => {
        e.preventDefault();
        post(route('cashier.orders.approve-code'), {
            onSuccess: () => reset('code'),
        });
    };

    return (
        <div className="p-6 bg-slate-50 min-h-screen">
            <Head title="Cashier" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Cashier order queue</h1>
                    <p className="mt-1 text-sm text-slate-500">Confirm a customer's pickup code to send the order to the kitchen.</p>
                </div>
                <button
                    onClick={() => router.post(route('logout'))}
                    className="flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-red-600 transition-all active:scale-95 shadow-sm"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                    Logout
                </button>
            </div>

            <section className="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="font-semibold text-slate-800">Accept an order</h2>
                <form onSubmit={handleApprove} className="mt-3 flex flex-col gap-3 sm:flex-row">
                    <label className="sr-only" htmlFor="pickup-code">Six-digit pickup code</label>
                    <input
                        id="pickup-code"
                        name="code"
                        type="text"
                        inputMode="numeric"
                        autoComplete="off"
                        maxLength={6}
                        pattern="[0-9]{6}"
                        value={data.code}
                        onChange={e => setData('code', e.target.value)}
                        placeholder="Enter 6-digit pickup code"
                        required
                        className="w-full rounded-xl border border-slate-200 px-4 py-3 font-mono tracking-widest text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all sm:max-w-xs"
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-xl bg-amber-500 px-6 py-3 text-sm font-bold text-white hover:bg-amber-600 transition-all active:scale-95 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed shadow-lg shadow-amber-500/20"
                    >
                        {processing ? 'Accepting...' : 'Accept order'}
                    </button>
                    {errors.code && <div className="absolute text-red-500 text-xs font-bold mt-1">{errors.code}</div>}
                </form>
            </section>

            <div className="grid grid-cols-1 gap-8 xl:grid-cols-2">
                <section>
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-lg font-bold text-slate-900">Awaiting confirmation</h2>
                        <span className="text-sm font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{unapprovedOrders.length}</span>
                    </div>
                    <div className="space-y-4">
                        {unapprovedOrders.length > 0 ? (
                            unapprovedOrders.map(order => (
                                <OrderCard key={order.id} order={order} type="unapproved" />
                            ))
                        ) : (
                            <p className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400 italic">
                                No orders are waiting for confirmation.
                            </p>
                        )}
                    </div>
                </section>

                <section>
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-lg font-bold text-slate-900">Sent to kitchen</h2>
                        <span className="text-sm font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{pendingOrders.length}</span>
                    </div>
                    <div className="space-y-4">
                        {pendingOrders.length > 0 ? (
                            pendingOrders.map(order => (
                                <OrderCard key={order.id} order={order} type="pending" />
                            ))
                        ) : (
                            <p className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400 italic">
                                No orders have been accepted yet.
                            </p>
                        )}
                    </div>
                </section>
            </div>
        </div>
    );
}
