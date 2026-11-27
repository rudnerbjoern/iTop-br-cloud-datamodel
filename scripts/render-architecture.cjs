const fs = require('node:fs');
const path = require('node:path');
const asciidoctor = require('@asciidoctor/core')();
const warningSeverity = 2; // Asciidoctor uses Ruby Logger severity levels.

const root = path.resolve(__dirname, '..');
const source = path.join(root, 'docs', 'architecture');
const entry = path.join(source, 'arc42-template.adoc');
const output = path.resolve(process.argv[2] ?? path.join(root, '.tmp', 'architecture-html'));

if (!fs.existsSync(entry)) {
    throw new Error(`Missing architecture document at ${entry}.`);
}

const editions = [
    ['cloud-inventory.html', { 'arc42help!': '' }],
    ['cloud-inventory-with-help.html', { arc42help: '' }],
];

const files = editions.map(([name, attributes]) => {
    const html = asciidoctor.convertFile(entry, {
        safe: 'safe',
        base_dir: source,
        standalone: true,
        to_file: false,
        attributes: { 'data-uri': '', ...attributes },
    });
    if (asciidoctor.LoggerManager.getLogger().getMaxSeverity() >= warningSeverity) {
        throw new Error(`Cannot publish ${name}. See the Asciidoctor diagnostics above.`);
    }
    return [name, html];
});

fs.mkdirSync(output, { recursive: true });
for (const [name, html] of files) {
    const target = path.join(output, name);
    fs.writeFileSync(target, html);
    console.log(target);
}
