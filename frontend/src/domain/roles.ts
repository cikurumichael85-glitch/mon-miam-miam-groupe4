// Prise en main : Assek ; relecture croisée du binôme B.
import type { Membership, Role, User } from './types';

export const roleLabels: Record<Role, string> = {
  employee: 'Serveur',
  manager: 'Gérant',
  restaurant_admin: 'Administrateur',
};

export function activeMemberships(user: User | null): Membership[] {
  return (user?.memberships ?? []).filter((membership) => membership.active);
}

export function firstRestaurant(memberships: Membership[], preferred?: string): string {
  const selected = memberships.find((membership) => String(membership.restaurant_id) === preferred);
  return String((selected ?? memberships[0])?.restaurant_id ?? '');
}

export function workspaceFor(user: User | null): 'administration' | 'service' | 'carte' {
  const memberships = activeMemberships(user);
  if (user?.platform_access || memberships.some((row) => row.role !== 'employee')) {
    return 'administration';
  }
  return memberships.length > 0 ? 'service' : 'carte';
}

export function canAddRestaurant(user: User | null): boolean {
  return activeMemberships(user).some((membership) => membership.role === 'restaurant_admin');
}
