const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/parent/ai/index.blade.php'), 'utf8');
const formatter = view.slice(view.indexOf('        function escapeHtml('), view.indexOf('        function formatUserContent('));
const context = vm.createContext({});
vm.runInContext(formatter, context);
const render = text => context.formatAssistantContent(text);

test('numbered steps continue after explanatory paragraphs and blank lines', () => {
    const html = render('1. Choose an adoption provider\nContact the agency.\n\n2. Submit the application\nPrepare your form.\n\n3. Prepare supporting documents');
    assert.deepEqual([...html.matchAll(/<li value="(\d+)"/g)].map(match => match[1]), ['1', '2', '3']);
    assert.match(html, /<ol start="2"><li value="2">Submit the application/);
    assert.match(html, /<ol start="3"><li value="3">Prepare supporting documents/);
});

test('numbered steps keep their source values when bullet lists intervene', () => {
    const html = render('4) Prepare documents\n- Birth certificate\n- Medical clearance\n\n5) Submit the documents');
    assert.match(html, /<ol start="4"><li value="4">/);
    assert.match(html, /<ul><li>Birth certificate<\/li><li>Medical clearance<\/li><\/ul>/);
    assert.match(html, /<ol start="5"><li value="5">/);
});

test('intentionally restarted lists and bold item titles are preserved', () => {
    const html = render('1. **First process**\n2. Second step\n\n## Another process\n1. Start again');
    assert.deepEqual([...html.matchAll(/<li value="(\d+)"/g)].map(match => match[1]), ['1', '2', '1']);
    assert.match(html, /<strong>First process<\/strong>/);
});

test('numbered answers escape HTML and remain safe while streaming', () => {
    const html = context.formatAssistantContent('2. <script>alert(1)</script>\n\n3. Continue', true);
    assert.doesNotMatch(html, /<script>/);
    assert.match(html, /&lt;script&gt;/);
    assert.match(html, /<li value="3">Continue<\/li>/);
    assert.match(html, /ai-cursor/);
});
