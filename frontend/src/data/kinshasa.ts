// Prise en main : Michael ; relecture croisée du binôme B.
import selection from './kinshasa-menu.json';
import type { Category, Dish } from '../domain/types';

const recipesByName = new Map(selection.items.map((item) => [item.name, item]));

export function kinshasaCategories(restaurantId: string): Category[] {
  return selection.categories.map((category) => ({
    id: `${restaurantId}-${category.id}`,
    restaurant_id: restaurantId,
    name: category.name,
  }));
}

export function kinshasaDishes(restaurantId: string): Dish[] {
  return selection.items.map((item) => ({
    id: `${restaurantId}-${item.id}`,
    restaurant_id: restaurantId,
    category_id: `${restaurantId}-${item.category}`,
    name: item.name,
    description: item.description,
    price: item.price,
    available: true,
    is_daily: item.id === 'moambe',
    version: 1,
    image: `/images/${item.photo}.webp`,
    photo_note: item.photo_note,
    photo_style: photoStyle(item.category),
  }));
}

function photoStyle(category: string): Dish['photo_style'] {
  return ['sucres', 'bieres'].includes(category) ? 'bottle' : 'plate';
}

export function illustrateKinshasaDish(dish: Dish): Dish {
  // Cette sélection illustre uniquement les articles connus du restaurant local 1.
  // Les prix, droits, disponibilités et ingrédients restent ceux du serveur.
  if (String(dish.restaurant_id) !== selection.restaurant_id || dish.image) {
    return dish;
  }
  const recipe = recipesByName.get(dish.name);
  if (!recipe) {
    return dish;
  }
  return {
    ...dish,
    image: `/images/${recipe.photo}.webp`,
    photo_note: recipe.photo_note,
    photo_style: photoStyle(recipe.category),
  };
}
