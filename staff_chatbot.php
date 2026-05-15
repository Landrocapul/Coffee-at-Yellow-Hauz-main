<?php
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    return;
}
?>
<div id="staffChatbot" class="font-sans">
    <section id="staffChatbotPanel" class="hidden fixed bottom-5 left-[96px] z-[9999] w-[360px] max-w-[calc(100vw-2rem)] bg-white border border-gray-200 rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-brand-black text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand text-brand-black flex items-center justify-center shadow-sm">
                    <i class="fa-solid fa-robot text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-bold">BrewMate AI</p>
                    <p class="text-[11px] text-white/70">Assistant for staff and admin workflows</p>
                </div>
            </div>
            <button type="button" id="staffChatbotClose" class="w-8 h-8 rounded-lg hover:bg-white/10 flex items-center justify-center" title="Close staff guide">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="staffChatbotMessages" class="h-80 overflow-y-auto p-4 space-y-3 bg-[#F8F7F3]">
            <div class="max-w-[85%] bg-white border border-gray-200 rounded-2xl rounded-tl-md px-3 py-2 text-sm text-gray-700 shadow-sm">
                Hello! ☕ I'm BrewMate AI. I can guide you step-by-step so staff and admins can use the POS smoothly ✨
            </div>
            <div class="max-w-[95%] bg-white border border-gray-200 rounded-2xl rounded-tl-md px-3 py-2 shadow-sm">
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Quick Topics</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-brand-light text-brand-black border border-brand/40">How to take order</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Dine-in table flow</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Take-out flow</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Process payment</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Apply discount</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Print receipt</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Complete ticket</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Cancel order</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Check table status</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Restock item</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Low stock alerts</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Sales report guide</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Manage users</button>
                    <button type="button" class="staff-chatbot-suggestion text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">Login help</button>
                </div>
            </div>
        </div>

        <div class="p-3 border-t border-gray-200 bg-white">
            <div id="staffChatbotTeachBox" class="hidden mb-3 rounded-xl border border-brand/40 bg-brand-light p-3">
                <p class="text-xs font-bold text-brand-black mb-2">Teach the guide this answer</p>
                <textarea id="staffChatbotTeachAnswer" rows="3" class="w-full resize-none rounded-lg border border-brand/40 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand" placeholder="Type the correct staff guidance..."></textarea>
                <div class="mt-2 flex items-center justify-end gap-2">
                    <button type="button" id="staffChatbotTeachCancel" class="px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 hover:bg-white">Cancel</button>
                    <button type="button" id="staffChatbotTeachSave" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-brand-black text-brand hover:bg-brand-dark hover:text-white">Save Lesson</button>
                </div>
            </div>
            <form id="staffChatbotForm" class="flex items-center gap-2">
                <input id="staffChatbotInput" type="text" autocomplete="off" placeholder="Ask BrewMate AI..." class="flex-1 h-10 rounded-xl border border-gray-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                <button type="submit" class="w-10 h-10 rounded-xl bg-brand-black text-brand flex items-center justify-center hover:bg-brand-dark hover:text-white transition-colors" title="Send">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </section>
</div>

