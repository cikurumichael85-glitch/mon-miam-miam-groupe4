// Prise en main : Michael ; relecture croisée du binôme B.
export type Mode = 'demo' | 'api';
export type Page = 'accueil' | 'carte' | 'commandes' | 'fidelite' | 'compte' | 'equipe' | 'credits' | 'administration' | 'service' | 'restaurants';
export type Identifier = string | number;
export type Role = 'employee' | 'manager' | 'restaurant_admin';
export interface Restaurant {
  id: string;
  name: string;
  timezone: string;
  status: string;
  delivery_enabled: boolean;
  subtitle?: string;
  image?: string;
}
export interface Category {
  id: Identifier;
  name: string;
  restaurant_id: Identifier;
}
export interface Dish {
  id: Identifier;
  restaurant_id: Identifier;
  category_id: Identifier;
  name: string;
  description: string | null;
  price: string;
  available: boolean;
  is_daily: boolean;
  version: number;
  image?: string;
  tag?: string;
  photo_note?: string;
  photo_style?: 'plate' | 'bottle';
}
export interface Menu {
  categories: Category[];
  items: Dish[];
}
export interface Membership {
  id: Identifier;
  restaurant_id: Identifier;
  user_id: Identifier;
  role: Role;
  active: boolean;
  user?: { name: string; email: string };
}
export interface User {
  id: Identifier;
  name: string;
  email: string;
  phone: string;
  delivery_address: string | null;
  ranking_opt_in: boolean;
  public_alias: string | null;
  analytics_consent: boolean;
  memberships?: Membership[];
  platform_access?: boolean;
}
export interface CartLine {
  item: Dish;
  quantity: number;
}
export interface Cart {
  restaurantId: string | null;
  lines: CartLine[];
}
export interface Quote {
  id: string;
  expires_at: string;
  payload: {
    subtotal: string;
    discount: string;
    delivery_fee: string;
    total: string;
    currency: string;
  };
}
export type OrderStatus =
  | 'pending'
  | 'accepted'
  | 'preparing'
  | 'ready'
  | 'out_for_delivery'
  | 'completed'
  | 'cancelled'
  | 'rejected';
export interface Order {
  payment?: OrderDetail['payment'];
  id: Identifier;
  restaurant_id: Identifier;
  status: OrderStatus;
  total: string;
  payment_state: string;
  service_mode: 'on_site' | 'delivery';
  arrival_at?: string | null;
  delivery_snapshot?: { address: string; phone: string; instructions?: string | null } | null;
  created_at: string;
  version: number;
}
export interface OrderDetail {
  cash_receipt?: { actor_name: string; occurred_at: string } | null;
  data: Order;
  lines: { id: Identifier; name: string; quantity: number; unit_price: string }[];
  events: { id: Identifier; from_status: string | null; to_status: string; occurred_at: string }[];
  payment: { state: string; verified_at: string | null; mode?: string } | null;
}

export interface OpeningSlot {
  weekday: number;
  opens_at: string;
  closes_at: string;
}

export interface PaymentAvailability {
  available: boolean;
  test_mode: boolean;
  networks: string[];
}

export interface CheckoutOptions {
  restaurant_id: string;
  timezone: string;
  delivery_enabled: boolean;
  delivery_fee: string | null;
  hours: OpeningSlot[];
  payment: PaymentAvailability;
}

export interface RestaurantSettings {
  data: {
    id: Identifier;
    name: string;
    address: string | null;
    timezone: string;
    delivery_enabled: boolean;
    delivery_fee: string | null;
    settings_version: number;
  };
  hours: OpeningSlot[];
}
export interface Paginated<T> {
  data: T[];
  current_page?: number;
  last_page?: number;
  total?: number;
  meta?: { page: number; per_page: number; total: number };
}
export interface Campaign {
  id: Identifier;
  kind: 'promotion' | 'event' | 'quiz';
  title: string;
  description: string;
  rules?: { question?: string; choices?: string[] };
}
export interface Claim {
  id: Identifier;
  order_id: Identifier;
  subject: string;
  message: string;
  status: string;
}
export interface Reply {
  id: Identifier;
  author_id?: Identifier;
  body: string;
  state?: string;
}
export interface ClaimDetail {
  data: Claim;
  replies: Reply[];
}
export interface LoyaltyAccount {
  id: Identifier;
  balance: number;
  program: { restaurant_id: Identifier | null; rules: string | Record<string, unknown> };
}
export interface Statistics {
  paid_orders_total: string;
  cash_paid_orders: number;
  cash_paid_total: string;
  orders: number;
  completed_orders: number;
  completed_total: string;
  paid_completed_total: string;
  points_awarded: number;
  referrals_rewarded: number;
  generated_at: string;
}
