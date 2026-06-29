@extends('layouts.app')

@section('title', 'المساعد الذكي')

@section('content')
<section class="mx-auto flex h-[calc(100vh-7.5rem)] max-w-6xl flex-col assistant-surface">
    <header class="flex flex-col gap-4 border-b border-slate-200 bg-white px-5 py-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-sm">
                <i data-lucide="sparkles" class="h-5 w-5"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-slate-950">المساعد الذكي</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">مساعد أعمال ذكي لتحليل الطلبات والمنتجات والعملاء</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                متصل
            </span>
            <button id="clear-chat" type="button" class="app-icon-button" aria-label="مسح المحادثة">
                <i data-lucide="trash-2" class="h-4 w-4"></i>
            </button>
        </div>
    </header>

    <div id="chat-messages" class="flex-1 overflow-y-auto bg-[radial-gradient(circle_at_top_left,rgba(37,99,235,0.06),transparent_28rem),linear-gradient(180deg,#f8fafc,#f1f5f9)] px-4 py-6 md:px-6">
        <div id="empty-state" class="mx-auto flex h-full max-w-3xl flex-col items-center justify-center text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-white text-slate-900 shadow-sm ring-1 ring-slate-200">
                <i data-lucide="bot" class="h-7 w-7"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-950">اسأل عن متجرك</h3>
            <p class="mt-2 max-w-xl text-sm leading-7 text-slate-500">اكتب سؤالا عن المنتجات، الطلبات، المخزون، أو أفضل العملاء. الردود ستظهر بشكل منظم وقابل للقراءة.</p>

            <div class="mt-6 grid w-full gap-3 md:grid-cols-3">
                <button type="button" class="prompt-chip" data-prompt="من أكثر زبون اشترى مني؟">أفضل زبون</button>
                <button type="button" class="prompt-chip" data-prompt="أرني المنتجات التي أوشكت على النفاد">مخزون منخفض</button>
                <button type="button" class="prompt-chip" data-prompt="ما حالة الطلبات اليوم؟">حالة الطلبات</button>
            </div>
        </div>
    </div>

    <div id="assistant-error" class="hidden border-t border-rose-100 bg-rose-50 px-5 py-3 text-sm font-medium text-rose-700"></div>

    <form id="assistant-form" class="border-t border-slate-200 bg-white p-4">
        <div class="mx-auto flex max-w-4xl items-end gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-2 shadow-sm transition focus-within:border-slate-300 focus-within:bg-white focus-within:ring-4 focus-within:ring-slate-200/60">
            <textarea
                id="assistant-message"
                rows="1"
                maxlength="2000"
                class="max-h-40 min-h-[44px] flex-1 resize-none border-0 bg-transparent px-3 py-3 text-sm leading-6 text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
                placeholder="اكتب سؤالك هنا..."
                required
            ></textarea>

            <button id="send-message" type="submit" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:hover:translate-y-0" aria-label="إرسال">
                <i data-lucide="send-horizontal" class="h-5 w-5"></i>
            </button>
        </div>
    </form>
</section>
@endsection

