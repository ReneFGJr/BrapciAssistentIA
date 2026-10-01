const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const context = { window: {}, Uint8Array, Uint16Array, Uint32Array, TextEncoder, console };
vm.createContext(context);
vm.runInContext(fs.readFileSync(require.resolve('../public/assets/vendor/qrcode.min.js'), 'utf8'), context);
const QRCode = context.QRCode || context.window.QRCode;
vm.runInContext(fs.readFileSync(require.resolve('../public/assets/vendor/JsBarcode.all.min.js'), 'utf8'), context);
const JsBarcode = context.window.JsBarcode;
const barcode = {};
JsBarcode(barcode, 'BRAPCI-2026', { format: 'CODE128' });
assert.ok(barcode.encodings[0].data.length > 0);
let valid;
JsBarcode({}, '5901234123457', {format: 'EAN13', valid: result => valid = result});
assert.equal(valid, true);
JsBarcode({}, '5901234123450', {format: 'EAN13', valid: result => valid = result});
assert.equal(valid, false);
// Exercise the form's validation and PNG download flow with the installed encoder.
const elements = Object.fromEntries(['code-form', 'code-content', 'barcode-format', 'code-result', 'code-canvas', 'code-download', 'code-status'].map(id => [id, {
    handlers: {},
    addEventListener(type, handler) { this.handlers[type] = handler; },
    removeAttribute(name) { delete this[name]; },
    toDataURL(type) { assert.equal(type, 'image/png'); return 'data:image/png;base64,test'; }
}]));
const messages = [];
const formContext = {
    document: {
        querySelector: () => ({ dataset: { codeTool: 'barcode' } }),
        getElementById: id => elements[id]
    },
    window: {
        JsBarcode: (canvas, value, options) => {
            const encoded = {};
            JsBarcode(encoded, value, options);
            canvas.encodings = encoded.encodings;
        },
        setOperationStatus: (message, type) => messages.push({ message, type })
    }
};
vm.runInNewContext(fs.readFileSync(require.resolve('../public/assets/js/tool-codes.js'), 'utf8'), formContext);
elements['barcode-format'].value = 'CODE39';
for (const value of ['BRAPCI-2026', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .$/+%-', 'A'.repeat(80)]) {
    elements['code-content'].value = value;
    elements['code-form'].handlers.submit({ preventDefault() {} });
    assert.equal(messages.at(-1).type, 'success');
    assert.ok(elements['code-canvas'].encodings[0].data.length > 0);
    assert.equal(elements['code-canvas'].encodings[0].text, value);
    assert.equal(elements['code-result'].hidden, false);
    assert.ok(elements['code-download'].href.startsWith('data:image/png'));
}
for (const value of ['brapci', 'AÇÃO', '*BRAPCI*', 'A_B', 'A\nB', 'A'.repeat(81)]) {
    elements['code-content'].value = value;
    elements['code-form'].handlers.submit({ preventDefault() {} });
    assert.equal(messages.at(-1).type, 'error');
    assert.match(messages.at(-1).message, /Code39/);
    assert.equal(elements['code-result'].hidden, true);
    assert.equal(elements['code-download'].href, undefined);
}
for (const text of ['https://cip.brapci.inf.br/', 'Olá, instituições!']) {
    const qr = QRCode.create(text, {errorCorrectionLevel: 'M'});
    assert.ok(qr.modules.size >= 21);
}
console.log('PASS: CODE128, Code39 com validação e download, EAN-13 válido/inválido, QR URL e UTF-8.');
