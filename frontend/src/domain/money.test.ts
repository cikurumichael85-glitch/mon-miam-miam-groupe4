// Prise en main : Michael ; relecture croisée du binôme B.
import { describe, expect, it } from 'vitest';
import { decimal, minor, money } from './money';

describe('Montants en francs congolais', () => {
  it('conserve les deux décimales sans conversion de devise', () => {
    expect(minor('2500.50')).toBe(250050);
    expect(decimal(250050)).toBe('2500.50');
  });
  it('additionne 0.10 et 0.20 sans erreur flottante', () => {
    expect(decimal(minor('0.10') + minor('0.20'))).toBe('0.30');
  });
  it('formate les montants en FC', () => {
    expect(money('15000.00').replace(/[\s\u202f\u00a0]/g, '')).toBe('15000FC');
    expect(money('15000.50')).toContain(',50');
  });
  it.each(['-1.00', '1e3', '12,50', '0.001', 'abc'])(
    'refuse le montant non canonique %s',
    (value) => {
      expect(() => minor(value)).toThrow();
    },
  );
  it('refuse les unités mineures non entières', () => {
    expect(() => decimal(0.1)).toThrow();
  });
});
