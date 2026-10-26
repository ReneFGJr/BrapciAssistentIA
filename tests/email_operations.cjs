const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('app/Views/email/account.php', 'utf8');
const sendCode = source.slice(source.indexOf(`    shell.querySelectorAll('form[action$="/send"]')`), source.indexOf(`    shell.querySelectorAll('form').forEach`));
const syncCode = source.slice(source.indexOf(`    document.getElementById('mail-sync-all').addEventListener`), source.indexOf(`    shell.querySelectorAll('.mail-delete-form')`));
async function exercise(code, failure, sent = true) {
    let handler, release;
    const events = [], button = {disabled: false}, textarea = {value: 'Resposta preservada', focus() {}};
    // A named input shadows HTMLFormElement.action in the browser.
    const form = {action: {value: 'reply', toString: () => '[object HTMLInputElement]'}, getAttribute: name => name === 'action' ? '/send' : null, querySelector: selector => selector === 'textarea' ? textarea : button,
        closest: () => ({id: 'message-7'}), addEventListener: (_, callback) => handler = callback};
    const context = {
        shell: {querySelectorAll: () => [form]}, document: {getElementById: () => form},
        working: false, cancelRead() {}, scheduleRead() {},
        FormData: class { *[Symbol.iterator]() { yield ['reply', textarea.value]; } },
        post: (path, values, guard, endpoint) => {
            if (code === sendCode) assert.equal(endpoint, '/send', 'Named action input must not override send URL');
            return new Promise((resolve, reject) => { release = () => failure ? reject(new Error('Falha SMTP')) : resolve({sent, message: 'Envio confirmado', error: sent ? undefined : 'Sem confirmação', count: 2}); });
        },
        setBusy(message = '') { context.working = Boolean(message); events.push(['busy', message]); },
        notify(message, status) { events.push([status, message]); },
        reloadWithResult(message, status, originalId) { events.push(['reload', message, status, originalId]); },
        activeAccounts: [{id: 1, email: 'sender@example.test'}], accountBase: '/email/account',
    };
    vm.runInNewContext(code, context);
    const event = {defaultPrevented: false, currentTarget: form, preventDefault() { this.defaultPrevented = true; }};
    const task = handler(event);
    assert.equal(context.working, true, 'Animation active while request is pending');
    assert.equal(button.disabled, true, 'Submit disabled while request is pending');
    release(); await task;
    if (failure || !sent) {
        assert.equal(context.working, false, 'Animation cleared on failure');
        assert.equal(button.disabled, false, 'Retry available after failure');
        assert(events.some(item => item[0] === 'error'), 'Visible failure status');
        assert.equal(textarea.value, 'Resposta preservada');
        assert(!events.some(item => item[0] === 'reload'), 'No reload loses the draft');
    } else {
        assert(events.some(item => item[0] === 'reload' && item[2] === 'success'), 'Successful operation preserves result across reload');
    }
}
(async () => {
    await exercise(sendCode, true);
    await exercise(sendCode, false, false);
    await exercise(sendCode, false);
    await exercise(syncCode, true);
    await exercise(syncCode, false);
    // Check the complete inline script after replacing server-rendered expressions.
    const script = source.slice(source.indexOf('<script>') + 8, source.lastIndexOf('</script>')).replace(/<\?=[\s\S]*?\?>/g, 'null');
    new vm.Script(script);
    console.log('Email operations: pending animation, send/collection success and failure, draft preservation and JavaScript syntax passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
