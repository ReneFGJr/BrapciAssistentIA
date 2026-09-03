<section class="col-12 chat-prompt-section" aria-label="Enviar mensagem">
    <form id="chat-prompt-form" class="chat-prompt" action="<?= site_url('chat/messages') ?>" method="post">
        <?= csrf_field() ?>
        <label class="visually-hidden" for="chat-message">Mensagem</label>
        <input id="chat-message" name="message" type="text" maxlength="4000"
            placeholder="Digite uma mensagem..." autocomplete="off" required>
        <button type="submit" aria-label="Enviar mensagem">&gt;</button>
    </form>
</section>

<style>
.chat-page{min-height:calc(100vh - 265px);padding:20px}.chat-page h1{margin:0;color:#fff;font-size:24px}.chat-feedback{margin-top:16px;color:#8db5d1}.chat-feedback.is-error{color:#ff8a80}.chat-prompt-section{position:sticky;bottom:0;padding:16px 20px 0;background:linear-gradient(0deg,#020811 65%,transparent)}.chat-prompt{display:flex;align-items:center;gap:10px;width:100%;margin:0;padding:8px;border:1px solid #1e5579;border-radius:8px;background:#041321;box-shadow:0 10px 30px #0008}.chat-prompt:focus-within{border-color:#00d9ff;box-shadow:0 0 0 3px #00d9ff20,0 10px 30px #0008}.chat-prompt input{min-width:0;flex:1;border:0;outline:0;padding:10px 12px;color:#fff;background:transparent;font-size:16px}.chat-prompt input::placeholder{color:#6f94af}.chat-prompt button{width:42px;height:42px;flex:0 0 42px;border:0;border-radius:6px;color:#02101a;background:#00d9ff;font-size:25px;font-weight:700;line-height:1;transition:background .2s ease,transform .2s ease}.chat-prompt button:hover{background:#67e9ff;transform:translateX(2px)}.chat-prompt button:disabled{cursor:wait;opacity:.65;transform:none}@media(max-width:768px){.chat-page{min-height:calc(100vh - 285px);padding:16px}.chat-prompt-section{padding:12px 16px 0}}
</style>

<script>
document.getElementById('chat-prompt-form').addEventListener('submit', async function (event) {
    event.preventDefault();

    const form = event.currentTarget;
    const input = form.elements.message;
    const button = form.querySelector('button');
    const feedback = document.getElementById('chat-feedback');

    button.disabled = true;
    feedback.className = 'chat-feedback';
    feedback.textContent = 'Enviando...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {'Accept': 'application/json'},
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.error || 'Não foi possível enviar a mensagem.');
        }

        input.value = '';
        feedback.textContent = payload.message || payload.response || 'Mensagem enviada.';
    } catch (error) {
        feedback.className = 'chat-feedback is-error';
        feedback.textContent = error.message;
    } finally {
        button.disabled = false;
        input.focus();
    }
});
</script>