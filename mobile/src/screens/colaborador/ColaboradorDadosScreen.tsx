/** Meus dados: contato e PIX do colaborador (o que o RH libera editar). */

import { useState } from 'react';
import * as col from '../../lib/api-colaborador';
import { useCarga, useFotoPerfil } from '../../lib/hooks';
import { useApi, useApp } from '../../lib/store';
import { navigate } from '../../lib/router';
import { dateBr } from '../../lib/format';
import { Alert, Button, Card, Field, Input, Kv, Select, SkeletonList, Topbar } from '../../components/ui';
import type { MeusDados } from '../../lib/types-tecnico';

const PIX_TIPOS = [
  { key: '', label: 'Não informar' },
  { key: 'cpf', label: 'CPF' },
  { key: 'cnpj', label: 'CNPJ' },
  { key: 'email', label: 'E-mail' },
  { key: 'telefone', label: 'Telefone' },
  { key: 'aleatoria', label: 'Chave aleatória' },
];

export function ColaboradorDadosScreen() {
  const { baseUrl, token, handle } = useApi();
  const { signOut } = useApp();
  const { data, loading, error, setData, recarregar } = useCarga<MeusDados>((url, tk) => col.meusDados(url, tk));
  const foto = useFotoPerfil(data?.fotoUrl);

  const [editando, setEditando] = useState(false);
  const [email, setEmail] = useState('');
  const [celular, setCelular] = useState('');
  const [pixTipo, setPixTipo] = useState('');
  const [pixChave, setPixChave] = useState('');
  const [busy, setBusy] = useState(false);
  const [erro, setErro] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  const abrirEdicao = () => {
    setEmail(data?.email ?? '');
    setCelular(data?.celular ?? '');
    setPixTipo(data?.pix_tipo ?? '');
    setPixChave(data?.pix_chave ?? '');
    setEditando(true);
    setErro(null);
    setOk(null);
  };

  const salvar = async () => {
    setBusy(true);
    setErro(null);
    try {
      const atualizado = await handle(() =>
        col.salvarDados(baseUrl, token, {
          email: email.trim(),
          celular: celular.replace(/\D/g, ''),
          pix_tipo: pixTipo,
          pix_chave: pixChave.trim(),
        }),
      );
      setData({ ...data!, ...atualizado });
      setOk('Dados atualizados.');
      setEditando(false);
      recarregar();
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="screen">
      <Topbar title="Meus dados" onBack={() => navigate('colaborador')} />

      <div className="content">
        {loading && !data ? <SkeletonList count={4} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}
        {erro ? <Alert kind="error">{erro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {data ? (
          <>
            <Card>
              <div className="row">
                <span className="foto-perfil" style={{ width: 60, height: 60 }}>
                  {foto ? <img src={foto} alt="" /> : data.nome.slice(0, 2).toUpperCase()}
                </span>
                <div style={{ minWidth: 0 }}>
                  <p className="card__title">{data.nome}</p>
                  <p className="card__meta">{data.cargo || 'Colaborador'}</p>
                </div>
              </div>
              <Kv k="Admissão" v={data.admissao ? dateBr(data.admissao) : '—'} />
              <Kv k="Unidade" v={data.unidade || '—'} />
            </Card>

            {!editando ? (
              <Card>
                <div className="section-head">
                  <p className="section-title">Contato e PIX</p>
                  <button type="button" className="reset-button" onClick={abrirEdicao}>
                    Editar
                  </button>
                </div>
                <Kv k="E-mail" v={data.email || '—'} />
                <Kv k="Celular" v={data.celular || '—'} />
                <Kv k="Tipo de PIX" v={PIX_TIPOS.find((p) => p.key === data.pix_tipo)?.label ?? '—'} />
                <Kv k="Chave PIX" v={data.pix_chave || '—'} />
              </Card>
            ) : (
              <Card>
                <div className="stack">
                  <Field label="E-mail">
                    <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoCapitalize="none" />
                  </Field>
                  <Field label="Celular" hint="DDD + número">
                    <Input value={celular} onChange={(e) => setCelular(e.target.value)} inputMode="tel" />
                  </Field>
                  <Field label="Tipo de chave PIX">
                    <Select value={pixTipo} onChange={(e) => setPixTipo(e.target.value)}>
                      {PIX_TIPOS.map((p) => (
                        <option key={p.key} value={p.key}>
                          {p.label}
                        </option>
                      ))}
                    </Select>
                  </Field>
                  <Field label="Chave PIX">
                    <Input value={pixChave} onChange={(e) => setPixChave(e.target.value)} maxLength={140} />
                  </Field>
                  <div className="actionbar">
                    <Button variant="ghost" block onClick={() => setEditando(false)}>
                      Cancelar
                    </Button>
                    <Button block loading={busy} onClick={() => void salvar()}>
                      Salvar
                    </Button>
                  </div>
                </div>
              </Card>
            )}

            <p className="card__meta">
              Nome, cargo, salário e jornada são mantidos pelo RH. Para corrigir algo, procure o RH.
            </p>

            <Button variant="ghost" block onClick={signOut}>
              Sair da conta
            </Button>
          </>
        ) : null}
      </div>
    </div>
  );
}
