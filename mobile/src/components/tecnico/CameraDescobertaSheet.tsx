/**
 * Configuração de uma câmera recém-descoberta na rede.
 *
 * Sequência de campo: câmera nova sai da fábrica em 192.168.1.64 e sem senha.
 * Aqui o técnico ativa a câmera, escolhe um IP livre da rede, aplica o IP e
 * liga a câmera a um canal do NVR — tudo antes de gravar no projeto.
 */

import { useEffect, useState } from 'react';
import { Alert, Button, Field, Input, Select, Sheet } from '../ui';
import { Hikvision } from '../../lib/hikvision';
import { ipValido, sugerirIpLivre } from '../../lib/rede';
import type { CameraPonto, CanalNvr, DispositivoDescoberto, EnvioCamera } from '../../lib/types-camera';

export function CameraDescobertaSheet({
  dispositivo,
  nvr,
  prefixo,
  usados,
  canaisLivres,
  nvrCredenciais,
  onFechar,
  onCadastrar,
}: {
  dispositivo: DispositivoDescoberto | null;
  nvr: CameraPonto | null;
  prefixo: string | null;
  usados: string[];
  canaisLivres: CanalNvr[];
  nvrCredenciais: { usuario: string; senha: string } | null;
  onFechar: () => void;
  onCadastrar: (dados: EnvioCamera) => Promise<void>;
}) {
  const [senha, setSenha] = useState('');
  const [novoIp, setNovoIp] = useState('');
  const [canal, setCanal] = useState('');
  const [ativada, setAtivada] = useState(false);
  const [ipAplicado, setIpAplicado] = useState<string | null>(null);
  const [busy, setBusy] = useState<'ativar' | 'ip' | 'salvar' | null>(null);
  const [erro, setErro] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  useEffect(() => {
    if (!dispositivo) return;
    setSenha('');
    setCanal('');
    setAtivada(dispositivo.ativado);
    setIpAplicado(null);
    setErro(null);
    setOk(null);
    setNovoIp(sugerirIpLivre(prefixo, [dispositivo.ip, ...usados]) ?? '');
    // `usados` é um array novo a cada render do pai; a chave evita resetar
    // o IP digitado quando o pai atualiza qualquer outro estado.
  }, [dispositivo, prefixo, usados.join(',')]);

  const ipAtual = ipAplicado ?? dispositivo?.ip ?? '';

  const ativar = async () => {
    if (!dispositivo) return;
    if (senha.length < 8) {
      setErro('A Hikvision exige senha de 8 a 16 caracteres, com letras e números.');
      return;
    }
    setBusy('ativar');
    setErro(null);
    try {
      await Hikvision.ativarCamera({ ip: dispositivo.ip, senha, porta: dispositivo.porta });
      setAtivada(true);
      setOk('Câmera ativada. Agora aplique o IP do projeto.');
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(null);
    }
  };

  const aplicarIp = async () => {
    if (!dispositivo) return;
    if (!ipValido(novoIp)) {
      setErro('IP inválido. Use o formato 192.168.1.100.');
      return;
    }
    if (!senha) {
      setErro('Informe a senha da câmera para alterar o IP.');
      return;
    }
    setBusy('ip');
    setErro(null);
    try {
      await Hikvision.definirRedeCamera({
        ip: ipAtual,
        usuario: 'admin',
        senha,
        porta: dispositivo.porta,
        novoIp,
        mascara: '255.255.255.0',
      });
      setIpAplicado(novoIp);
      setOk(`IP da câmera alterado para ${novoIp}.`);
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(null);
    }
  };

  const cadastrar = async () => {
    if (!dispositivo) return;
    setBusy('salvar');
    setErro(null);
    try {
      let modelo = dispositivo.modelo;
      let serie = dispositivo.serie;
      let mac = dispositivo.mac;
      let firmware = dispositivo.firmware;

      // Com a senha em mãos, a identificação definitiva vem do ISAPI.
      if (senha) {
        try {
          const info = await Hikvision.lerCamera({
            ip: ipAtual,
            usuario: 'admin',
            senha,
            porta: dispositivo.porta,
          });
          modelo = info.modelo || modelo;
          serie = info.serie || serie;
          mac = info.mac || mac;
          firmware = info.firmware || firmware;
        } catch {
          // segue com o que a varredura já sabia
        }
      }

      let avisoCanal: string | null = null;
      if (nvr?.ip && nvrCredenciais?.senha && canal) {
        try {
          await Hikvision.adicionarCanal({
            nvr: { ip: nvr.ip, usuario: nvrCredenciais.usuario || 'admin', senha: nvrCredenciais.senha },
            canal: Number(canal),
            cameraIp: ipAtual,
            cameraUsuario: 'admin',
            cameraSenha: senha,
          });
        } catch (err) {
          avisoCanal = `O NVR não aceitou a câmera no canal ${canal}: ${(err as Error).message}`;
        }
      }

      await onCadastrar({
        ip: ipAtual,
        mac,
        modelo,
        serie,
        firmware,
        sadp_id: dispositivo.sadpId,
        ativada: true,
        canal: canal || undefined,
        nvr_id: nvr ? nvr.idPonto : null,
        status: 'configurado',
        observacao: avisoCanal,
      });
      if (avisoCanal) setErro(avisoCanal);
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(null);
    }
  };

  return (
    <Sheet
      open={Boolean(dispositivo)}
      title={dispositivo ? `Câmera ${dispositivo.ip}` : 'Câmera encontrada'}
      onClose={onFechar}
      footer={
        <>
          <Button variant="ghost" block onClick={onFechar}>
            Fechar
          </Button>
          <Button block loading={busy === 'salvar'} disabled={!ativada} onClick={() => void cadastrar()}>
            Cadastrar no projeto
          </Button>
        </>
      }
    >
      {erro ? <Alert kind="error">{erro}</Alert> : null}
      {ok ? <Alert kind="ok">{ok}</Alert> : null}
      {!ativada ? (
        <Alert kind="info">
          Esta câmera ainda está de fábrica. Defina a senha inicial para ativá-la; até lá ela não
          grava no NVR.
        </Alert>
      ) : null}

      {dispositivo ? (
        <div className="stack" style={{ gap: 4 }}>
          <div className="kv">
            <span className="kv__k">Modelo</span>
            <span className="kv__v">{dispositivo.modelo || '—'}</span>
          </div>
          <div className="kv">
            <span className="kv__k">Série</span>
            <span className="kv__v">{dispositivo.serie || '—'}</span>
          </div>
          <div className="kv">
            <span className="kv__k">MAC</span>
            <span className="kv__v">{dispositivo.mac || '—'}</span>
          </div>
          <div className="kv">
            <span className="kv__k">IP atual</span>
            <span className="kv__v">{ipAtual || '—'}</span>
          </div>
          <div className="kv">
            <span className="kv__k">Firmware</span>
            <span className="kv__v">{dispositivo.firmware || '—'}</span>
          </div>
        </div>
      ) : null}

      <Field
        label="Senha da câmera"
        hint="Fica só no aparelho; o MAPOS não recebe esta senha."
      >
        <Input
          type="password"
          autoComplete="off"
          value={senha}
          onChange={(e) => setSenha(e.target.value)}
        />
      </Field>

      {!ativada ? (
        <Button block loading={busy === 'ativar'} onClick={() => void ativar()}>
          Ativar câmera com esta senha
        </Button>
      ) : null}

      <Field label="IP do projeto" hint="Um endereço livre na mesma faixa da rede.">
        <Input
          value={novoIp}
          inputMode="decimal"
          onChange={(e) => setNovoIp(e.target.value)}
          placeholder="Ex.: 192.168.1.100"
        />
      </Field>
      <Button
        block
        variant="ghost"
        loading={busy === 'ip'}
        disabled={!ativada || novoIp === ipAtual}
        onClick={() => void aplicarIp()}
      >
        {ipAplicado ? `IP aplicado: ${ipAplicado}` : 'Aplicar IP na câmera'}
      </Button>

      {nvr ? (
        <Field label={`Canal no ${nvr.identificacao}`} hint="Só canais livres aparecem.">
          <Select value={canal} onChange={(e) => setCanal(e.target.value)}>
            <option value="">Escolher depois</option>
            {canaisLivres.map((c) => (
              <option key={c.id} value={String(c.id)}>
                Canal {c.id}
                {c.nome ? ` · ${c.nome}` : ''}
              </option>
            ))}
          </Select>
        </Field>
      ) : null}
    </Sheet>
  );
}
