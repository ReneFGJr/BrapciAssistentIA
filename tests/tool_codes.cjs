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
for (const text of ['https://cip.brapci.inf.br/', 'Olá, instituições!']) {
    const qr = QRCode.create(text, {errorCorrectionLevel: 'M'});
    assert.ok(qr.modules.size >= 21);
}
console.log('PASS: CODE128, EAN-13 válido/inválido, QR URL e UTF-8.');