<script>
(() => {
    const root = document.getElementById('staffChatbot');
    if (!root || root.dataset.ready === 'true') return;
    root.dataset.ready = 'true';

    const sidebar = document.getElementById('sidebar');
    const close = document.getElementById('staffChatbotClose');
    const panel = document.getElementById('staffChatbotPanel');
    const form = document.getElementById('staffChatbotForm');
    const input = document.getElementById('staffChatbotInput');
    const messages = document.getElementById('staffChatbotMessages');
    const teachBox = document.getElementById('staffChatbotTeachBox');
    const teachAnswer = document.getElementById('staffChatbotTeachAnswer');
    const teachCancel = document.getElementById('staffChatbotTeachCancel');
    const teachSave = document.getElementById('staffChatbotTeachSave');
    let pendingQuestion = '';

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.id = 'staffChatbotToggle';
    toggle.title = 'BrewMate AI';
    toggle.className = 'w-full flex items-center gap-3 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-2 font-medium transition-all rounded-xl';
    toggle.innerHTML = '<i class="fa-solid fa-headset w-5 text-center"></i> <span class="nav-text">BrewMate AI</span>';
    const toggleLabel = toggle.querySelector('.nav-text');

    if (sidebar) {
        const sidebarBottom = sidebar.querySelector('.space-y-4');
        const logoutLink = sidebar.querySelector('a[onclick="showLogoutModal()"]');
        if (logoutLink && logoutLink.parentElement) {
            logoutLink.parentElement.insertBefore(toggle, logoutLink);
        } else if (sidebarBottom) {
            sidebarBottom.prepend(toggle);
        } else {
            sidebar.appendChild(toggle);
        }
    } else {
        root.appendChild(toggle);
        toggle.className = 'fixed bottom-5 right-5 z-[9999] w-14 h-14 rounded-full bg-brand text-brand-black shadow-xl border border-brand-dark/20 flex items-center justify-center hover:bg-brand-dark hover:text-white transition-colors';
        toggle.innerHTML = '<i class="fa-solid fa-headset text-xl"></i>';
        panel.className = panel.className.replace('left-[96px]', 'right-5');
    }

    const updatePanelPosition = () => {
        if (!sidebar) return;
        const rect = sidebar.getBoundingClientRect();
        panel.style.left = `${Math.max(16, rect.right + 16)}px`;
    };

    const syncSidebarState = () => {
        if (!sidebar || !toggleLabel) return;
        const isCollapsed = localStorage.getItem('sidebarCollapsed') !== 'false';
        toggleLabel.classList.toggle('hidden', isCollapsed);
        toggle.classList.toggle('justify-center', isCollapsed);
    };

    syncSidebarState();

    const formatStepsReply = (text) => {
        const normalized = String(text || '').replace(/\r\n/g, '\n');
        const match = normalized.match(/^(.*?):\s*(1\..*)$/s);
        if (!match) return null;

        const title = match[1].trim();
        const stepsPart = match[2].trim();
        const rawSteps = stepsPart.split(/\n?\s*(?=\d+\.)/).map((item) => item.trim()).filter(Boolean);
        if (rawSteps.length < 2) return null;

        const steps = rawSteps.map((item) => item.replace(/^\d+\.\s*/, '').trim()).filter(Boolean);
        if (steps.length < 2) return null;

        return { title, steps };
    };

    const addMessage = (text, sender) => {
        const bubble = document.createElement('div');
        bubble.className = sender === 'user'
            ? 'ml-auto max-w-[85%] bg-brand text-brand-black rounded-2xl rounded-tr-md px-3 py-2 text-sm font-medium shadow-sm'
            : 'max-w-[85%] bg-white border border-gray-200 rounded-2xl rounded-tl-md px-3 py-2 text-sm text-gray-700 shadow-sm';
        if (sender === 'bot') {
            const structured = formatStepsReply(text);
            if (structured) {
                const title = document.createElement('p');
                title.className = 'text-xs font-bold uppercase tracking-wider text-gray-500 mb-2';
                title.textContent = structured.title;
                bubble.appendChild(title);

                const flow = document.createElement('div');
                flow.className = 'space-y-1.5';

                structured.steps.forEach((step, index) => {
                    const stepBox = document.createElement('div');
                    stepBox.className = 'rounded-lg border border-gray-200 bg-[#F8F7F3] px-2.5 py-2 text-xs text-gray-700';
                    stepBox.textContent = `${index + 1}. ${step}`;
                    flow.appendChild(stepBox);

                    if (index < structured.steps.length - 1) {
                        const arrow = document.createElement('div');
                        arrow.className = 'text-center text-[10px] text-gray-400 font-bold';
                        arrow.textContent = '↓';
                        flow.appendChild(arrow);
                    }
                });

                bubble.appendChild(flow);
            } else {
                bubble.textContent = text;
            }
        } else {
            bubble.textContent = text;
        }
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    };

    const askBot = async (message) => {
        const cleanMessage = message.trim();
        if (!cleanMessage) return;

        teachBox.classList.add('hidden');
        addMessage(cleanMessage, 'user');
        input.value = '';
        input.disabled = true;
        const loading = addMessage('Checking the staff guide...', 'bot');

        try {
            const response = await fetch('api.php?action=staff_chatbot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: cleanMessage })
            });
            const result = await response.json();
            loading.remove();
            addMessage(result.success ? result.reply : (result.error || 'The staff guide is unavailable right now.'), 'bot');
            if (result.success && result.needs_training) {
                pendingQuestion = cleanMessage;
                teachAnswer.value = '';
                teachBox.classList.remove('hidden');
            }
        } catch (error) {
            loading.remove();
            addMessage('The staff guide is unavailable right now.', 'bot');
        } finally {
            input.disabled = false;
            input.focus();
        }
    };

    toggle.addEventListener('click', () => {
        updatePanelPosition();
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) input.focus();
    });
    window.addEventListener('resize', updatePanelPosition);
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        setTimeout(() => {
            syncSidebarState();
            updatePanelPosition();
        }, 0);
    });
    close.addEventListener('click', () => panel.classList.add('hidden'));
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        askBot(input.value);
    });
    teachCancel.addEventListener('click', () => {
        teachBox.classList.add('hidden');
        teachAnswer.value = '';
        pendingQuestion = '';
    });
    teachSave.addEventListener('click', async () => {
        const answer = teachAnswer.value.trim();
        if (!pendingQuestion || !answer) return;

        teachSave.disabled = true;
        teachSave.textContent = 'Saving...';
        try {
            const response = await fetch('api.php?action=staff_chatbot_learn', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: pendingQuestion, answer })
            });
            const result = await response.json();
            addMessage(result.success ? result.reply : (result.error || 'I could not save that lesson.'), 'bot');
            if (result.success) {
                teachBox.classList.add('hidden');
                teachAnswer.value = '';
                pendingQuestion = '';
            }
        } catch (error) {
            addMessage('I could not save that lesson right now.', 'bot');
        } finally {
            teachSave.disabled = false;
            teachSave.textContent = 'Save Lesson';
            input.focus();
        }
    });
    document.querySelectorAll('.staff-chatbot-suggestion').forEach((button) => {
        button.addEventListener('click', () => askBot(button.textContent));
    });
})();
</script>



