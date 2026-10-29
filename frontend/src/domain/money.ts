// Prise en main : Michael ; relecture croisée du binôme B.
// Les centimes sont des entiers ; aucun calcul de panier ne passe par un flottant.
export function minor(value: string): number {
  if (!/^\d{1,13}(\.\d{1,2})?$/.test(value)) {
    throw new Error('Montant CDF invalide.');
  }
  const [whole, fraction = ''] = value.split('.');
  const result = Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
  if (!Number.isSafeInteger(result)) {
    throw new Error('Montant hors limites.');
  }
  return result;
}

export function decimal(value: number): string {
  if (!Number.isSafeInteger(value) || value < 0) {
    throw new Error('Montant hors limites.');
  }
  return `${Math.floor(value / 100)}.${String(value % 100).padStart(2, '0')}`;
}

export function money(value: string): string {
  const amount = minor(value);
  const whole = new Intl.NumberFormat('fr-CD').format(Math.floor(amount / 100));
  const fraction = amount % 100;
  return `${whole}${fraction ? `,${String(fraction).padStart(2, '0')}` : ''} FC`;
}
