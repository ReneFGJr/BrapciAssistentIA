const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('app/Views/email/meeting.php', 'utf8');
const script = source.slice(source.indexOf('<script>') + 8, source.lastIndexOf('</script>')).replace(/<\?=[\s\S]*?\?>/g, 'csrf');
async function exercise(success) {
    let handler, release;
    const button = {disabled: false}, csrf = {value: 'old'}, result = {}, busy = {hidden: true}, links = [], statuses = [];
    const form = {getAttribute: () => '/meeting', querySelector: selector => selector === 'button' ? button : csrf,
        setAttribute() {}, addEventListener: (_, callback) => handler = callback};
    const context = {document: {getElementById: id => ({'email-meeting-form': form, 'meeting-result': result, 'meeting-busy': busy, 'meeting-links': {append: link => links.push(link)}}[id]), createElement: () => ({})},
        window: {setOperationStatus: (...args) => statuses.push(args)},
        FormData: class {},
        fetch: (url, options) => {
            assert.equal(url, '/meeting'); assert.equal(options.method, 'POST');
            return new Promise(resolve => { release = () => resolve({ok: success, json: async () => success
                ? {message: 'Reunião confirmada', csrf: 'new', event_url: 'https://www.google.com/calendar/event?eid=x', email_url: '/email/account?message=1'}
                : {error: 'Reconecte a agenda', csrf: 'new'}}); });
        }};
    vm.runInNewContext(script, context);
    const pending = handler({preventDefault() {}});
    assert.equal(busy.hidden, false); assert.equal(button.disabled, true); assert.equal(form.inert, true);
    release(); await pending;
    assert.equal(busy.hidden, true); assert.equal(form.inert, false); assert.equal(csrf.value, 'new');
    assert.equal(result.hidden, false);
    if (success) {
        assert.equal(button.disabled, true); assert.equal(links.length, 2); assert.equal(statuses.at(-1)[1], 'success');
    } else {
        assert.equal(button.disabled, false); assert.equal(links.length, 0); assert.equal(statuses.at(-1)[1], 'error');
    }
}
(async () => { await exercise(true); await exercise(false); console.log('Meeting form: animation, API result, footer status, CSRF refresh, retry and navigation links passed.'); })()
    .catch(error => { console.error(error); process.exitCode = 1; });
