import { describe, expect, it } from 'vitest';
import { illustrateCatalogueDish } from './cataloguePhotos';
import type { Dish } from '../domain/types';

const dish: Dish = {
  id: '25',
  restaurant_id: '2',
  category_id: '5',
  name: 'Moussaka grecque',
  description: 'Description fournie par le restaurant.',
  price: '19500.00',
  available: false,
  is_daily: false,
  version: 7,
};

describe('Photographies du catalogue connecté', () => {
  it('ajoute la photo sans remplacer le prix, le texte ou la disponibilité du serveur', () => {
    const result = illustrateCatalogueDish(dish);
    expect(result.image).toBe('/images/menu-moussaka.webp');
    expect(result.price).toBe('19500.00');
    expect(result.description).toBe(dish.description);
    expect(result.available).toBe(false);
    expect(result.version).toBe(7);
    expect(dish.image).toBeUndefined();
  });

  it('isole les photos par restaurant même si un nom est identique', () => {
    const foreignDish = { ...dish, restaurant_id: '98' };
    expect(illustrateCatalogueDish(foreignDish)).toBe(foreignDish);
  });

  it('accepte un identifiant numérique issu du serveur', () => {
    expect(illustrateCatalogueDish({ ...dish, restaurant_id: 2 }).image).toBe(
      '/images/menu-moussaka.webp',
    );
  });

  it('conserve une photo fournie explicitement par le restaurant', () => {
    const custom = { ...dish, image: '/images/photo-du-restaurant.webp' };
    expect(illustrateCatalogueDish(custom)).toBe(custom);
  });

  it('ne devine pas de photo pour une nouvelle recette inconnue', () => {
    const unknown = { ...dish, name: 'Recette privée du chef' };
    expect(illustrateCatalogueDish(unknown)).toBe(unknown);
  });
});