@section('scripts')
<script>
    const chatMessages = document.getElementById('chat-messages');
    const assistantForm = document.getElementById('assistant-form');
    const assistantMessage = document.getElementById('assistant-message');
    const sendMessage = document.getElementById('send-message');
    const assistantError = document.getElementById('assistant-error');
    const clearChat = document.getElementById('clear-chat');
    const emptyState = document.getElementById('empty-state');
    let conversationId = localStorage.getItem('ai_assistant_conversation_id') || null;
    let typingNode = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatMetricText(text) {
        return escapeHtml(text).replace(/(\d[\d,]*(?:\.\d+)?\s*(?:د\.ل|LYD|دينار|طلب|طلبات|منتج|منتجات|%))/gi, '<span class="inline-flex rounded-lg bg-slate-100 px-1.5 py-0.5 font-bold text-slate-950">$1</span>');
    }

    function parseStructuredCards(text) {
        const lines = text.split(/\n+/).map(line => line.trim()).filter(Boolean);
        const cards = [];
        const metrics = [];

        const topCustomer = text.match(/(?:أفضل زبون لديك هو|أفضل عميل لديك هو|Top Customer:?|الزبون:?|العميل:?)\s*(.+?)(?:\.|\n|$)/i);
        if (topCustomer) {
            const spent = text.match(/(?:إجمالي مشترياته|إجمالي المشتريات|Total Spent)\s*[:：]?\s*([^\n.]+)/i);
            const orders = text.match(/(?:عدد طلباته|عدد الطلبات|Orders)\s*[:：]?\s*([^\n.]+)/i);

            cards.push({
                type: 'customer',
                title: topCustomer[1],
                fields: [
                    orders ? `الطلبات: ${orders[1].trim()}` : null,
                    spent ? `إجمالي المشتريات: ${spent[1].trim()}` : null
                ].filter(Boolean),
                badge: 'عميل'
            });
        }

        lines.forEach((line) => {
            const productMatch = line.match(/^[-•]?\s*(.+?)\s*(?:—|-|:)\s*(\d+)\s*(?:remaining|متبقي|باقي|قطعة)?/i);
            if (/(remaining|متبقي|باقي|المتبقية|المخزون|الكمية)/i.test(line) && productMatch) {
                cards.push({
                    type: 'product',
                    title: productMatch[1].replace(/^[-•]\s*/, ''),
                    fields: [`المتبقي: ${productMatch[2]}`],
                    badge: 'مخزون'
                });
                return;
            }

            const metricMatch = line.match(/^(.{3,40}?)[：:]\s*([\d,]+(?:\.\d+)?\s*(?:د\.ل|LYD|دينار|طلب|طلبات|منتج|منتجات|%)?)/i);
            if (metricMatch && !/(إجمالي مشترياته|عدد طلباته)/i.test(line)) {
                metrics.push({
                    label: metricMatch[1],
                    value: metricMatch[2]
                });
            }
        });

        return {
            cards: cards.slice(0, 6),
            metrics: metrics.slice(0, 3)
        };
    }

    function renderAssistantContent(text) {
        const lines = text.split(/\n/);
        const blocks = [];
        let listItems = [];
        let orderedItems = [];

        function flushLists() {
            if (listItems.length) {
                blocks.push(`<ul class="my-3 space-y-2">${listItems.map(item => `<li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span><span>${formatMetricText(item)}</span></li>`).join('')}</ul>`);
                listItems = [];
            }

            if (orderedItems.length) {
                blocks.push(`<ol class="my-3 list-decimal space-y-2 pr-5">${orderedItems.map(item => `<li>${formatMetricText(item)}</li>`).join('')}</ol>`);
                orderedItems = [];
            }
        }

        lines.forEach((line) => {
            const trimmed = line.trim();

            if (!trimmed) {
                flushLists();
                return;
            }

            const bullet = trimmed.match(/^[-•*]\s+(.+)$/);
            if (bullet) {
                orderedItems = [];
                listItems.push(bullet[1]);
                return;
            }

            const ordered = trimmed.match(/^\d+[.)]\s+(.+)$/);
            if (ordered) {
                listItems = [];
                orderedItems.push(ordered[1]);
                return;
            }

            flushLists();
            blocks.push(`<p class="my-2 leading-8">${formatMetricText(trimmed)}</p>`);
        });

        flushLists();

        const structured = parseStructuredCards(text);
        if (structured.metrics.length) {
            blocks.push(`
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    ${structured.metrics.map(metric => `
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold text-slate-500">${escapeHtml(metric.label)}</p>
                            <p class="mt-2 text-lg font-bold text-slate-950">${escapeHtml(metric.value)}</p>
                        </div>
                    `).join('')}
                </div>
            `);
        }

        if (structured.cards.length) {
            blocks.push(`
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    ${structured.cards.map(card => `
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h4 class="truncate text-sm font-bold text-slate-950">${escapeHtml(card.title)}</h4>
                                <span class="rounded-full bg-white px-2 py-1 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">${escapeHtml(card.badge)}</span>
                            </div>
                            <div class="space-y-1">
                                ${(card.fields || []).map(field => `<p class="text-sm font-semibold text-slate-600">${escapeHtml(field)}</p>`).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `);
        }

        return blocks.join('');
    }

    function currentTime() {
        return new Date().toLocaleTimeString('ar-LY', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function hideEmptyState() {
        emptyState?.classList.add('hidden');
    }

    function showError(message) {
        assistantError.textContent = message;
        assistantError.classList.remove('hidden');
    }

    function hideError() {
        assistantError.textContent = '';
        assistantError.classList.add('hidden');
    }

    function appendMessage(role, text) {
        hideEmptyState();

        const wrapper = document.createElement('div');
        wrapper.className = role === 'user'
            ? 'assistant-bubble mx-auto mb-5 flex max-w-4xl justify-end'
            : 'assistant-bubble mx-auto mb-5 flex max-w-4xl justify-start';

        const bubble = document.createElement('article');
        bubble.className = role === 'user'
            ? 'max-w-[82%] rounded-3xl rounded-tl-lg bg-slate-950 px-4 py-3 text-sm leading-7 text-white shadow-sm md:max-w-2xl'
            : 'max-w-[92%] rounded-3xl rounded-tr-lg border border-slate-200 bg-white px-4 py-3 text-sm leading-7 text-slate-700 shadow-sm md:max-w-3xl';

        const content = document.createElement('div');
        if (role === 'assistant') {
            content.className = 'assistant-response';
            content.innerHTML = renderAssistantContent(text);
        } else {
            content.className = 'whitespace-pre-line';
            content.textContent = text;
        }

        const time = document.createElement('div');
        time.className = role === 'user'
            ? 'mt-2 text-left text-[11px] font-medium text-white/60'
            : 'mt-2 text-left text-[11px] font-medium text-slate-400';
        time.textContent = currentTime();

        bubble.appendChild(content);
        bubble.appendChild(time);
        wrapper.appendChild(bubble);
        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTypingIndicator() {
        hideEmptyState();
        typingNode = document.createElement('div');
        typingNode.id = 'typing-indicator';
        typingNode.className = 'assistant-bubble mx-auto mb-5 flex max-w-4xl justify-start';
        typingNode.innerHTML = `
            <div class="rounded-3xl rounded-tr-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.2s]"></span>
                    <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.1s]"></span>
                    <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400"></span>
                </div>
            </div>
        `;
        chatMessages.appendChild(typingNode);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function removeTypingIndicator() {
        typingNode?.remove();
        typingNode = null;
    }

    function resizeTextarea() {
        assistantMessage.style.height = 'auto';
        assistantMessage.style.height = `${Math.min(assistantMessage.scrollHeight, 160)}px`;
    }

    function setLoading(isLoading) {
        sendMessage.disabled = isLoading;
        assistantMessage.disabled = isLoading;
        sendMessage.innerHTML = isLoading
            ? '<i data-lucide="loader-2" class="h-5 w-5 animate-spin"></i>'
            : '<i data-lucide="send-horizontal" class="h-5 w-5"></i>';
        lucide.createIcons();
    }

    async function submitMessage() {
        hideError();

        const message = assistantMessage.value.trim();
        if (!message) {
            showError('اكتب رسالة للمساعد أولا.');
            return;
        }

        appendMessage('user', message);
        assistantMessage.value = '';
        resizeTextarea();
        setLoading(true);
        showTypingIndicator();

        try {
            const response = await fetch('/api/ai/assistant', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                },
                body: JSON.stringify({
                    message,
                    conversation_id: conversationId,
                }),
            });

            const data = await response.json();
            removeTypingIndicator();

            if (!response.ok) {
                throw new Error(data.reply || data.message || 'تعذر تشغيل المساعد الذكي الآن.');
            }

            conversationId = data.conversation_id || conversationId;
            if (conversationId) {
                localStorage.setItem('ai_assistant_conversation_id', conversationId);
            }

            appendMessage('assistant', data.reply);
        } catch (error) {
            removeTypingIndicator();
            showError(error.message || 'حدث خطأ غير متوقع.');
        } finally {
            setLoading(false);
            assistantMessage.focus();
        }
    }

    assistantForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitMessage();
    });

    assistantMessage.addEventListener('input', resizeTextarea);

    assistantMessage.addEventListener('keydown', async (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            if (!sendMessage.disabled) {
                await submitMessage();
            }
        }
    });

    document.querySelectorAll('.prompt-chip').forEach((button) => {
        button.addEventListener('click', () => {
            assistantMessage.value = button.dataset.prompt;
            resizeTextarea();
            assistantMessage.focus();
        });
    });

    clearChat.addEventListener('click', () => {
        conversationId = null;
        localStorage.removeItem('ai_assistant_conversation_id');
        chatMessages.querySelectorAll('.assistant-bubble').forEach(node => node.remove());
        emptyState?.classList.remove('hidden');
        hideError();
        assistantMessage.value = '';
        resizeTextarea();
        assistantMessage.focus();
    });

    resizeTextarea();
    lucide.createIcons();
</script>
@endsection
