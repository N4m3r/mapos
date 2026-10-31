import test from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { interpretar } = require('../../assets/js/etiqueta-camera.js');

test('Hikvision serie completa', () => {
    const r = interpretar(['DS-2CD1023G0E-I20220315AAWRK12345678']);
    assert.equal(r.modelo, 'DS-2CD1023G0E-I');
    assert.equal(r.serie, 'DS-2CD1023G0E-I20220315AAWRK12345678');
    assert.equal(r.origem, 'hikvision-completo');
});

test('Hikvision com lente e CRLF', () => {
    const r = interpretar(['  DS-2CD1043G2-LIU(2.8mm)20230510AAWRAB1234567\r\n']);
    assert.equal(r.modelo, 'DS-2CD1043G2-LIU(2.8mm)');
    assert.ok(r.serie.startsWith('DS-2CD1043G2-LIU(2.8mm)2023'));
});

test('iDS e IDS', () => {
    assert.equal(interpretar(['iDS-2CD7A46G0-IZHS20210101AAWRC12345678']).modelo, 'iDS-2CD7A46G0-IZHS');
    assert.equal(interpretar(['IDS-2CD7A46G0-IZHS20210101AAWRC12345678']).modelo, 'IDS-2CD7A46G0-IZHS');
});

test('serie curta Hikvision', () => {
    const r = interpretar(['J12345678']);
    assert.equal(r.serie, 'J12345678');
    assert.equal(r.origem, 'hikvision-curto');
});

test('QR Hik-Connect multilinha: nao usa codigo de verificacao como serie', () => {
    const r = interpretar(['DS-2CD1023G0E-I\r\nJ12345678\r\nABCDEF\r\n']);
    assert.equal(r.modelo, 'DS-2CD1023G0E-I');
    assert.equal(r.serie, 'J12345678');
    assert.equal(interpretar(['ABCDEF\nJ12345678']).serie, 'J12345678');
    assert.equal(interpretar(['ABCDEF']).serie, '');
});

test('MAC com : e -, e 12 hex com prefixo MAC', () => {
    assert.equal(interpretar(['mac: a4:14:37:1b:2c:3d']).mac, 'A4:14:37:1B:2C:3D');
    assert.equal(interpretar(['A4-14-37-1B-2C-3D']).mac, 'A4:14:37:1B:2C:3D');
    assert.equal(interpretar(['MAC A414371B2C3D']).mac, 'A4:14:37:1B:2C:3D');
    assert.equal(interpretar(['MAC: A414371B2C3D']).serie, 'A4:14:37:1B:2C:3D');
});

test('serie + MAC no campo serie', () => {
    const r = interpretar(['J12345678', 'A4:14:37:1B:2C:3D']);
    assert.equal(r.serie, 'J12345678 / A4:14:37:1B:2C:3D');
});

test('serie + MAC que nao cabe: so a serie', () => {
    const longa = 'DS-2CD1043G2-LIU(2.8mm)20230510AAWRAB1234567890123';
    const r = interpretar([longa, 'A4:14:37:1B:2C:3D']);
    assert.equal(r.serie, longa.slice(0, 60));
    assert.ok(r.serie.length <= 60);
    assert.equal(r.mac, 'A4:14:37:1B:2C:3D');
});

test('Intelbras', () => {
    assert.equal(interpretar(['VIP 1230 B']).modelo, 'VIP 1230 B');
    const r = interpretar(['VIP-1230-B\nS/N: 1A2B3C4D5E6F']);
    assert.equal(r.modelo, 'VIP-1230-B');
    assert.equal(r.serie, '1A2B3C4D5E6F');
    assert.equal(interpretar(['SN:ABC12345']).serie, 'ABC12345');
});

test('lixo, URL e vazio', () => {
    assert.deepEqual(interpretar([]), { modelo: '', serie: '', mac: '', origem: 'nenhuma' });
    const u = interpretar(['https://exemplo.com/produto/ABCDEFGHIJKLMNOP']);
    assert.equal(u.serie, '');
    assert.equal(u.origem, 'nenhuma');
    assert.equal(interpretar(['oi', 'abc']).serie, '');
});

test('generico: maior token alfanumerico', () => {
    const r = interpretar(['ref 12345678 XYZ9876543210']);
    assert.equal(r.serie, 'XYZ9876543210');
    assert.equal(r.origem, 'generico');
});

test('limite do modelo (80)', () => {
    const r = interpretar(['VIP-' + 'X'.repeat(200)]);
    assert.equal(r.modelo.length, 80);
});
