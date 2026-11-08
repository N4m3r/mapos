/**
 * Nova atividade não programada: cliente, descrição, itens (serviços e
 * produtos sem preço), fotos e assinatura do solicitante. O preço é resolvido
 * no servidor — o app nunca envia valores.
 */

import { useRef, useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useApi, useApp } from '../../lib/store';
import { navigate } from '../../lib/router';
import { escolherFoto } from '../../lib/capture';
import { Alert, Button, Card, Field, Input, SkeletonList, Textarea, Topbar } from '../../components/ui';
import { Assinatura, lerAssinatura } from '../../components/Assinatura';
import type { ClienteBusca, ProdutoBusca, ServicoBusca } from '../../lib/types-tecnico';

interface Item {
  id: number;
  nome: string;
  quantidade: number;
}

export function TecnicoAtividadeScreen() {
  const { baseUrl, token, handle } = useApi();
  const { can } = useApp();

  const [clienteTermo, setClienteTermo] = useState('');
  const [clientes, setClientes] = useState<ClienteBusca[]>([]);
  const [buscandoCliente, setBuscandoCliente] = useState(false);
  const [cliente, setCliente] = useState<ClienteBusca | null>(null);

  const [servicoTermo, setServicoTermo] = useState('');
  const [servicos, setServicos] = useState<ServicoBusca[]>([]);
  const [servicosSel, setServicosSel] = useState<Item[]>([]);

  const [produtoTermo, setProdutoTermo] = useState('');
  const [produtos, setProdutos] = useState<ProdutoBusca[]>([]);
  const [produtosSel, setProdutosSel] = useState<Item[]>([]);

  const [descricao, setDescricao] = useState('');
  const [defeito, setDefeito] = useState('');
  const [chamado, setChamado] = useState('');
  const [realizado, setRealizado] = useState('');
  const [fotos, setFotos] = useState<string[]>([]);
  const [querAssinatura, setQuerAssinatura] = useState(false);
  const [nomeAssinatura, setNomeAssinatura] = useState('');
  const [temAssinatura, setTemAssinatura] = useState(false);
  const canvas = useRef<HTMLCanvasElement | null>(null);

  const [busy, setBusy] = useState(false);
  const [erro, setErro] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  if (!can('aTecnicoAtividade')) {
    return (
      <div className="screen">
        <Topbar title="Nova atividade" onBack={() => navigate('tecnico/os')} />
        <div className="content">
          <Alert kind="error">Seu usuário não tem permissão para abrir atividades não programadas.</Alert>
        </div>
      </div>
    );
  }

  const buscarClientes = async (termo: string) => {
    setClienteTermo(termo);
    if (termo.trim().length < 2) {
      setClientes([]);
      return;
    }
    setBuscandoCliente(true);
    try {
      setClientes(await handle(() => tec.buscarClientes(baseUrl, token, termo.trim())));
    } catch {
      setClientes([]);
    } finally {
      setBuscandoCliente(false);
    }
  };

  const buscarServicos = async (termo: string) => {
    setServicoTermo(termo);
    if (termo.trim().length < 2) {
      setServicos([]);
      return;
    }
    try {
      setServicos(await handle(() => tec.buscarServicos(baseUrl, token, termo.trim())));
    } catch {
      setServicos([]);
    }
  };

  const buscarProdutos = async (termo: string) => {
    setProdutoTermo(termo);
    if (termo.trim().length < 2) {
      setProdutos([]);
      return;
    }
    try {
      setProdutos(await handle(() => tec.buscarProdutos(baseUrl, token, termo.trim())));
    } catch {
      setProdutos([]);
    }
  };

  const adicionarFoto = async () => {
    const foto = await escolherFoto();
    if (foto) setFotos((l) => [...l, foto].slice(0, 6));
  };

  const salvar = async () => {
    if (cliente?.exige_chamado && !chamado.trim()) {
      setErro('Este cliente exige o Nº do chamado. Informe o chamado para abrir a atividade.');
      return;
    }
    if (!cliente) {
      setErro('Selecione o cliente da atividade.');
      return;
    }
    setBusy(true);
    setErro(null);
    setOk(null);
    try {
      const assinatura = querAssinatura ? lerAssinatura(canvas.current) : null;
      if (querAssinatura && !assinatura) {
        setErro('Assine no quadro ou desmarque a coleta de assinatura.');
        return;
      }
      const r = await handle(() =>
        tec.criarAtividade(baseUrl, token, {
          clientes_id: cliente.id,
          descricaoProduto: descricao.trim() || undefined,
          chamado_id: chamado.trim() || undefined,
          defeito: defeito.trim() || undefined,
          observacoes: descricao.trim() || undefined,
          atividade_realizada: realizado.trim() || undefined,
          servicos: servicosSel.map((s) => ({ id: s.id, quantidade: s.quantidade })),
          produtos: produtosSel.map((p) => ({ id: p.id, quantidade: p.quantidade })),
          fotos,
          assinatura: assinatura ?? undefined,
          assinatura_nome: nomeAssinatura.trim() || undefined,
        }),
      );
      setOk(`Atividade aberta (OS ${String(r.idOs).padStart(4, '0')}).`);
      navigate(`tecnico/atendimento/${r.idOs}`);
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="screen">
      <Topbar title="Nova atividade" subtitle="Atendimento fora da agenda" onBack={() => navigate('tecnico/os')} />

      <div className="content">
        {erro ? <Alert kind="error">{erro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        <Card>
          <div className="stack">
            <p className="section-title">Cliente</p>
            {cliente ? (
              <div className="row row--between">
                <div style={{ minWidth: 0 }}>
                  <p className="card__title">{cliente.nome}</p>
                  <p className="card__meta">
                    {[cliente.documento, cliente.telefone].filter(Boolean).join(' · ') || 'Sem contato cadastrado'}
                  </p>
                </div>
                <button type="button" className="reset-button" onClick={() => setCliente(null)}>
                  Trocar
                </button>
              </div>
            ) : (
              <>
                <Field label="Buscar cliente" hint="Por nome, documento ou telefone">
                  <Input
                    type="search"
                    placeholder="Digite para buscar…"
                    value={clienteTermo}
                    onChange={(e) => void buscarClientes(e.target.value)}
                  />
                </Field>
                {buscandoCliente ? <SkeletonList count={1} /> : null}
                {clientes.map((c) => (
                  <button
                    key={c.id}
                    type="button"
                    className="listrow"
                    onClick={() => {
                      setCliente(c);
                      setClientes([]);
                      setClienteTermo('');
                    }}
                  >
                    <span className="listrow__main">
                      <span className="listrow__title">{c.nome}</span>
                      <span className="listrow__meta">{[c.documento, c.telefone].filter(Boolean).join(' · ')}</span>
                    </span>
                  </button>
                ))}
                {clienteTermo.trim().length >= 2 && !buscandoCliente && !clientes.length ? (
                  <p className="card__meta">Nenhum cliente encontrado.</p>
                ) : null}
              </>
            )}
          </div>
        </Card>

        <Card>
          <div className="stack">
            <Field
              label={cliente?.exige_chamado ? 'Nº do chamado (obrigatório)' : 'Nº do chamado'}
              hint={
                cliente?.exige_chamado
                  ? 'Este cliente exige o Nº do chamado para atividade não programada.'
                  : 'Protocolo informado pelo cliente (opcional)'
              }
            >
              <Input
                value={chamado}
                onChange={(e) => setChamado(e.target.value)}
                maxLength={60}
                required={Boolean(cliente?.exige_chamado)}
                aria-invalid={Boolean(cliente?.exige_chamado) && !chamado.trim()}
              />
            </Field>
            <Field label="Produto / equipamento">
              <Textarea value={descricao} onChange={(e) => setDescricao(e.target.value)} maxLength={255} />
            </Field>
            <Field label="Defeito relatado">
              <Textarea value={defeito} onChange={(e) => setDefeito(e.target.value)} maxLength={2000} />
            </Field>
            <Field label="Atividade realizada">
              <Textarea
                value={realizado}
                onChange={(e) => setRealizado(e.target.value)}
                maxLength={2000}
                placeholder="Descreva o que foi feito…"
              />
            </Field>
          </div>
        </Card>

        <Card>
          <p className="section-title">Serviços</p>
          <Input
            type="search"
            placeholder="Buscar serviço…"
            value={servicoTermo}
            onChange={(e) => void buscarServicos(e.target.value)}
            style={{ marginTop: 8 }}
          />
          {servicos.map((s) => (
            <button
              key={s.id}
              type="button"
              className="listrow"
              onClick={() => {
                setServicosSel((l) => (l.some((i) => i.id === s.id) ? l : [...l, { ...s, quantidade: 1 }]));
                setServicos([]);
                setServicoTermo('');
              }}
            >
              <span className="listrow__main">
                <span className="listrow__title">{s.nome}</span>
              </span>
              <span className="pill pill--info">+</span>
            </button>
          ))}
          {servicosSel.length ? (
            <div className="stack" style={{ marginTop: 10 }}>
              {servicosSel.map((s) => (
                <div className="row row--between" key={s.id}>
                  <span style={{ flex: 1, minWidth: 0, fontSize: '0.88rem' }}>{s.nome}</span>
                  <Quantidade
                    valor={s.quantidade}
                    onChange={(q) => setServicosSel((l) => l.map((i) => (i.id === s.id ? { ...i, quantidade: q } : i)))}
                  />
                  <button type="button" className="reset-button" onClick={() => setServicosSel((l) => l.filter((i) => i.id !== s.id))}>
                    remover
                  </button>
                </div>
              ))}
            </div>
          ) : null}
        </Card>

        <Card>
          <p className="section-title">Produtos</p>
          <Input
            type="search"
            placeholder="Buscar produto…"
            value={produtoTermo}
            onChange={(e) => void buscarProdutos(e.target.value)}
            style={{ marginTop: 8 }}
          />
          {produtos.map((p) => (
            <button
              key={p.id}
              type="button"
              className="listrow"
              onClick={() => {
                setProdutosSel((l) => (l.some((i) => i.id === p.id) ? l : [...l, { id: p.id, nome: p.nome, quantidade: 1 }]));
                setProdutos([]);
                setProdutoTermo('');
              }}
            >
              <span className="listrow__main">
                <span className="listrow__title">{p.nome}</span>
                <span className="listrow__meta">Estoque: {p.estoque ?? '—'}</span>
              </span>
              <span className="pill pill--info">+</span>
            </button>
          ))}
          {produtosSel.length ? (
            <div className="stack" style={{ marginTop: 10 }}>
              {produtosSel.map((p) => (
                <div className="row row--between" key={p.id}>
                  <span style={{ flex: 1, minWidth: 0, fontSize: '0.88rem' }}>{p.nome}</span>
                  <Quantidade
                    valor={p.quantidade}
                    onChange={(q) => setProdutosSel((l) => l.map((i) => (i.id === p.id ? { ...i, quantidade: q } : i)))}
                  />
                  <button type="button" className="reset-button" onClick={() => setProdutosSel((l) => l.filter((i) => i.id !== p.id))}>
                    remover
                  </button>
                </div>
              ))}
            </div>
          ) : null}
        </Card>

        <Card>
          <p className="section-title">Fotos</p>
          <button type="button" className="capture-btn" style={{ marginTop: 8 }} onClick={() => void adicionarFoto()}>
            Tirar foto
          </button>
          {fotos.length ? (
            <div className="thumbs" style={{ marginTop: 8 }}>
              {fotos.map((f, i) => (
                <button key={i} type="button" className="thumb" onClick={() => setFotos((l) => l.filter((_, x) => x !== i))}>
                  <img src={f} alt="" />
                  <span className="thumb__etapa">remover</span>
                </button>
              ))}
            </div>
          ) : null}
        </Card>

        <Card>
          <label className="checkline">
            <input type="checkbox" checked={querAssinatura} onChange={(e) => setQuerAssinatura(e.target.checked)} />
            <span>Coletar assinatura do solicitante agora</span>
          </label>
          {querAssinatura ? (
            <div className="stack" style={{ marginTop: 8 }}>
              <Assinatura canvasRef={canvas} onChange={setTemAssinatura} rotulo="Assinatura do solicitante" altura={160} />
              <Field label="Nome de quem assinou">
                <Input value={nomeAssinatura} onChange={(e) => setNomeAssinatura(e.target.value)} maxLength={100} />
              </Field>
              {!temAssinatura ? (
                <p className="card__meta">Assine no quadro acima ou desmarque a opção.</p>
              ) : null}
            </div>
          ) : null}
        </Card>

        <div className="sticky-actions">
          <Button variant="ghost" block onClick={() => navigate('tecnico/os')}>
            Cancelar
          </Button>
          <Button block loading={busy} onClick={() => void salvar()}>
            Abrir atividade
          </Button>
        </div>
      </div>
    </div>
  );
}

function Quantidade({ valor, onChange }: { valor: number; onChange: (v: number) => void }) {
  return (
    <div className="row" style={{ gap: 4 }}>
      <button
        type="button"
        className="icon-btn icon-btn--sm"
        onClick={() => onChange(Math.max(1, valor - 1))}
        aria-label="Diminuir"
      >
        −
      </button>
      <span style={{ minWidth: 22, textAlign: 'center', fontWeight: 650 }}>{valor}</span>
      <button type="button" className="icon-btn icon-btn--sm" onClick={() => onChange(valor + 1)} aria-label="Aumentar">
        +
      </button>
    </div>
  );
}
