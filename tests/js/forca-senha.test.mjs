import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { readFileSync } from 'node:fs';

const require = createRequire(import.meta.url);
const { forcaSenha } = require('../../assets/js/forca-senha.js');
const casos = JSON.parse(readFileSync(new URL('./forca-senha-casos.json', import.meta.url)));

// Mesmos casos de tests/Unit/SenhaForcaTest.php: tela e banco precisam concordar.
for (const [nome, [senha, esperado]] of Object.entries(casos)) {
    test(`forca da senha: ${nome}`, () => {
        assert.equal(forcaSenha(senha), esperado);
    });
}
