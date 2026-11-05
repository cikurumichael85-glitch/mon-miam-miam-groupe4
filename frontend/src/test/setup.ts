// Prise en main : Assek ; relecture croisée du binôme B.
import '@testing-library/jest-dom/vitest';
import { afterEach } from 'vitest';
import { cleanup } from '@testing-library/react';

// JSDOM ne fournit pas la couche modale native ; conserver son état ouvert pour les tests.
if (typeof HTMLDialogElement.prototype.showModal !== 'function') {
  Object.defineProperty(HTMLDialogElement.prototype, 'showModal', {
    configurable: true,
    value: function (this: HTMLDialogElement) {
      this.setAttribute('open', '');
    },
  });
}
if (typeof HTMLDialogElement.prototype.close !== 'function') {
  Object.defineProperty(HTMLDialogElement.prototype, 'close', {
    configurable: true,
    value: function (this: HTMLDialogElement) {
      this.removeAttribute('open');
    },
  });
}

afterEach(() => {
  cleanup();
});
