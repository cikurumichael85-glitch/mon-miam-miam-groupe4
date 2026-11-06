// Prise en main : Assek ; expiration de session et suppression des données privées en mémoire.
import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
import { AppProvider, useApp } from './AppContext';
import { request } from '../services/http';

vi.mock('../services/http', async (importOriginal) => ({
  ...await importOriginal<typeof import('../services/http')>(),
  request: vi.fn(),
}));

function SessionProbe() {
  const app = useApp();
  return (
    <>
      <output aria-label="Identité de session">{app.user?.name ?? 'Visiteur'}</output>
      <output aria-label="Nombre d’articles">{app.cart.lines.length}</output>
      <button onClick={() => app.dispatch({ type: 'add', item: {
        id: 1, restaurant_id: 1, category_id: 1, name: 'Plat fictif', description: '', price: '2500.00', available: true, is_daily: false, version: 1,
      } })}>Ajouter un article fictif</button>
    </>
  );
}

beforeEach(() => vi.resetAllMocks());

it('retire l’identité et le panier privés dès que la session expire', async () => {
  vi.mocked(request).mockResolvedValue({ data: { id: 1, name: 'Client fictif' } });
  render(<AppProvider mode="api"><SessionProbe /></AppProvider>);
  expect(await screen.findByText('Client fictif')).toBeInTheDocument();
  fireEvent.click(screen.getByRole('button', { name: 'Ajouter un article fictif' }));
  expect(screen.getByLabelText('Nombre d’articles')).toHaveTextContent('1');
  fireEvent(window, new Event('mmm:session-expired'));
  expect(screen.getByLabelText('Identité de session')).toHaveTextContent('Visiteur');
  expect(screen.getByLabelText('Nombre d’articles')).toHaveTextContent('0');
});
