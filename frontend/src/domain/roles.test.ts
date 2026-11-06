// Prise en main : Assek ; décisions de navigation sans attribuer de droits au navigateur.
import { describe, expect, it } from 'vitest';
import type { Membership, User } from './types';
import { canAddRestaurant, firstRestaurant, workspaceFor } from './roles';

function account(role?: Membership['role'], active = true): User {
  return {
    id: 1, name: 'Compte de test', email: 'compte@example.test', phone: '', delivery_address: null,
    ranking_opt_in: false, public_alias: null, analytics_consent: false,
    memberships: role ? [{ id: 1, user_id: 1, restaurant_id: 7, role, active }] : [],
  };
}

describe('Espaces par rôle', () => {
  it('dirige les visiteurs et clients vers la carte', () => {
    expect(workspaceFor(null)).toBe('carte');
    expect(workspaceFor(account())).toBe('carte');
    expect(canAddRestaurant(account())).toBe(false);
  });
  it('sépare le service de la gestion', () => {
    expect(workspaceFor(account('employee'))).toBe('service');
    expect(canAddRestaurant(account('employee'))).toBe(false);
    expect(workspaceFor(account('restaurant_admin'))).toBe('administration');
    expect(canAddRestaurant(account('restaurant_admin'))).toBe(true);
    expect(canAddRestaurant(account('manager'))).toBe(false);
  });
  it('ignore un rattachement révoqué et sélectionne le nouveau restaurant autorisé', () => {
    expect(workspaceFor(account('restaurant_admin', false))).toBe('carte');
    expect(canAddRestaurant(account('restaurant_admin', false))).toBe(false);
    expect(firstRestaurant(account('restaurant_admin').memberships ?? [], '7')).toBe('7');
    expect(firstRestaurant([], '999')).toBe('');
  });
});
