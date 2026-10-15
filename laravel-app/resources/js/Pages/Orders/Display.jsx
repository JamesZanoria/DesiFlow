import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';

export default function Display() {
    const [orders, setOrders] = useState([]);
    const [lastUpdated, setLastUpdated] = useState(new Date().toLocaleTimeString());

    const fetchOrders = async () => {
        try {
            const response = await fetch(route('display.orders'));
            const data = await response.json();
            setOrders(data.orders);
            setLastUpdated(new Date().toLocaleTimeString());
        } catch (error) {
            console.error('Error fetching orders:', error);
        }
    };

    useEffect(() => {
        fetchOrders();
        const interval = setInterval(fetchOrders, 10000);
        return () => clearInterval(interval);
    }, []);

    return (
        <div className="min-h-screen bg-slate-950 text-white p-8 font-sans">
            <Head title="Order Status Board" />

            <div className="flex justify-between items-center mb-12">
                <div>
                    <h1 className="text-5xl font-black tracking-tighter text-white">
                        PICKUP <span className="text-amber-500">STATUS</span>
                    </h1>
                    <p className="text-slate-400 mt-2 font-medium uppercase tracking-widest text-sm">
                        Real-time Order Fulfillment Board
                    </p>
                </div>
                <div className="text-right">
                    <div className="text-xs text-slate-500 uppercase font-bold mb-1">Last Sync</div>
                    <div className="text-sm font-mono text-slate-300">{lastUpdated}</div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                {orders.length > 0 ? (
                    orders.map((order, idx) => (
                        <div
                            key={idx}
                            className={`relative overflow-hidden rounded-3xl border-2 p-6 transition-all duration-500 animate-in fade-in slide-in-from-bottom-4 ${
                                order.status === 'ready'
                                    ? 'bg-green-500/10 border-green-500 shadow-[0_0_20px_rgba(34,197,94,0.2)]'
                                    : 'bg-slate-900 border-slate-800 shadow-xl'
                            }`}
                        >
                            {order.status === 'ready' && (
                                <div className="absolute top-0 right-0 bg-green-500 text-white text-[10px] font-black px-3 py-1 rounded-bl-xl uppercase">
                                    Ready
                                </div>
                            )}

                            <div className="flex flex-col items-center text-center">
                                <div className={`text-xs font-bold uppercase tracking-widest mb-2 ${
                                    order.status === 'ready' ? 'text-green-400' : 'text-slate-500'
                                }`}>
                                    Order Code
                                </div>
                                <div className={`text-6xl font-black tracking-tighter mb-4 ${
                                    order.status === 'ready' ? 'text-green-400' : 'text-white'
                                }`}>
                                    {order.code}
                                </div>
                                <div className={`px-4 py-2 rounded-full text-sm font-bold uppercase tracking-wider ${
                                    order.status === 'ready'
                                        ? 'bg-green-500 text-white'
                                        : 'bg-slate-800 text-slate-400'
                                }`}>
                                    {order.status === 'ready' ? 'Ready for Pickup' : 'Preparing'}
                                </div>
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="col-span-full py-24 text-center">
                        <div className="text-slate-600 text-xl font-medium italic">
                            No orders currently in queue.
                        </div>
                    </div>
                )}
            </div>

            <style dangerouslySetInnerHTML={{ __html: `
                @keyframes pulse-slow {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
                }
                .animate-pulse-slow {
                    animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
            `}} />
        </div>
    );
}
