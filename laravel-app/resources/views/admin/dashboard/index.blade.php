@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome to the Dashboard</h1>
        <p class="text-gray-500 max-w-md mx-auto">
            This page is currently under development by the team.
            For now, you can use the <span class="font-semibold text-amber-600">Smart Business Assistant</span> on the bottom right to get quick insights!
        </p>
    </div>

    {{-- Floating Chatbot Widget --}}
    {{-- Chat Window --}}
    <div id="chatbot-panel" class="fixed bottom-24 right-10 w-80 sm:w-96 h-[500px] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden transition-all duration-300 ease-in-out opacity-0 scale-95 origin-bottom-right z-[100]" style="position: fixed !important; right: 40px !important;">
        {{-- Header --}}
        <div class="bg-amber-500 p-4 text-white flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold leading-none">DesiFlow AI</h3>
                    <span class="text-[10px] opacity-80">Business Assistant</span>
                </div>
            </div>
            <button id="chatbot-close" class="p-1 hover:bg-white/20 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Messages --}}
        <div id="chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 text-sm">
            <div class="flex justify-start">
                <div class="bg-white border border-gray-200 text-gray-800 p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]">
                    Hi! I'm the DesiFlow Smart Assistant. I can help you with inventory levels, menu availability, daily revenue, and staff information. What would you like to know?
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="p-4 border-t bg-white">
            <form id="chatbot-form" class="flex gap-2">
                <input type="text" id="chatbot-input"
                       placeholder="Ask about stock, revenue..."
                       class="flex-1 px-3 py-2 bg-gray-100 border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-amber-500 transition-all outline-none">
                <button type="submit" class="bg-amber-500 text-white p-2 rounded-xl hover:bg-amber-600 transition-colors shadow-sm active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7" /></svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Floating Button --}}
    <button id="chatbot-toggle" class="fixed bottom-10 right-10 w-14 h-14 bg-amber-500 text-white rounded-full shadow-xl flex items-center justify-center hover:bg-amber-600 hover:scale-110 transition-all duration-200 active:scale-95 group relative z-[100]" style="position: fixed !important; right: 40px !important; bottom: 40px !important; cursor: pointer !important;">
        <svg id="chatbot-icon-open" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
        <svg id="chatbot-icon-close" class="hidden w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>

        <span class="absolute right-full mr-3 px-2 py-1 bg-gray-800 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
            Ask DesiFlow AI
        </span>
    </button>

    <script>
    (function() {
        const toggleBtn = document.getElementById('chatbot-toggle');
        const panel = document.getElementById('chatbot-panel');
        const closeBtn = document.getElementById('chatbot-close');
        const form = document.getElementById('chatbot-form');
        const input = document.getElementById('chatbot-input');
        const messagesContainer = document.getElementById('chatbot-messages');
        const iconOpen = document.getElementById('chatbot-icon-open');
        const iconClose = document.getElementById('chatbot-icon-close');

        let contextEntity = null;

        function toggleChat() {
            const isOpen = !panel.classList.contains('hidden');
            if (isOpen) {
                closeChat();
            } else {
                openChat();
            }
        }

        function openChat() {
            panel.classList.remove('hidden');
            setTimeout(() => {
                panel.classList.remove('opacity-0', 'scale-95');
                panel.classList.add('opacity-100', 'scale-100');
            }, 10);
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');
        }

        function closeChat() {
            panel.classList.remove('opacity-100', 'scale-100');
            panel.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                panel.classList.add('hidden');
            }, 300);
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
        }

        function addMessage(text, sender = 'assistant') {
            const div = document.createElement('div');
            div.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'}`;

            const bubble = document.createElement('div');
            bubble.className = sender === 'user'
                ? 'bg-amber-500 text-white p-3 rounded-2xl rounded-tr-none shadow-sm max-w-[80%] text-sm'
                : 'bg-white border border-gray-200 text-gray-800 p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[80%] text-sm';

            // Simple markdown-like formatting for bold and lists
            bubble.innerHTML = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');

            div.appendChild(bubble);
            messagesContainer.appendChild(div);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function showTyping() {
            const div = document.createElement('div');
            div.id = 'chatbot-typing';
            div.className = 'flex justify-start';
            div.innerHTML = `<div class="bg-white border border-gray-200 text-gray-400 p-3 rounded-2xl rounded-tl-none shadow-sm text-xs animate-pulse">Typing...</div>`;
            messagesContainer.appendChild(div);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        async function handleAsk(e) {
            e.preventDefault();
            const message = input.value.trim();
            if (!message) return;

            addMessage(message, 'user');
            input.value = '';
            showTyping();

            try {
                const response = await fetch('{{ route('admin.assistant.ask') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        context_entity: contextEntity
                    })
                });

                const data = await response.json();

                // Remove typing indicator
                const typing = document.getElementById('chatbot-typing');
                if (typing) typing.remove();

                if (response.ok) {
                    addMessage(data.answer, 'assistant');
                    if (data.resolved_entity) {
                        contextEntity = data.resolved_entity;
                    }
                } else {
                    addMessage(data.answer || 'Something went wrong. Please try again.', 'assistant');
                }
            } catch (err) {
                const typing = document.getElementById('chatbot-typing');
                if (typing) typing.remove();
                addMessage('Sorry, I couldn\'t connect to the server.', 'assistant');
            }
        }

        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleChat();
        });

        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeChat();
        });

        document.addEventListener('click', (e) => {
            if (!panel.classList.contains('hidden') && !panel.contains(e.target) && e.target !== toggleBtn) {
                closeChat();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
                closeChat();
            }
        });

        form.addEventListener('submit', handleAsk);
    })();
    </script>
@endsection
