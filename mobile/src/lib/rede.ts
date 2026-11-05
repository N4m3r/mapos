/** Contas simples de rede IPv4 usadas na configuração das câmeras. */

const IP = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;

/** "192.168.1.64" → "192.168.1"; null se não for IPv4. */
export function prefixoDeRede(ip: string | null | undefined): string | null {
  const m = String(ip ?? '').trim().match(IP);
  return m ? `${m[1]}.${m[2]}.${m[3]}` : null;
}

export function ipValido(ip: string | null | undefined): boolean {
  const m = String(ip ?? '').trim().match(IP);
  if (!m) return false;
  return m.slice(1).every((parte) => Number(parte) <= 255);
}

/**
 * Primeiro IP livre a partir de um ponto de partida.
 *
 * As câmeras Hikvision saem de fábrica em 192.168.1.64: com várias na mesma
 * rede, todas colidem. O app sugere um endereço que ninguém respondeu na
 * varredura e que o projeto ainda não usa.
 */
export function sugerirIpLivre(
  prefixo: string | null | undefined,
  usados: Array<string | null | undefined>,
  inicio = 100,
): string | null {
  if (!prefixo || !ipValido(`${prefixo}.1`)) return null;

  const ocupados = new Set(
    usados
      .map((ip) => String(ip ?? '').trim())
      .filter((ip) => ipValido(ip)),
  );

  for (let n = inicio; n <= 250; n++) {
    const ip = `${prefixo}.${n}`;
    if (!ocupados.has(ip)) return ip;
  }
  // Faixa alta tomada: tenta o início, evitando 1 (gateway) e reservados baixos.
  for (let n = 20; n < inicio; n++) {
    const ip = `${prefixo}.${n}`;
    if (!ocupados.has(ip)) return ip;
  }
  return null;
}

/** O IP pertence à mesma faixa /24? */
export function mesmaFaixa(ip: string | null | undefined, prefixo: string | null | undefined): boolean {
  const doIp = prefixoDeRede(ip);
  return Boolean(doIp) && Boolean(prefixo) && doIp === prefixo;
}
