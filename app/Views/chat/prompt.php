<section class="col-12 chat-prompt-section" aria-label="Enviar mensagem">
    <form id="chat-prompt-form" class="chat-prompt" action="<?= site_url('chat/messages') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="conversation_id" value="<?= (int) ($selected['id'] ?? 0) ?>">
        <label class="visually-hidden" for="chat-message">Mensagem</label>
        <input id="chat-message" name="message" type="text" maxlength="4000"
            placeholder="Digite uma mensagem..." autocomplete="off" required>
        <button type="submit" aria-label="Enviar mensagem">&gt;</button>
    </form>
</section>

<style>
.chat-page{min-height:calc(100vh - 265px);padding:16px 16px 110px}.chat-layout{display:grid;grid-template-columns:240px minmax(0,1fr);gap:14px}.chat-conversations{padding:12px;border:1px solid #1e5579;border-radius:8px;background:#041321}.chat-conversation-list{display:flex;flex-direction:column;gap:6px}.chat-conversation-item{display:flex;align-items:center;gap:5px}.chat-conversation-item .chat-conversation-link{min-width:0;flex:1}.chat-conversation-item form{flex:0 0 auto}.chat-conversation-link{padding:8px 10px;border-radius:6px;color:#8db5d1;text-decoration:none;overflow-wrap:anywhere}.chat-conversation-link:hover,.chat-conversation-link.is-active{color:#fff;background:#12344c}.chat-main{min-width:0}.chat-history{display:flex;flex-direction:column;gap:10px;margin-top:14px}.chat-message{box-sizing:border-box;width:80%;max-width:80%;padding:10px 14px;border-radius:10px;overflow-wrap:anywhere}.chat-message-user{align-self:flex-end;margin-left:auto;background:#0b4c68}.chat-message-assistant{align-self:flex-start;margin-right:auto;background:#132534}.chat-message strong{display:block;margin-bottom:4px;color:#67e9ff}.chat-message>div{white-space:pre-wrap}.chat-feedback{margin-top:12px;color:#8db5d1}.chat-feedback.is-error{color:#ff8a80}.chat-waiting{display:inline-flex;align-items:center;gap:10px}.chat-spinner{width:20px;height:20px;border:3px solid #1e5579;border-top-color:#00d9ff;border-radius:50%;animation:chat-spin .8s linear infinite}@keyframes chat-spin{to{transform:rotate(360deg)}}@media(prefers-reduced-motion:reduce){.chat-spinner{animation-duration:1.8s}}.chat-prompt-section{position:fixed;z-index:990;left:90px;right:32px;bottom:86px;width:auto;padding:8px 0;background:linear-gradient(0deg,#020811 70%,transparent)}.chat-prompt{display:flex;align-items:center;gap:10px;width:100%;margin:0;padding:8px;border:1px solid #1e5579;border-radius:8px;background:#041321;box-shadow:0 10px 30px #0008}.chat-prompt:focus-within{border-color:#00d9ff;box-shadow:0 0 0 3px #00d9ff20,0 10px 30px #0008}.chat-prompt input[type=text]{min-width:0;flex:1;border:0;outline:0;padding:10px 12px;color:#fff;background:transparent;font-size:16px}.chat-prompt input::placeholder{color:#6f94af}.chat-prompt button{width:42px;height:42px;flex:0 0 42px;border:0;border-radius:6px;color:#02101a;background:#00d9ff;font-size:25px;font-weight:700;line-height:1;transition:background .2s ease,transform .2s ease}.chat-prompt button:hover{background:#67e9ff;transform:translateX(2px)}.chat-prompt button:disabled{cursor:wait;opacity:.65;transform:none}@media(max-width:1199px){.chat-prompt-section{left:28px;right:28px}}@media(max-width:768px){.chat-page{min-height:calc(100vh - 285px);padding:12px 12px 105px}.chat-layout{grid-template-columns:1fr}.chat-conversations{max-height:220px;overflow:auto}.chat-message{width:90%;max-width:90%}.chat-prompt-section{left:12px;right:12px;bottom:78px;padding:6px 0}}
.chat-title-form .form-control{padding:.25rem .5rem}.chat-title-form .btn{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;padding:0;flex:0 0 42px}
</style>

<script>
document.getElementById('chat-prompt-form').addEventListener('submit', async function (event) {
    event.preventDefault();

    const form = event.currentTarget;
    const input = form.elements.message;
    const button = form.querySelector('button');
    const feedback = document.getElementById('chat-feedback');
    const sentMessage = input.value.trim();
    if (!sentMessage) return;
    const requestData = new FormData(form);
    const history = document.getElementById('chat-history');
    const appendMessage = function (role, content) {
        const article = document.createElement('article');
        article.className = 'chat-message chat-message-' + role;
        const author = document.createElement('strong');
        author.textContent = role === 'user' ? 'Você' : 'Assistente';
        const body = document.createElement('div');
        body.textContent = content;
        article.append(author, body);
        history.appendChild(article);
        article.scrollIntoView({behavior: 'smooth', block: 'end'});
        return article;
    };

    button.disabled = true;
    input.value = '';
    appendMessage('user', sentMessage);
    feedback.className = 'chat-feedback';
    feedback.innerHTML = '<span class="chat-waiting"><span class="chat-spinner" aria-hidden="true"></span><span>Aguardando resposta...</span></span>';
    window.setOperationStatus('Enviando...');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: requestData,
            headers: {'Accept': 'application/json'},
        });
        const refreshedToken = response.headers.get('<?= esc(csrf_header(), 'js') ?>');
        const csrfInputs = document.querySelectorAll('input[name="<?= esc(csrf_token(), 'attr') ?>"]');
        if (refreshedToken) {
            csrfInputs.forEach(function (csrfInput) { csrfInput.value = refreshedToken; });
        }
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.error || 'Não foi possível enviar a mensagem.');
        }

        if ((parseInt(form.elements.conversation_id.value, 10) || 0) < 1 && payload.conversation_id) {
            window.location.assign('<?= site_url('chat') ?>?conversation=' + encodeURIComponent(payload.conversation_id));
            return;
        }
        appendMessage('assistant', payload.response || '');
        feedback.textContent = '';
        window.setOperationStatus(payload.message || 'Mensagem enviada.', 'success');
    } catch (error) {
        input.value = sentMessage;
        feedback.className = 'chat-feedback is-error';
        feedback.textContent = error.message;
        window.setOperationStatus(error.message, 'error');
    } finally {
        button.disabled = false;
        input.focus();
    }
});
</script>
