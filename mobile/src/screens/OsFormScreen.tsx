/**
 * Formulário de OS (criar e editar).
 * O PUT da API exige dataInicial, dataFinal, status, clientes_id e usuarios_id
 * e não sobrescreve `faturado` (sempre envia 0) — replicamos esse contrato.
 */

import { useEffect, useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import * as api from '../lib/api';
import { useApi, useApp } from '../lib/store';
import { todayIso, toApiDate } from '../lib/format';
import { navigate } from '../lib/router';
import { Alert, Button, Card, Field, Input, Select, SkeletonList, Textarea, Topbar } from '../components/ui';
import { OS_STATUS, type Cliente, type Usuario } from '../lib/types';

interface FormState {
  clientes_id: string;
  usuarios_id: string;
  status: string;
  dataInicial: string;
  dataFinal: string;
  descricaoProduto: string;
  defeito: string;
  observacoes: string;
  laudoTecnico: string;
  garantia: string;
}

function emptyForm(userId: string): FormState {
  const today = todayIso();
  return {
    clientes_id: '',
    usuarios_id: userId,
    status: 'Aberto',
    dataInicial: today,
    dataFinal: today,
    descricaoProduto: '',
    defeito: '',
    observacoes: '',
    laudoTecnico: '',
    garantia: '',
  };
}

export function OsFormScreen({ id }: { id?: string }) {
  const { baseUrl, token, session, handle } = useApi();
  const { can } = useApp();
  const editing = Boolean(id);

  const [form, setForm] = useState<FormState>(() => emptyForm(String(session.user.idUsuarios)));
  const [clientes, setClientes] = useState<Cliente[]>([]);
  const [usuarios, setUsuarios] = useState<Usuario[]>([]);
  const [clienteBusca, setClienteBusca] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const set = <K extends keyof FormState>(key: K, value: FormState[K]) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  useEffect(() => {
    const timer = window.setTimeout(async () => {
      try {
        const list = await handle(() => api.listClientes(baseUrl, token, { search: clienteBusca.trim() || undefined, perPage: 30 }));
        setClientes(list);
      } catch {
        setClientes([]);
      }
    }, 300);
    return () => window.clearTimeout(timer);
  }, [baseUrl, token, clienteBusca, handle]);

  useEffect(() => {
    (async () => {
      setLoading(true);
      setError(null);
      try {
        if (can('cUsuario')) {
          try {
            const list = await handle(() => api.listUsuarios(baseUrl, token));
            setUsuarios(list);
          } catch {
            setUsuarios([]);
          }
        }
        if (editing && id) {
          const os = await handle(() => api.getOs(baseUrl, token, id));
          setForm({
            clientes_id: String(os.clientes_id),
            usuarios_id: String(os.usuarios_id),
            status: os.status,
            dataInicial: String(os.dataInicial).slice(0, 10),
            dataFinal: String(os.dataFinal).slice(0, 10),
            descricaoProduto: os.descricaoProduto ?? '',
            defeito: os.defeito ?? '',
            observacoes: os.observacoes ?? '',
            laudoTecnico: os.laudoTecnico ?? '',
            garantia: os.garantia ?? '',
          });
          // garante que o cliente atual apareça no seletor mesmo fora da busca
          if (os.nomeCliente) {
            setClientes((prev) =>
              prev.some((c) => String(c.idClientes) === String(os.clientes_id))
                ? prev
                : [...prev, { idClientes: os.clientes_id, nomeCliente: os.nomeCliente! }],
            );
          }
        }
      } catch (err) {
        setError((err as Error).message);
      } finally {
        setLoading(false);
      }
    })();
  }, [baseUrl, token, editing, id, can, handle]);

  const clienteSelecionado = useMemo(
    () => clientes.find((c) => String(c.idClientes) === form.clientes_id),
    [clientes, form.clientes_id],
  );

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);

    if (!form.clientes_id) {
      setError('Selecione o cliente da OS.');
      return;
    }
    if (!form.usuarios_id) {
      setError('Selecione o técnico responsável.');
      return;
    }

    setSaving(true);
    try {
      const payload = {
        clientes_id: form.clientes_id,
        usuarios_id: form.usuarios_id,
        status: form.status,
        dataInicial: toApiDate(form.dataInicial),
        dataFinal: toApiDate(form.dataFinal),
        descricaoProduto: form.descricaoProduto,
        defeito: form.defeito,
        observacoes: form.observacoes,
        laudoTecnico: form.laudoTecnico,
        garantia: form.garantia,
      };

      if (editing && id) {
        await handle(() => api.updateOs(baseUrl, token, id, payload));
        navigate(`os/${id}`);
      } else {
        const created = await handle(() => api.createOs(baseUrl, token, payload));
        navigate(`os/${created.idOs}`);
      }
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="screen">
      <Topbar
        title={editing ? `Editar OS #${id}` : 'Nova OS'}
        subtitle="Os campos com * são obrigatórios"
        onBack={() => (editing && id ? navigate(`os/${id}`) : navigate('os'))}
      />

      <div className="content">
        {loading ? <SkeletonList count={4} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}

        {!loading ? (
          <form className="stack" onSubmit={submit}>
            <Card>
              <div className="stack">
                <Field label="Buscar cliente" hint="Digite para buscar por nome, documento ou telefone">
                  <Input
                    type="search"
                    placeholder="Buscar cliente…"
                    value={clienteBusca}
                    onChange={(e) => setClienteBusca(e.target.value)}
                  />
                </Field>
                <Field label="Cliente *">
                  <Select value={form.clientes_id} onChange={(e) => set('clientes_id', e.target.value)} required>
                    <option value="">Selecione o cliente</option>
                    {clientes.map((c) => (
                      <option key={c.idClientes} value={String(c.idClientes)}>
                        {c.nomeCliente}
                        {c.documento ? ` — ${c.documento}` : ''}
                      </option>
                    ))}
                  </Select>
                </Field>
                {clienteSelecionado?.celular ? (
                  <p className="card__meta">Celular: {clienteSelecionado.celular}</p>
                ) : null}
              </div>
            </Card>

            <Card>
              <div className="stack">
                {usuarios.length > 0 ? (
                  <Field label="Técnico responsável *">
                    <Select value={form.usuarios_id} onChange={(e) => set('usuarios_id', e.target.value)} required>
                      <option value="">Selecione</option>
                      {usuarios.map((u) => (
                        <option key={u.idUsuarios} value={String(u.idUsuarios)}>
                          {u.nome}
                        </option>
                      ))}
                    </Select>
                  </Field>
                ) : (
                  <Field label="ID do técnico *" hint="Você pode informar o ID do usuário responsável">
                    <Input
                      inputMode="numeric"
                      value={form.usuarios_id}
                      onChange={(e) => set('usuarios_id', e.target.value)}
                      required
                    />
                  </Field>
                )}

                <Field label="Situação da OS">
                  <Select value={form.status} onChange={(e) => set('status', e.target.value)}>
                    {OS_STATUS.map((st) => (
                      <option key={st} value={st}>
                        {st}
                      </option>
                    ))}
                  </Select>
                </Field>

                <div className="grid-2">
                  <Field label="Data inicial *">
                    <Input type="date" value={form.dataInicial} onChange={(e) => set('dataInicial', e.target.value)} required />
                  </Field>
                  <Field label="Data final">
                    <Input type="date" value={form.dataFinal} onChange={(e) => set('dataFinal', e.target.value)} />
                  </Field>
                </div>
              </div>
            </Card>

            <Card>
              <div className="stack">
                <Field label="Produto / descrição">
                  <Textarea value={form.descricaoProduto} onChange={(e) => set('descricaoProduto', e.target.value)} />
                </Field>
                <Field label="Defeito relatado">
                  <Textarea value={form.defeito} onChange={(e) => set('defeito', e.target.value)} />
                </Field>
                <Field label="Observações">
                  <Textarea value={form.observacoes} onChange={(e) => set('observacoes', e.target.value)} />
                </Field>
                <Field label="Laudo técnico">
                  <Textarea value={form.laudoTecnico} onChange={(e) => set('laudoTecnico', e.target.value)} />
                </Field>
              </div>
            </Card>

            <div className="sticky-actions">
              <Button variant="ghost" block small onClick={() => navigate(editing && id ? `os/${id}` : 'os')}>
                Cancelar
              </Button>
              <Button type="submit" block loading={saving}>
                {editing ? 'Salvar' : 'Criar OS'}
              </Button>
            </div>
          </form>
        ) : null}
      </div>
    </div>
  );
}
