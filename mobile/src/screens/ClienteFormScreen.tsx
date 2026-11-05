/** Cadastro rápido de cliente (subset de campos aceitos por /api/v1/clientes). */

import { useState } from 'react';
import type { FormEvent } from 'react';
import * as api from '../lib/api';
import { useApi } from '../lib/store';
import { navigate } from '../lib/router';
import { Alert, Button, Card, Field, Input, Topbar } from '../components/ui';

export function ClienteFormScreen() {
  const { baseUrl, token, handle } = useApi();
  const [form, setForm] = useState({
    nomeCliente: '',
    documento: '',
    telefone: '',
    celular: '',
    email: '',
    cep: '',
    rua: '',
    numero: '',
    bairro: '',
    cidade: '',
    estado: '',
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const set = (key: keyof typeof form) => (e: { target: { value: string } }) =>
    setForm((prev) => ({ ...prev, [key]: e.target.value }));

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    if (!form.nomeCliente.trim()) {
      setError('Informe o nome do cliente.');
      return;
    }
    setSaving(true);
    try {
      const created = await handle(() => api.createCliente(baseUrl, token, form));
      navigate(`clientes/${created.idClientes}`);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="screen">
      <Topbar title="Novo cliente" onBack={() => navigate('clientes')} />

      <div className="content">
        {error ? <Alert kind="error">{error}</Alert> : null}

        <form className="stack" onSubmit={submit}>
          <Card>
            <div className="stack">
              <Field label="Nome *">
                <Input value={form.nomeCliente} onChange={set('nomeCliente')} required autoComplete="name" />
              </Field>
              <Field label="CPF/CNPJ">
                <Input inputMode="numeric" value={form.documento} onChange={set('documento')} />
              </Field>
              <div className="grid-2">
                <Field label="Telefone">
                  <Input inputMode="tel" value={form.telefone} onChange={set('telefone')} />
                </Field>
                <Field label="Celular">
                  <Input inputMode="tel" value={form.celular} onChange={set('celular')} />
                </Field>
              </div>
              <Field label="E-mail">
                <Input type="email" inputMode="email" value={form.email} onChange={set('email')} autoComplete="email" />
              </Field>
            </div>
          </Card>

          <Card>
            <div className="stack">
              <p className="section-title">Endereço</p>
              <div className="grid-2">
                <Field label="CEP">
                  <Input inputMode="numeric" value={form.cep} onChange={set('cep')} />
                </Field>
                <Field label="Número">
                  <Input value={form.numero} onChange={set('numero')} />
                </Field>
              </div>
              <Field label="Rua">
                <Input value={form.rua} onChange={set('rua')} autoComplete="address-line1" />
              </Field>
              <Field label="Bairro">
                <Input value={form.bairro} onChange={set('bairro')} />
              </Field>
              <div className="grid-2">
                <Field label="Cidade">
                  <Input value={form.cidade} onChange={set('cidade')} />
                </Field>
                <Field label="UF">
                  <Input maxLength={2} value={form.estado} onChange={set('estado')} />
                </Field>
              </div>
            </div>
          </Card>

          <div className="sticky-actions">
            <Button variant="ghost" block small onClick={() => navigate('clientes')}>
              Cancelar
            </Button>
            <Button type="submit" block loading={saving}>
              Salvar
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
