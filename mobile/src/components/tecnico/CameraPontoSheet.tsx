/**
 * Painel de configuração de uma câmera já prevista no projeto: IP, NVR, canal,
 * modelo, série e MAC. A senha digitada aqui só existe no aparelho, para o
 * NVR buscar a imagem; ela não vai para a API do MAPOS.
 */

import { Button, Field, Input, Select, Sheet } from '../ui';
import type { CameraPonto, CanalNvr, EnvioCamera } from '../../lib/types-camera';

export function CameraPontoSheet({
  camera,
  form,
  senha,
  nvrs,
  canaisLivres,
  nvrLido,
  busy,
  onForm,
  onSenha,
  onFechar,
  onSalvar,
}: {
  camera: CameraPonto | null;
  form: EnvioCamera;
  senha: string;
  nvrs: CameraPonto[];
  canaisLivres: CanalNvr[];
  nvrLido: boolean;
  busy: boolean;
  onForm: (atualizar: (f: EnvioCamera) => EnvioCamera) => void;
  onSenha: (valor: string) => void;
  onFechar: () => void;
  onSalvar: () => void;
}) {
  return (
    <Sheet
      open={Boolean(camera)}
      title={camera ? `Configurar ${camera.identificacao}` : 'Configurar câmera'}
      onClose={onFechar}
      footer={
        <>
          <Button variant="ghost" block onClick={onFechar}>
            Cancelar
          </Button>
          <Button block loading={busy} onClick={onSalvar}>
            Salvar
          </Button>
        </>
      }
    >
      <Field label="IP da câmera" hint="Use o IP que aparece na busca da rede.">
        <Input
          value={String(form.ip ?? '')}
          inputMode="decimal"
          onChange={(e) => onForm((f) => ({ ...f, ip: e.target.value }))}
        />
      </Field>

      <Field label="NVR">
        <Select
          value={String(form.nvr_id ?? '')}
          onChange={(e) => onForm((f) => ({ ...f, nvr_id: e.target.value ? Number(e.target.value) : null }))}
        >
          <option value="">Sem NVR</option>
          {nvrs.map((n) => (
            <option key={n.idPonto} value={String(n.idPonto)}>
              {n.identificacao}
              {n.ip ? ` · ${n.ip}` : ''}
            </option>
          ))}
        </Select>
      </Field>

      {nvrLido ? (
        <Field label="Canal livre no NVR" hint="Canais já ocupados não aparecem.">
          <Select
            value={String(form.canal ?? '')}
            onChange={(e) => onForm((f) => ({ ...f, canal: e.target.value }))}
          >
            <option value="">Escolha o canal</option>
            {canaisLivres.map((c) => (
              <option key={c.id} value={String(c.id)}>
                Canal {c.id}
                {c.nome ? ` · ${c.nome}` : ''}
              </option>
            ))}
          </Select>
        </Field>
      ) : (
        <Field label="Canal no NVR" hint="Conecte ao NVR para ver a lista de canais livres.">
          <Input
            value={String(form.canal ?? '')}
            inputMode="numeric"
            placeholder="Ex.: 5"
            onChange={(e) => onForm((f) => ({ ...f, canal: e.target.value.replace(/[^\d]/g, '') }))}
          />
        </Field>
      )}

      <div className="grid-2">
        <Field label="Modelo">
          <Input value={String(form.modelo ?? '')} onChange={(e) => onForm((f) => ({ ...f, modelo: e.target.value }))} />
        </Field>
        <Field label="Série">
          <Input value={String(form.serie ?? '')} onChange={(e) => onForm((f) => ({ ...f, serie: e.target.value }))} />
        </Field>
      </div>

      <Field label="MAC">
        <Input value={String(form.mac ?? '')} onChange={(e) => onForm((f) => ({ ...f, mac: e.target.value }))} />
      </Field>

      {nvrLido ? (
        <Field label="Senha da câmera" hint="Só é usada no aparelho, para o NVR acessar a imagem.">
          <Input
            type="password"
            autoComplete="off"
            value={senha}
            onChange={(e) => onSenha(e.target.value)}
          />
        </Field>
      ) : null}
    </Sheet>
  );
}
