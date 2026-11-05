// Prise en main : Michael ; relecture croisée du binôme B.
import type { Category, Dish, Restaurant } from '../domain/types';
import { kinshasaCategories, kinshasaDishes } from './kinshasa';

// Le mode démo reste distinct du catalogue enregistré dans la base Laravel.
export const demoRestaurants: Restaurant[] = [
  {
    id: 'demo-mboka',
    name: 'Mboka',
    subtitle: 'La générosité congolaise',
    timezone: 'Africa/Kinshasa',
    status: 'active',
    delivery_enabled: true,
    image: '/images/moambe.webp',
  },
  {
    id: 'demo-atelier',
    name: 'L’Atelier',
    subtitle: 'Les envies d’ici et d’ailleurs',
    timezone: 'Africa/Kinshasa',
    status: 'active',
    delivery_enabled: true,
    image: '/images/burger.webp',
  },
];

const categories = ['Les signatures', 'À partager', 'Boissons'];
const atelierCategories: Category[] = demoRestaurants.slice(1).flatMap((restaurant) =>
  categories.map((name, index) => ({
    id: `${restaurant.id}-${index}`,
    restaurant_id: restaurant.id,
    name,
  })),
);
export const demoCategories: Category[] = [...kinshasaCategories('demo-mboka'), ...atelierCategories];

type Recipe = [string, string, string, string, number, string?];
const atelier: Recipe[] = [
  [
    'Burger maison',
    'Pain moelleux, steak de bœuf, salade croquante et sauce maison.',
    '12000.00',
    'burger',
    0,
    'Le plaisir gourmand',
  ],
  [
    'Pizza au fromage',
    'Pâte dorée, fromage fondant et une touche d’herbes.',
    '18000.00',
    'pizza',
    0,
    'À partager',
  ],
  [
    'Poulet grillé & makemba',
    'Poulet à la braise et plantains, le duo qui met tout le monde d’accord.',
    '14000.00',
    'chicken',
    0,
  ],
  [
    'Poisson grillé',
    'Une assiette de poisson grillé, citron et accompagnement du jour.',
    '20000.00',
    'fish',
    0,
  ],
  ['Duo de burgers', 'Deux burgers maison pour une pause à deux.', '22000.00', 'burger', 1],
  [
    'Plantains dorés',
    'Une portion de bananes plantains frites à partager.',
    '3500.00',
    'plantain',
    1,
  ],
  [
    'Jus d’orange frais · 30 cl',
    'Un verre de soleil pour accompagner votre pause.',
    '4000.00',
    'juice',
    2,
  ],
  [
    'Jus tropical · 30 cl',
    'Des fruits selon saison. Photo d’illustration : jus d’orange.',
    '4500.00',
    'juice',
    2,
  ],
  ['Eau plate · 50 cl', 'Eau minérale, servie fraîche.', '1500.00', 'water', 2],
  ['Soda · 30 cl', 'Votre boisson gazeuse selon disponibilité.', '2500.00', 'soda', 2],
];

function buildMenu(restaurant: string, recipes: Recipe[]): Dish[] {
  return recipes.map(([name, description, price, photo, category, tag], index) => ({
    id: `${restaurant}-plat-${index}`,
    restaurant_id: restaurant,
    category_id: `${restaurant}-${category}`,
    name,
    description,
    price,
    available: true,
    is_daily: index === 0,
    version: 1,
    image: `/images/${photo}.${['water', 'soda'].includes(photo) ? 'svg' : 'webp'}`,
    tag,
  }));
}

export const demoDishes: Dish[] = [
  ...kinshasaDishes('demo-mboka'),
  ...buildMenu('demo-atelier', atelier),
];
