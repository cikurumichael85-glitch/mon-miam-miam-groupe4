// Prise en main : Michael ; relecture croisée du binôme B.
import { describe, expect, it } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { AppProvider, useApp } from '../state/AppContext';
import { Explore } from './Explore';

function CartProbe() {
  const { cart } = useApp();
  return (
    <output aria-label="Articles dans le panier">
      {cart.lines.reduce((sum, line) => sum + line.quantity, 0)}
    </output>
  );
}

describe('Catalogue de démonstration', () => {
  it('conserve le panier du premier restaurant et refuse un mélange avec le second', async () => {
    render(<AppProvider mode="demo"><Explore home={false} /><CartProbe /></AppProvider>);
    fireEvent.click(await screen.findByRole('button', { name: 'Ajouter Poulet à la moambe au panier' }));
    expect(screen.getByLabelText('Articles dans le panier')).toHaveTextContent('1');
    fireEvent.click(screen.getByRole('button', { name: /L’Atelier/ }));
    fireEvent.click(await screen.findByRole('button', { name: 'Ajouter Burger maison au panier' }));
    expect(screen.getByLabelText('Articles dans le panier')).toHaveTextContent('1');
    await waitFor(() => expect(screen.queryByRole('button', { name: 'Poulet à la moambe' })).not.toBeInTheDocument());
  });
  it('change de restaurant et filtre les boissons', async () => {
    render(
      <AppProvider mode="demo">
        <Explore home={false} />
        <CartProbe />
      </AppProvider>,
    );
    await screen.findByRole('button', { name: 'Poulet à la moambe' });
    fireEvent.click(screen.getByRole('button', { name: /L’Atelier/ }));
    await screen.findByRole('button', { name: 'Burger maison' });
    await waitFor(() =>
      expect(screen.queryByRole('button', { name: 'Poulet à la moambe' })).not.toBeInTheDocument(),
    );
    fireEvent.click(screen.getByRole('button', { name: 'Boissons' }));
    await screen.findByRole('button', { name: 'Eau plate · 50 cl' });
    expect(screen.queryByRole('button', { name: 'Burger maison' })).not.toBeInTheDocument();
  });
  it('cherche un plat et permet son ajout au panier', async () => {
    render(
      <AppProvider mode="demo">
        <Explore home={false} />
        <CartProbe />
      </AppProvider>,
    );
    await screen.findByRole('button', { name: 'Poulet à la moambe' });
    fireEvent.change(screen.getByRole('searchbox'), { target: { value: 'moambe' } });
    fireEvent.click(screen.getByRole('button', { name: 'Ajouter Poulet à la moambe au panier' }));
    expect(screen.queryByRole('button', { name: 'Poisson à la braise' })).not.toBeInTheDocument();
    expect(screen.getByLabelText('Articles dans le panier')).toHaveTextContent('1');
  });
});
