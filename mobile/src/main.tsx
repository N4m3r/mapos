import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';
import { App } from './App';

const container = document.getElementById('root');
if (!container) throw new Error('Elemento #root não encontrado');

createRoot(container).render(
  <StrictMode>
    <App />
  </StrictMode>,
);

// PWA: cache do shell para abertura offline (dados continuam vindo da API).
// No APK os arquivos já são locais — service worker não acrescenta nada.
const isNative = Boolean(
  (window as unknown as { Capacitor?: { isNativePlatform?: () => boolean } }).Capacitor?.isNativePlatform?.(),
);

if ('serviceWorker' in navigator && import.meta.env.PROD && !isNative) {
  window.addEventListener('load', () => {
    // sw.js fica na raiz do dist (public/), não junto dos chunks em assets/.
    navigator.serviceWorker.register(`${import.meta.env.BASE_URL}sw.js`, { scope: './' }).catch(() => {
      /* sem service worker o app continua funcionando online */
    });
  });
}
