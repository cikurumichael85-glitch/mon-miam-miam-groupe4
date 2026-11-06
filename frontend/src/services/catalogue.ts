// Prise en main : Michael ; relecture croisée du binôme B.
import type { Dish, Menu, Mode, Paginated, Restaurant } from '../domain/types';
import { demoCategories, demoDishes, demoRestaurants } from '../data/demo';
import { illustrateCatalogueDish } from '../data/cataloguePhotos';
import { request } from './http';

export async function restaurants(mode: Mode, signal: AbortSignal): Promise<Restaurant[]> {
  if (mode === 'demo') {
    return demoRestaurants;
  }
  const result = await request<Paginated<Restaurant>>(
    '/restaurants?per_page=100',
    'GET',
    undefined,
    signal,
  );
  const rows = [...result.data];
  const pages = Math.ceil((result.meta?.total ?? rows.length) / 100);
  for (let page = 2; page <= pages; page += 1) {
    const next = await request<Paginated<Restaurant>>(
      `/restaurants?per_page=100&page=${page}`,
      'GET',
      undefined,
      signal,
    );
    rows.push(...next.data);
  }
  return rows;
}

export async function menu(mode: Mode, id: string, signal: AbortSignal): Promise<Menu> {
  if (mode === 'demo') {
    return {
      categories: demoCategories.filter((row) => row.restaurant_id === id),
      items: demoDishes.filter((row) => row.restaurant_id === id),
    };
  }
  const endpoint = `/restaurants/${encodeURIComponent(id)}/menu`;
  const result = await request<{ categories: Menu['categories']; items: Paginated<Dish> }>(
    endpoint,
    'GET',
    undefined,
    signal,
  );
  const items = [...result.items.data];
  for (let page = 2; page <= (result.items.last_page ?? 1); page += 1) {
    const next = await request<{ items: Paginated<Dish> }>(
      `${endpoint}?page=${page}`,
      'GET',
      undefined,
      signal,
    );
    items.push(...next.items.data);
  }
  return { categories: result.categories, items: items.map(illustrateCatalogueDish) };
}

// Un article non documenté conserve son image serveur ou une illustration neutre.
export function dishPhoto(dish: Dish): string {
  return dish.image ?? '/images/placeholder.svg';
}
