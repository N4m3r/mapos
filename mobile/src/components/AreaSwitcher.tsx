/** Seletor de área para quem tem mais de uma (sistema, técnico, RH). */

import { ROTULO_AREA } from '../lib/area';
import { useApp } from '../lib/store';

export function AreaSwitcher() {
  const { area, areas, trocarArea } = useApp();
  if (areas.length < 2) return null;

  return (
    <div className="area-switcher" role="tablist" aria-label="Área do aplicativo">
      {areas.map((a) => (
        <button
          key={a}
          type="button"
          role="tab"
          aria-selected={a === area}
          className={a === area ? 'area-switcher__item area-switcher__item--ativo' : 'area-switcher__item'}
          onClick={() => a !== area && trocarArea(a)}
        >
          {ROTULO_AREA[a]}
        </button>
      ))}
    </div>
  );
}
