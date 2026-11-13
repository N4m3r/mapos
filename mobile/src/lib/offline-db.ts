/**
 * Armazenamento do modo offline no aparelho (IndexedDB).
 *
 * Duas "gavetas": `cache` guarda a última resposta de cada leitura da API
 * (chave = servidor + caminho) e `fila` guarda os envios feitos sem internet,
 * em ordem, até serem entregues. IndexedDB cabe bem mais que o localStorage
 * (fotos e assinaturas em base64 vão na fila). Sem IndexedDB (navegador
 * antigo, modo privado), tudo fica só em memória enquanto o app estiver aberto.
 */

const NOME = 'mapos-offline';
const VERSAO = 1;

export interface ItemCache {
  chave: string;
  dados: unknown;
  /** Blob de imagem/PDF (fotos do atendimento, holerite). */
  blob?: Blob;
  salvoEm: number;
}

export interface ItemFila {
  id?: number;
  usuario: string;
  baseUrl: string;
  caminho: string;
  metodo: 'POST' | 'PUT' | 'DELETE';
  corpo?: unknown;
  rotulo: string;
  criadoEm: number;
  tentativas: number;
  /** Mensagem do servidor quando ele recusou o envio (não adianta reenviar). */
  erro?: string | null;
}

type Gaveta = 'cache' | 'fila';

const memoria: { cache: Map<string, ItemCache>; fila: Map<number, ItemFila>; seq: number } = {
  cache: new Map(),
  fila: new Map(),
  seq: 1,
};

let abrindo: Promise<IDBDatabase | null> | null = null;

function abrir(): Promise<IDBDatabase | null> {
  if (abrindo) return abrindo;
  abrindo = new Promise((resolve) => {
    try {
      if (typeof indexedDB === 'undefined') return resolve(null);
      const req = indexedDB.open(NOME, VERSAO);
      req.onupgradeneeded = () => {
        const db = req.result;
        if (!db.objectStoreNames.contains('cache')) db.createObjectStore('cache', { keyPath: 'chave' });
        if (!db.objectStoreNames.contains('fila')) db.createObjectStore('fila', { keyPath: 'id', autoIncrement: true });
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => resolve(null);
      req.onblocked = () => resolve(null);
    } catch {
      resolve(null);
    }
  });
  return abrindo;
}

function pedir<T>(gaveta: Gaveta, modo: IDBTransactionMode, fn: (s: IDBObjectStore) => IDBRequest): Promise<T> {
  return abrir().then(
    (db) =>
      new Promise<T>((resolve, reject) => {
        if (!db) return reject(new Error('sem-indexeddb'));
        const tx = db.transaction(gaveta, modo);
        const req = fn(tx.objectStore(gaveta));
        req.onsuccess = () => resolve(req.result as T);
        req.onerror = () => reject(req.error ?? new Error('indexeddb'));
      }),
  );
}

export async function lerCache(chave: string): Promise<ItemCache | null> {
  try {
    return (await pedir<ItemCache | undefined>('cache', 'readonly', (s) => s.get(chave))) ?? null;
  } catch {
    return memoria.cache.get(chave) ?? null;
  }
}

export async function gravarCache(item: ItemCache): Promise<void> {
  try {
    await pedir('cache', 'readwrite', (s) => s.put(item));
  } catch {
    memoria.cache.set(item.chave, item);
  }
}

export async function limparCache(): Promise<void> {
  memoria.cache.clear();
  try {
    await pedir('cache', 'readwrite', (s) => s.clear());
  } catch {
    /* só memória */
  }
}

export async function listarFila(): Promise<ItemFila[]> {
  try {
    const itens = await pedir<ItemFila[]>('fila', 'readonly', (s) => s.getAll());
    return itens.sort((a, b) => (a.id ?? 0) - (b.id ?? 0));
  } catch {
    return [...memoria.fila.values()].sort((a, b) => (a.id ?? 0) - (b.id ?? 0));
  }
}

export async function adicionarFila(item: ItemFila): Promise<number> {
  try {
    return await pedir<number>('fila', 'readwrite', (s) => s.add(item));
  } catch {
    const id = memoria.seq++;
    memoria.fila.set(id, { ...item, id });
    return id;
  }
}

export async function atualizarFila(item: ItemFila): Promise<void> {
  try {
    await pedir('fila', 'readwrite', (s) => s.put(item));
  } catch {
    if (item.id !== undefined) memoria.fila.set(item.id, item);
  }
}

export async function removerFila(id: number): Promise<void> {
  memoria.fila.delete(id);
  try {
    await pedir('fila', 'readwrite', (s) => s.delete(id));
  } catch {
    /* só memória */
  }
}
