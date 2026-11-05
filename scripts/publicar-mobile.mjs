/**
 * Publica o build do MAPOS Mobile dentro do webroot da instalação, em /mobile/.
 *
 * Servido da mesma origem, o app fala com a API sem CORS e já detecta a URL
 * do sistema sozinho — não é preciso configurar nada no servidor.
 *
 * Uso: node scripts/publicar-mobile.mjs [--destino <caminho>]
 *   padrão: <raiz do MAPOS>/mobile-app
 */

import { cpSync, existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = dirname(here);
const dist = join(root, 'mobile', 'dist');

const flagIndex = process.argv.indexOf('--destino');
const destino = resolve(flagIndex > -1 ? process.argv[flagIndex + 1] : join(root, 'mobile-app'));

if (!existsSync(join(dist, 'index.html'))) {
  console.error(`Build não encontrado em ${dist}\nRode antes: npm --prefix mobile run build`);
  process.exit(1);
}

rmSync(destino, { recursive: true, force: true });
mkdirSync(destino, { recursive: true });
cpSync(dist, destino, { recursive: true });

// .htaccess: impede o CodeIgniter de reescrever as rotas do app e libera o shell.
writeFileSync(
  join(destino, '.htaccess'),
  `# MAPOS Mobile (app estático) — gerado por scripts/publicar-mobile.mjs
<IfModule mod_rewrite.c>
    RewriteEngine Off
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 30 days"
</IfModule>

AddType application/manifest+json .webmanifest
`,
  'utf8',
);

console.log(`MAPOS Mobile publicado em: ${destino}`);
console.log('Acesse: <url da sua instalação>/mobile-app/');
