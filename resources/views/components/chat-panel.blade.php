<style>
    #chat-fab {
        position: fixed; bottom: 28px; right: 28px; z-index: 1000;
        width: 52px; height: 52px; border-radius: 50%;
        background: #6366f1; color: #fff; border: none; cursor: pointer;
        font-size: 1.4rem; line-height: 1;
        box-shadow: 0 4px 16px rgba(99,102,241,.45);
        transition: background .15s, transform .15s;
        display: none; align-items: center; justify-content: center;
    }
    #chat-fab:hover { background: #4f46e5; transform: scale(1.07); }

    #chat-panel {
        position: fixed; bottom: 92px; right: 28px; z-index: 999;
        width: 440px; height: 600px;
        background: #fff; border-radius: 14px;
        box-shadow: 0 8px 40px rgba(0,0,0,.18);
        display: none; flex-direction: column; overflow: hidden;
    }

    #chat-head {
        background: #1a1a2e; color: #fff;
        padding: 11px 16px;
        display: flex; justify-content: space-between; align-items: center;
        flex-shrink: 0;
    }
    #chat-head-title { font-weight: 600; font-size: .92rem; }
    #chat-head-meta { font-size: .72rem; color: #94a3b8; margin-top: 2px; display: flex; gap: 8px; }
    #chat-head-model { background: #ffffff18; padding: 1px 6px; border-radius: 4px; font-family: monospace; }
    #chat-close-btn {
        background: none; border: none; color: #94a3b8; font-size: 1.3rem;
        cursor: pointer; line-height: 1; padding: 0 2px; flex-shrink: 0;
    }
    #chat-close-btn:hover { color: #fff; }

    #chat-messages {
        flex: 1; overflow-y: auto; padding: 14px 14px 8px;
        display: flex; flex-direction: column; gap: 10px;
    }

    .chat-bubble {
        max-width: 86%; padding: 9px 13px; border-radius: 14px;
        font-size: .875rem; line-height: 1.6; word-break: break-word;
    }
    .chat-bubble-user {
        align-self: flex-end;
        background: #6366f1; color: #fff;
        border-bottom-right-radius: 4px;
        white-space: pre-wrap;
    }
    .chat-bubble-assistant {
        align-self: flex-start;
        background: #f1f5f9; color: #1e293b;
        border-bottom-left-radius: 4px;
    }
    /* markdown inside assistant bubble */
    .chat-bubble-assistant p { margin: 0 0 .5em; }
    .chat-bubble-assistant p:last-child { margin-bottom: 0; }
    .chat-bubble-assistant strong { font-weight: 600; }
    .chat-bubble-assistant em { font-style: italic; }
    .chat-bubble-assistant h1,.chat-bubble-assistant h2,.chat-bubble-assistant h3 {
        font-size: .92rem; font-weight: 700; margin: .6em 0 .25em;
    }
    .chat-bubble-assistant code {
        background: #e2e8f0; padding: 1px 5px; border-radius: 3px;
        font-family: monospace; font-size: .82rem;
    }
    .chat-bubble-assistant pre {
        background: #1e293b; color: #e2e8f0; border-radius: 6px;
        padding: 10px 12px; overflow-x: auto; margin: .5em 0;
    }
    .chat-bubble-assistant pre code {
        background: none; padding: 0; font-size: .8rem; color: inherit;
    }
    .chat-bubble-assistant ul,.chat-bubble-assistant ol {
        padding-left: 1.25em; margin: .4em 0;
    }
    .chat-bubble-assistant li { margin-bottom: .2em; }
    .chat-bubble-assistant blockquote {
        border-left: 3px solid #cbd5e1; padding-left: .75em;
        color: #64748b; margin: .4em 0;
    }
    .chat-bubble-error {
        align-self: flex-start;
        background: #fee2e2; color: #991b1b;
        border-bottom-left-radius: 4px;
    }

    .chat-status-row {
        align-self: center;
        display: flex; align-items: center; gap: 7px;
        color: #94a3b8; font-size: .78rem; padding: 2px 0;
    }
    @keyframes chat-spin { to { transform: rotate(360deg); } }
    .chat-spinner {
        width: 13px; height: 13px; flex-shrink: 0;
        border: 2px solid #e2e8f0; border-top-color: #6366f1;
        border-radius: 50%;
        animation: chat-spin .7s linear infinite;
    }

    .chat-intro {
        align-self: center; text-align: center;
        color: #94a3b8; font-size: .82rem; padding: 20px 12px; line-height: 1.5;
    }

    #chat-footer {
        padding: 10px 12px 12px;
        border-top: 1px solid #f1f5f9;
        display: flex; gap: 8px; align-items: flex-end;
        flex-shrink: 0;
    }
    #chat-input {
        flex: 1; resize: none; font-size: .875rem; font-family: inherit;
        border: 1px solid #cbd5e1; border-radius: 8px;
        padding: 8px 10px; line-height: 1.4; max-height: 110px;
        outline: none; transition: border-color .15s;
    }
    #chat-input:focus { border-color: #6366f1; }
    #chat-send {
        flex-shrink: 0; background: #6366f1; color: #fff; border: none;
        border-radius: 8px; padding: 8px 14px; font-size: .85rem;
        cursor: pointer; font-family: inherit; align-self: flex-end;
        transition: background .15s;
    }
    #chat-send:hover { background: #4f46e5; }
    #chat-send:disabled, #chat-input:disabled { opacity: .55; cursor: not-allowed; }
