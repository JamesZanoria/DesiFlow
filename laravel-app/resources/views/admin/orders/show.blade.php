@extends('layouts.app')

@section('title', 'Order #' . $order->order_id)

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-amber-600 hover:underline">&larr; Back to Real-Time Order Monitoring</a>
    </div>

    <div class="bg-white border rounded-xl shadow-sm p-6 max-w-xl">
        @include('admin.orders._quickview')
    </div>

@endsection