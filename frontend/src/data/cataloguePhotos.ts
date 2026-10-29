// Prise en main : Michael ; les prix et disponibilités restent ceux du serveur.
import selection from './catalogue-photos.json';
import type { Dish } from '../domain/types';

const photosByDish = new Map(
  selection.map((photo) => [photo.restaurant_id + ':' + photo.name, photo]),
);

export function illustrateCatalogueDish(dish: Dish): Dish {
  if (dish.image) {
    return dish;
  }
  const photo = photosByDish.get(dish.restaurant_id + ':' + dish.name);
  if (!photo) {
    return dish;
  }
  return {
    ...dish,
    image: photo.image,
    photo_note: photo.note,
    photo_style: photo.style === 'bottle' ? 'bottle' : 'plate',
  };
}
