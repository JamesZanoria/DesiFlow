/**
 * SmartAssistant.js
 * Centralized intelligence logic and UI orchestration for the DesiFlow Hybrid Assistant.
 */

class SmartAssistant {
    constructor(options = {}) {
        this.endpoint = options.endpoint || '/admin/assistant/ask';
        this.onTyping = options.onTyping || (() => {});
        this.onResponse = options.onResponse || (() => {});
        this.onError = options.onError || (() => {});
    }

    async ask(message, contextEntity = null, source = 'studio') {
        this.onTyping(true);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    message: message,
                    context_entity: contextEntity,
                    source: source
                })
            });

            if (!response.ok) {
                throw new Error(`Server responded with ${response.status}`);
            }

            const data = await response.json();

            const result = {
                answer: data.answer,
                resolved_entity: data.resolved_entity,
                source: source,
                timestamp: new Date().toISOString()
            };

            this.onResponse(result);
            return result;

        } catch (error) {
            console.error('SmartAssistant Error:', error);
            this.onError(error.message || 'An unexpected error occurred.');
        } finally {
            this.onTyping(false);
        }
    }

    formatResponse(answer, source, maxLength = 300) {
        if (source === 'widget' && answer.length > maxLength) {
            return {
                text: answer.substring(0, maxLength) + '...',
                isTruncated: true,
                fullLink: '/admin/assistant'
            };
        }
        return {
            text: answer,
            isTruncated: false,
            fullLink: null
        };
    }
}

/**
 * UI Orchestrator for the Assistant Studio Page
 */
class AssistantUI {
    constructor(config) {
        this.config = config;
        this.currentConversationId = config.conversationId;
        this.attachedFile = null;

        this.initElements();
        this.initAssistant();
        this.bindEvents();
    }

    initElements() {
        this.messagesContainer = document.getElementById('assistant-messages');
        this.form = document.getElementById('assistant-form');
        this.input = document.getElementById('assistant-input');
        this.sendBtn = document.getElementById('assistant-send-btn');
        this.newChatBtn = document.getElementById('new-chat-btn');
        this.uploadBtn = document.getElementById('assistant-upload-btn');
        this.fileInput = document.getElementById('assistant-file-input');
    }

    initAssistant() {
        this.assistant = new SmartAssistant({
            endpoint: this.config.endpoint,
            onTyping: (isTyping) => {
                if (this.sendBtn) this.sendBtn.disabled = isTyping;
                if (isTyping) this.showTypingIndicator(); else this.hideTypingIndicator();
            },
            onResponse: (result) => {
                this.appendMessage(result.answer, 'assistant');
                if (result.conversation_id) {
                    this.currentConversationId = result.conversation_id;
                    const url = new URL(window.location);
                    url.searchParams.set('conversation_id', this.currentConversationId);
                    window.history.pushState({}, '', url);
                }
            },
            onError: (error) => {
                this.appendMessage(error, 'error');
            }
        });
    }

    bindEvents() {
        if (!this.form) return;

        this.uploadBtn?.addEventListener('click', () => this.fileInput?.click());

        this.fileInput?.addEventListener('change', () => {
            if (this.fileInput.files && this.fileInput.files[0]) {
                this.attachedFile = this.fileInput.files[0];
                this.uploadBtn?.classList.add('text-amber-600', 'bg-amber-50');
            }
        });

        this.form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = this.input.value.trim();
            if (!text && !this.attachedFile) return;

            this.appendMessage(text || "Uploaded an image", 'user');
            this.input.value = '';

            const formData = new FormData();
            formData.append('message', text);
            if (this.attachedFile) {
                formData.append('image', this.attachedFile);
            }
            formData.append('conversation_id', this.currentConversationId || '');

            if (this.sendBtn) this.sendBtn.disabled = true;
            this.showTypingIndicator();

            try {
                const response = await fetch(this.config.endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();

                this.hideTypingIndicator();
                if (this.sendBtn) this.sendBtn.disabled = false;

                if (response.ok) {
                    this.appendMessage(result.answer, 'assistant');
                    if (result.conversation_id) {
                        this.currentConversationId = result.conversation_id;
                        const url = new URL(window.location);
                        url.searchParams.set('conversation_id', this.currentConversationId);
                        window.history.pushState({}, '', url);
                    }
                } else {
                    this.appendMessage(result.answer || 'An error occurred', 'error');
                }
            } catch (error) {
                this.hideTypingIndicator();
                if (this.sendBtn) this.sendBtn.disabled = false;
                this.appendMessage('Connection error. Please try again.', 'error');
            }

            this.attachedFile = null;
            this.uploadBtn?.classList.remove('text-amber-600', 'bg-amber-50');
            if (this.fileInput) this.fileInput.value = '';
        });

