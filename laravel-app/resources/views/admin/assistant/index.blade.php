@extends('layouts.app')

@section('title', 'Smart Assistant')

@section('content')

    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Smart Assistant</h1>
        <p class="text-sm text-gray-500 mt-1">Ask questions about your business in plain language, answers are sourced only from your own recorded data.</p>
    </div>

    <div class="bg-white border rounded-xl shadow-sm flex flex-col h-[32rem] max-w-3xl">

        {{-- Message thread, session-only (resets on reload). There's no
             chat-history table in the schema, only asks for a
             running thread "within the current session," so this is kept
             entirely in the DOM/JS rather than persisted server-side. --}}
        <div id="assistant-messages" class="flex-1 overflow-y-auto p-5 space-y-4">
            <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm text-gray-700 max-w-[80%]">
                    Hi! I can answer questions about Desi Zaika's own recorded data, things like current stock, today's earnings, dish performance, or what needs reordering. What would you like to know?
                </div>
            </div>
        </div>

        {{-- Suggested question chips, kept visible above the input for the
             whole session (not just on first load), Each chip's wording deliberately contains the exact
             keyword SmartAssistantController::matchQuestion() checks for
             (e.g. "best selling", "reorder") so clicking one is guaranteed to
             route the same way typing it would. --}}
        <div class="px-5 pt-3 border-t">
            <div class="flex flex-wrap gap-2" id="assistant-chips">
                <button type="button" class="suggested-chip text-xs bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1.5 rounded-full hover:bg-amber-100">What's my current stock level?</button>
                <button type="button" class="suggested-chip text-xs bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1.5 rounded-full hover:bg-amber-100">What are today's earnings?</button>
                <button type="button" class="suggested-chip text-xs bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1.5 rounded-full hover:bg-amber-100">What is my best selling dish today?</button>
                <button type="button" class="suggested-chip text-xs bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1.5 rounded-full hover:bg-amber-100">What do I need to reorder?</button>
            </div>
        </div>

        {{-- Input --}}
        <form id="assistant-form" class="flex items-center gap-2 p-4 border-t">
            <input type="text" id="assistant-input" maxlength="255" autocomplete="off"
                placeholder="Ask about stock, earnings, dish performance, or reordering..."
                class="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
            <button type="submit" id="assistant-send-btn"
                class="flex items-center justify-center w-10 h-10 rounded-lg bg-amber-500 text-white hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
            </button>
        </form>
    </div>

    <script>
        (function () {
            const messages = document.getElementById('assistant-messages');
            const form = document.getElementById('assistant-form');
            const input = document.getElementById('assistant-input');
            const sendBtn = document.getElementById('assistant-send-btn');
            const askUrl = '{{ route('admin.assistant.ask') }}';

            const userIcon = `
                <div class="w-7 h-7 rounded-full bg-gray-700 text-white flex items-center justify-center flex-shrink-0 text-xs font-semibold">AU</div>
            `;
            const assistantIcon = `
                <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                </div>
            `;

            function scrollToBottom() {
                messages.scrollTop = messages.scrollHeight;
            }

            function appendUserMessage(text) {
                const row = document.createElement('div');
                row.className = 'flex items-start gap-2.5 justify-end';
                row.innerHTML = `
                    <div class="bg-amber-500 text-white rounded-2xl rounded-tr-sm px-4 py-2.5 text-sm max-w-[80%]">${escapeHtml(text)}</div>
                    ${userIcon}
                `;
                messages.appendChild(row);
                scrollToBottom();
            }

            function appendAssistantMessage(text) {
                const row = document.createElement('div');
                row.className = 'flex items-start gap-2.5';
                row.innerHTML = `
                    ${assistantIcon}
                    <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm text-gray-700 max-w-[80%]">${escapeHtml(text)}</div>
                `;
                messages.appendChild(row);
                scrollToBottom();
            }

            function appendErrorMessage(text) {
                const row = document.createElement('div');
                row.className = 'flex items-start gap-2.5';
                row.innerHTML = `
                    ${assistantIcon}
                    <div class="bg-red-50 text-red-700 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm max-w-[80%]">${escapeHtml(text)}</div>
                `;
                messages.appendChild(row);
                scrollToBottom();
            }

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            let typingRow = null;
            function showTyping() {
                typingRow = document.createElement('div');
                typingRow.className = 'flex items-start gap-2.5';
                typingRow.innerHTML = `
                    ${assistantIcon}
                    <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                    </div>
                `;
                messages.appendChild(typingRow);
                scrollToBottom();
            }
            function hideTyping() {
                typingRow?.remove();
                typingRow = null;
            }

            // Deliberately a standalone fetch call rather than the shared
            // submitAjaxForm()/submitAjaxAction() helpers in admin.js, those
            // assume a {success, message}-style response used for CRUD
            // actions, whereas this endpoint returns {question, answer} and
            // isn't mutating any record, so wrapping it into that contract
            // would cost more than it'd save.
            async function askQuestion(question) {
                appendUserMessage(question);
                input.value = '';
                sendBtn.disabled = true;
                showTyping();

                try {
                    const response = await fetch(askUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ question }),
                    });

                    hideTyping();

                    if (!response.ok) {
                        const data = await response.json().catch(() => null);
                        const message = data?.errors?.question?.[0] || 'Something went wrong on my end. Please try rephrasing your question.';
                        appendErrorMessage(message);
                        return;
                    }

                    const data = await response.json();
                    appendAssistantMessage(data.answer);
                } catch (err) {
                    hideTyping();
                    console.error('Smart Assistant request failed', err);
                    appendErrorMessage("I couldn't reach the server just now. Please check your connection and try again.");
                } finally {
                    sendBtn.disabled = false;
                    input.focus();
                }
            }

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const question = input.value.trim();
                if (!question) return;
                askQuestion(question);
            });

            document.getElementById('assistant-chips').addEventListener('click', (e) => {
                const chip = e.target.closest('.suggested-chip');
                if (!chip) return;
                askQuestion(chip.textContent.trim());
            });

            input.focus();
        })();
    </script>

@endsection