</style>

<button id="chat-fab" title="Open AI Chat" aria-label="Open AI Chat">&#128172;</button>

<div id="chat-panel" role="dialog" aria-label="AI Chat">
    <div id="chat-head">
        <div>
            <div id="chat-head-title">AI Chat</div>
            <div id="chat-head-meta">
                <span id="chat-head-sub"></span>
                <span id="chat-head-model"></span>
            </div>
        </div>
        <button id="chat-close-btn" aria-label="Close chat">&times;</button>
    </div>
    <div id="chat-messages"></div>
    <div id="chat-footer">
        <textarea id="chat-input" rows="2" placeholder="Ask about test results, scenarios, defects… (Enter to send)"></textarea>
        <button id="chat-send">Send</button>
    </div>
</div>

<script>
(function () {
    var ctx    = window.ChatContext;
    var models = window.OllamaModels || {};
    if (!ctx) return;

    var fab      = document.getElementById('chat-fab');
    var panel    = document.getElementById('chat-panel');
    var messages = document.getElementById('chat-messages');
    var input    = document.getElementById('chat-input');
    var sendBtn  = document.getElementById('chat-send');
    var headSub  = document.getElementById('chat-head-sub');
    var headModel= document.getElementById('chat-head-model');
    var closeBtn = document.getElementById('chat-close-btn');

    var history      = [];
    var isProcessing = false;
    var introduced   = false;

    headSub.textContent   = ctx.label;
    headModel.textContent = models.smart || '';
    fab.style.display     = 'flex';

    fab.addEventListener('click', togglePanel);
    closeBtn.addEventListener('click', function () { panel.style.display = 'none'; });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.style.display === 'flex') {
            panel.style.display = 'none';
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    sendBtn.addEventListener('click', sendMessage);

    // ── Panel toggle ──────────────────────────────────────────────────────────

    function togglePanel() {
        var open = panel.style.display === 'flex';
        panel.style.display = open ? 'none' : 'flex';
        if (!open) {
            if (!introduced) { introduced = true; appendIntro(); }
            input.focus();
        }
    }

    function appendIntro() {
        var div = document.createElement('div');
        div.className   = 'chat-intro';
        div.textContent = 'Ask me anything about ' + ctx.label
            + '. I have access to all test results, scenarios, and acceptance criteria for this context.';
        messages.appendChild(div);
    }

    // ── Send / stream ─────────────────────────────────────────────────────────

    async function sendMessage() {
        var prompt = input.value.trim();
        if (!prompt || isProcessing) return;

        isProcessing = true;
        input.value  = '';
        setInputsEnabled(false);

        appendBubble('user', prompt, false);

        var statusRow   = appendStatusRow('Validating prompt…');
        var assistantEl = null;
        var rawText     = '';
        var statusGone  = false;

        function removeStatus() {
            if (!statusGone && statusRow.parentNode) {
                statusRow.parentNode.removeChild(statusRow);
                statusGone = true;
            }
        }

        try {
            var response = await fetch('/chat/stream', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                    'Accept':       'application/x-ndjson',
                },
                body: JSON.stringify({
                    prompt:       prompt,
                    context_type: ctx.type,
                    context_id:   ctx.id,
                    history:      history.slice(-20),
                }),
            });

            var reader  = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer  = '';

            while (true) {
                var chunk = await reader.read();
                if (chunk.done) break;

                buffer += decoder.decode(chunk.value, { stream: true });

                var nl;
                while ((nl = buffer.indexOf('\n')) !== -1) {
                    var line = buffer.slice(0, nl).trim();
                    buffer   = buffer.slice(nl + 1);
                    if (!line) continue;

                    var data;
                    try { data = JSON.parse(line); } catch (_) { continue; }

                    if (data.type === 'status') {
                        var span = statusRow.querySelector('.chat-status-text');
                        if (span) span.textContent = data.text;

                    } else if (data.type === 'token') {
                        removeStatus();
                        if (!assistantEl) {
                            assistantEl = document.createElement('div');
                            assistantEl.className = 'chat-bubble chat-bubble-assistant';
                            messages.appendChild(assistantEl);
                        }
                        rawText += data.text;
                        // Show raw text during streaming for performance
                        assistantEl.textContent = rawText;
                        scrollBottom();

                    } else if (data.type === 'error') {
                        removeStatus();
                        if (!assistantEl) {
                            assistantEl = document.createElement('div');
                            assistantEl.className = 'chat-bubble chat-bubble-error';
                            messages.appendChild(assistantEl);
                        }
                        assistantEl.className  = 'chat-bubble chat-bubble-error';
                        assistantEl.textContent = data.text;
                        rawText = '';
                        scrollBottom();

                    } else if (data.type === 'done') {
                        removeStatus();
                        // Render markdown now that streaming is complete
                        if (assistantEl && rawText) {
                            assistantEl.innerHTML = renderMarkdown(rawText);
                        }
                        scrollBottom();
                    }
                }
            }

            if (rawText) {
                history.push({ role: 'user',      content: prompt  });
                history.push({ role: 'assistant',  content: rawText });
            }
        } catch (_) {
            removeStatus();
            var errEl       = document.createElement('div');
            errEl.className = 'chat-bubble chat-bubble-error';
            errEl.textContent = 'Connection error. Please try again.';
            messages.appendChild(errEl);
            scrollBottom();
        } finally {
            isProcessing = false;
            setInputsEnabled(true);
            input.focus();
        }
    }

    // ── DOM helpers ───────────────────────────────────────────────────────────

    function appendBubble(role, text, isHtml) {
        var div = document.createElement('div');
        div.className = 'chat-bubble chat-bubble-' + role;
        if (isHtml) { div.innerHTML  = text; }
        else        { div.textContent = text; }
        messages.appendChild(div);
        scrollBottom();
        return div;
    }

    function appendStatusRow(text) {
        var div = document.createElement('div');
        div.className = 'chat-status-row';
        div.innerHTML = '<div class="chat-spinner"></div><span class="chat-status-text">' + escHtml(text) + '</span>';
        messages.appendChild(div);
        scrollBottom();
        return div;
    }

    function scrollBottom() { messages.scrollTop = messages.scrollHeight; }

    function setInputsEnabled(on) {
        input.disabled   = !on;
        sendBtn.disabled = !on;
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Markdown renderer ─────────────────────────────────────────────────────

    function renderMarkdown(raw) {
        // Work on a copy; escape HTML first so injected content is safe
        var s = raw;

        // Fenced code blocks  ```lang\n...\n```
        s = s.replace(/```[\w]*\n?([\s\S]*?)```/g, function (_, code) {
            return '<pre><code>' + escHtml(code.replace(/^\n|\n$/g,'')) + '</code></pre>';
        });

        // Split on block-level elements already replaced so inline rules don't touch them
        var parts = s.split(/(<pre>[\s\S]*?<\/pre>)/g);
        parts = parts.map(function (part) {
            if (part.indexOf('<pre>') === 0) return part;   // leave code blocks alone

            // Inline code  `...`
            part = part.replace(/`([^`\n]+)`/g, function (_, c) { return '<code>' + escHtml(c) + '</code>'; });

            // Headings  ###, ##, #
            part = part.replace(/^######\s+(.+)$/gm, '<h3>$1</h3>');
            part = part.replace(/^#####\s+(.+)$/gm,  '<h3>$1</h3>');
            part = part.replace(/^####\s+(.+)$/gm,   '<h3>$1</h3>');
            part = part.replace(/^###\s+(.+)$/gm,    '<h3>$1</h3>');
            part = part.replace(/^##\s+(.+)$/gm,     '<h3>$1</h3>');
            part = part.replace(/^#\s+(.+)$/gm,      '<h3>$1</h3>');

            // Bold + italic  ***...***
            part = part.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
            // Bold  **...**  or __...__
            part = part.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            part = part.replace(/__(.+?)__/g,      '<strong>$1</strong>');
            // Italic  *...*  or _..._
            part = part.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
            part = part.replace(/_([^_\n]+)_/g,   '<em>$1</em>');

            // Blockquote  > ...
            part = part.replace(/^&gt;\s*(.+)$/gm, '<blockquote>$1</blockquote>');

            // Unordered lists  -  or  *
            part = part.replace(/^[\-\*]\s+(.+)$/gm, '<li>$1</li>');
            part = part.replace(/(<li>[\s\S]*?<\/li>)(\n(?=<li>)|$)/g, '$1');
            part = part.replace(/(<li>.*<\/li>\n?)+/g, function (m) { return '<ul>' + m + '</ul>'; });

            // Ordered lists  1.  2.  etc.
            part = part.replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>');
            part = part.replace(/(<li>.*<\/li>\n?)+/g, function (m) {
                if (m.indexOf('<ul>') !== -1) return m;
                return '<ol>' + m + '</ol>';
            });

            // Paragraphs: double newline → paragraph break; single → <br>
            part = part.replace(/\n{2,}/g, '</p><p>');
            part = part.replace(/\n/g, '<br>');

            // Wrap non-block content in <p>
            if (!/<(h[1-6]|ul|ol|blockquote|pre)/.test(part)) {
                part = '<p>' + part + '</p>';
            }

            return part;
        });

        return parts.join('');
    }
})();
</script>