        this.newChatBtn?.addEventListener('click', () => {
            window.location.href = '/admin/assistant';
        });

        // Handle suggested chips
        document.getElementById('assistant-chips')?.addEventListener('click', async (e) => {
            const chip = e.target.closest('.suggested-chip');
            if (!chip) return;
            const text = chip.querySelector('p').textContent.trim();
            this.appendMessage(text, 'user');
            await this.assistant.ask(text, this.currentConversationId, 'studio');
        });

        this.input?.focus();
    }

    appendMessage(text, sender) {
        if (!this.messagesContainer) return;

        const row = document.createElement('div');
        row.className = `flex items-start gap-6 ${sender === 'user' ? 'justify-end' : ''}`;

        if (sender === 'user') {
            let attachmentHtml = '';
            if (this.attachedFile) {
                attachmentHtml = `
                    <div class="flex items-center gap-2 p-2 mb-2 bg-slate-800/50 rounded-lg text-xs text-slate-300 border border-slate-700">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span class="truncate max-w-[150px]">${this.attachedFile.name}</span>
                    </div>`;
            }

            row.innerHTML = `
                <div class="flex flex-col items-end gap-1">
                    ${attachmentHtml}
                    <div class="bg-slate-900 text-white rounded-2xl rounded-tr-none px-5 py-3 text-sm shadow-sm max-w-[75%] leading-relaxed">
                        ${this.escapeHtml(text)}
                    </div>
                </div>
                <div class="w-9 h-9 rounded-xl bg-slate-200 text-slate-600 flex items-center justify-center shrink-0 text-[10px] font-bold shadow-sm border border-slate-300">
                    ${this.getUserInitials()}
                </div>
            `;
        } else if (sender === 'assistant') {
            row.innerHTML = `
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                </div>
                <div class="bg-slate-100 border border-slate-200 text-slate-800 rounded-2xl rounded-tl-none px-5 py-3 text-sm shadow-sm max-w-[75%] leading-relaxed">
                    ${this.escapeHtml(text)}
                </div>
            `;
        } else {
            row.innerHTML = `
                <div class="w-9 h-9 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="bg-red-50 border border-red-100 text-red-700 rounded-2xl rounded-tl-none px-5 py-3 text-sm shadow-sm max-w-[75%] leading-relaxed">
                    ${this.escapeHtml(text)}
                </div>
            `;
        }
        this.messagesContainer.appendChild(row);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    showTypingIndicator() {
        if (!this.messagesContainer) return;
        const row = document.createElement('div');
        row.id = 'typing-indicator';
        row.className = 'flex items-start gap-6';
        row.innerHTML = `
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
            </div>
            <div class="bg-slate-100 border border-slate-200 rounded-2xl rounded-tl-none px-4 py-4 flex gap-1 shadow-sm">
                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
            </div>
        `;
        this.messagesContainer.appendChild(row);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    hideTypingIndicator() {
        document.getElementById('typing-indicator')?.remove();
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    getUserInitials() {
        const userName = document.querySelector('header .text-sm.font-bold')?.textContent || 'User';
        return userName.split(' ').map(p => (p[0] || '').toUpperCase()).slice(0, 2).join('');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // We can embed config in a data attribute or a global object
    const configElement = document.getElementById('assistant-config');
    if (configElement) {
        const config = JSON.parse(configElement.dataset.config);
        new AssistantUI(config);
    }
});

window.SmartAssistant = SmartAssistant;
window.AssistantUI = AssistantUI;
