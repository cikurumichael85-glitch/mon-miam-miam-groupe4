// Prise en main : Michael ; relecture croisée du binôme B.
import { useEffect, useState } from 'react';
import type { Dish, Menu, Restaurant } from '../domain/types';
import { money } from '../domain/money';
import { useApp } from '../state/AppContext';
import { useResource } from '../hooks/useResource';
import { dishPhoto, menu, restaurants } from '../services/catalogue';
import { Icon, Modal, SectionTitle, State } from '../components/Ui';
import { Community } from './Community';

function Hero() {
  return (
    <section className="hero">
      <img
        className="hero-photo"
        src="/images/moambe.webp"
        alt="Poulet à la moambe, pondu et chikwangue, une table aux saveurs d’Afrique centrale"
        fetchPriority="high"
      />
      <div className="hero-shade" />
      <div className="hero-copy">
        <p className="eyebrow">
          <span /> DU CONGO, AVEC GÉNÉROSITÉ
        </p>
        <h1>
          Le goût d’ici.
          <br />
          Le plaisir <em>ensemble.</em>
        </h1>
        <p>
          La moambe qui mijote, les braises qui crépitent,
          <br className="desktop-break" /> et cette envie de revenir. Votre prochaine bonne assiette
          est ici.
        </p>
        <a className="button gold" href="#carte">
          Commander mes plats <Icon name="arrow" />
        </a>
        <div className="hero-notes">
          <span>
            <Icon name="leaf" />
            Une carte généreuse
          </span>
          <span>
            <Icon name="pin" />
            En République démocratique du Congo
          </span>
        </div>
      </div>
      <div className="hero-caption">
        <span>À L’HONNEUR</span>
        <strong>Le poulet à la moambe</strong>
        <small>Un classique qui rassemble.</small>
      </div>
      <div className="hero-number">
        01 <span>/ LE GOÛT DU CONGO</span>
      </div>
    </section>
  );
}

function RestaurantCard({
  row,
  active,
  select,
}: {
  row: Restaurant;
  active: boolean;
  select: () => void;
}) {
  return (
    <button
      className={`restaurant-card ${active ? 'selected' : ''}`}
      onClick={select}
      aria-pressed={active}
      aria-label={`Choisir ${row.name}`}
    >
      <img src={row.image ?? '/images/placeholder.svg'} alt="" loading="lazy" />
      <span className="restaurant-card-body">
        <span className="restaurant-kicker">{row.subtitle ?? 'Restaurant partenaire'}</span>
        <strong>{row.name}</strong>
        <span className="restaurant-meta">
          {row.delivery_enabled ? 'Sur place · Livraison possible' : 'Sur place'}
          <span className="round-arrow">
            <Icon name={active ? 'check' : 'arrow'} size={18} />
          </span>
        </span>
      </span>
    </button>
  );
}

function DishCard({ dish, add, detail }: { dish: Dish; add: () => void; detail: () => void }) {
  return (
    <article className={`dish-card ${!dish.available ? 'unavailable' : ''}`}>
      <button
        className={`dish-photo-button ${dish.photo_style === 'bottle' ? 'bottle-photo' : ''}`}
        onClick={detail}
        aria-label={`Voir ${dish.name}`}
      >
        <img src={dishPhoto(dish)} alt={dish.name} loading="lazy" />
        {dish.is_daily && <span className="dish-tag">À l’honneur</span>}
      </button>
      <div className="dish-body">
        <p className="dish-kicker">{dish.tag ?? 'À savourer'}</p>
        <button className="dish-name" onClick={detail}>
          {dish.name}
        </button>
        <p className="dish-description">{dish.description}</p>
        {dish.photo_note && <p className="photo-note">{dish.photo_note}</p>}
        <div className="dish-bottom">
          <strong>{money(dish.price)}</strong>
          <button
            className="add-button"
            disabled={!dish.available}
            onClick={add}
            aria-label={`Ajouter ${dish.name} au panier`}
          >
            {dish.available ? 'Ajouter' : 'Épuisé'}
          </button>
        </div>
      </div>
    </article>
  );
}

function TasteBanner() {
  return (
    <section className="taste-banner">
      <img src="/images/juice.webp" alt="Jus d’orange frais avec oranges" loading="lazy" />
      <div>
        <p className="eyebrow">UN PEU DE FRAÎCHEUR ?</p>
        <h2>
          Le soleil aussi
          <br />
          se sert <em>au verre.</em>
        </h2>
        <p>
          Jus de fruits, boissons fraîches et petites envies :<br />
          la touche qui complète votre repas.
        </p>
        <a href="#carte" className="button secondary">
          Explorer toute la carte <Icon name="arrow" />
        </a>
      </div>
      <span className="taste-pattern" aria-hidden="true">
        ✳
      </span>
    </section>
  );
}

function Intro({ home }: { home: boolean }) {
  if (home) {
    return <Hero />;
  }
  return (
    <section className="page-intro">
      <p className="eyebrow">CHAQUE ENVIE A SON ASSIETTE</p>
      <h1>
        Qu’est-ce qui vous
        <br />
        fait <em>envie aujourd’hui ?</em>
      </h1>
      <p>Choisissez votre restaurant, puis laissez parler votre appétit.</p>
    </section>
  );
}

function Places({
  data,
  loading,
  error,
  reload,
  selected,
  select,
}: {
  data: Restaurant[] | null;
  loading: boolean;
  error: string;
  reload: () => void;
  selected: string;
  select: (row: Restaurant) => void;
}) {
  const rows = data ?? [];
  return (
    <section className="section" id="restaurants">
      <SectionTitle eyebrow="À CHAQUE ENVIE SA TABLE" title="À quelle table vous installer ?" />
      <State
        loading={loading}
        error={error}
        retry={reload}
        empty={rows.length === 0 ? 'Aucun restaurant disponible pour le moment.' : undefined}
      />
      <div className="restaurant-grid">
        {rows.map((row) => (
          <RestaurantCard
            key={row.id}
            row={row}
            active={selected === row.id}
            select={() => select(row)}
          />
        ))}
      </div>
    </section>
  );
}

function MenuControls({
  search,
  setSearch,
  daily,
  setDaily,
}: {
  search: string;
  setSearch: (value: string) => void;
  daily: boolean;
  setDaily: (value: boolean) => void;
}) {
  return (
    <div className="menu-tools">
      <label className="search-field">
        <Icon name="search" />
        <input
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          type="search"
          aria-label="Rechercher un plat ou une boisson"
          placeholder="Un plat, une envie, une boisson…"
        />
      </label>
      <label className="toggle">
        <input
          type="checkbox"
          checked={daily}
          onChange={(event) => setDaily(event.target.checked)}
        />
        Les plats à l’honneur
      </label>
    </div>
  );
}

function matchesDish(dish: Dish, category: string, search: string, daily: boolean): boolean {
  const matchingCategory = category === 'all' || String(dish.category_id) === category;
  const matchingDay = !daily || dish.is_daily;
  const text = `${dish.name} ${dish.description ?? ''}`.toLocaleLowerCase('fr');
  return matchingCategory && matchingDay && text.includes(search.toLocaleLowerCase('fr'));
}

function MenuResults({
  data,
  loading,
  error,
  reload,
  home,
  category,
  setCategory,
  search,
  daily,
  add,
  detail,
}: {
  data: Menu | null;
  loading: boolean;
  error: string;
  reload: () => void;
  home: boolean;
  category: string;
  setCategory: (value: string) => void;
  search: string;
  daily: boolean;
  add: (dish: Dish) => void;
  detail: (dish: Dish) => void;
}) {
  if (loading || error || !data) {
    return <State loading={loading} error={error} retry={reload} />;
  }
  const filtered = data.items.filter((dish) => matchesDish(dish, category, search, daily));
  const shown = home ? filtered.slice(0, 4) : filtered;
  return (
    <>
      <div className="category-tabs" aria-label="Catégories">
        <button className={category === 'all' ? 'active' : ''} onClick={() => setCategory('all')}>
          Toute la carte
        </button>
        {data.categories.map((row) => (
          <button
            key={row.id}
            className={category === String(row.id) ? 'active' : ''}
            onClick={() => setCategory(String(row.id))}
          >
            {row.name}
          </button>
        ))}
      </div>
      <State
        empty={
          shown.length === 0
            ? 'Aucun plat pour cette sélection. Essayez une autre envie.'
            : undefined
        }
      />
      <div className="dish-grid">
        {shown.map((dish) => (
          <DishCard key={dish.id} dish={dish} detail={() => detail(dish)} add={() => add(dish)} />
        ))}
      </div>
    </>
  );
}

export function Explore({ home }: { home: boolean }) {
  const app = useApp();
  const places = useResource(`restaurants:${app.mode}`, (signal) => restaurants(app.mode, signal));
  const [category, setCategory] = useState('all');
  const [search, setSearch] = useState('');
  const [daily, setDaily] = useState(false);
  const [detail, setDetail] = useState<Dish | null>(null);
  useEffect(() => {
    const first = places.data?.[0];
    if (!app.restaurant && first) {
      app.setRestaurant(first);
    }
  }, [places.data, app]);
  const id = app.restaurant ? app.restaurant.id : '';
  const catalogue = useResource(`menu:${app.mode}:${id}`, async (signal) =>
    id ? menu(app.mode, id, signal) : { categories: [], items: [] },
  );
  function select(row: Restaurant) {
    app.setRestaurant(row);
    setCategory('all');
    setSearch('');
    setDaily(false);
  }
  function add(item: Dish) {
    if (app.cart.restaurantId && app.cart.restaurantId !== String(item.restaurant_id)) {
      app.notify(
        'Votre panier appartient à un autre restaurant. Videz-le avant de composer une nouvelle commande.',
      );
      return;
    }
    app.dispatch({ type: 'add', item });
    app.notify(`${item.name} ajouté au panier.`);
  }
  const name = app.restaurant ? app.restaurant.name.toUpperCase() : 'À LA CARTE';
  return (
    <>
      <Intro home={home} />
      <div className="value-strip">
        <span>
          01 <strong>Choisissez votre restaurant</strong>
        </span>
        <span>
          02 <strong>Composez votre pause</strong>
        </span>
        <span>
          03 <strong>Savourez, tout simplement</strong>
        </span>
      </div>
      <Places
        data={places.data}
        loading={places.loading}
        error={places.error}
        reload={places.reload}
        selected={id}
        select={select}
      />
      <section className="section menu-section">
        <SectionTitle eyebrow={name} title="De quoi mettre l’eau à la bouche.">
          <a className="text-link" href="#carte">
            Toute la carte <Icon name="arrow" />
          </a>
        </SectionTitle>
        {!home && (
          <MenuControls search={search} setSearch={setSearch} daily={daily} setDaily={setDaily} />
        )}
        <MenuResults
          data={catalogue.data}
          loading={catalogue.loading}
          error={catalogue.error}
          reload={catalogue.reload}
          home={home}
          category={category}
          setCategory={setCategory}
          search={search}
          daily={daily}
          add={add}
          detail={setDetail}
        />
        <p className="menu-footnote">
          Photos d’illustration. Les tarifs du catalogue de démonstration sont indicatifs. En mode
          connecté, le restaurant vérifie les prix et les disponibilités à la commande. Pour les
          ingrédients et allergènes, renseignez-vous auprès du restaurant.
        </p>
      </section>
      {home && <TasteBanner />}
      {id && !home && <Community key={id} restaurantId={id} />}
      {detail && (
        <Modal title={detail.name} close={() => setDetail(null)}>
          <img
            className={`detail-photo ${detail.photo_style === 'bottle' ? 'bottle-detail' : ''}`}
            src={dishPhoto(detail)}
            alt={detail.name}
          />
          {detail.photo_note && <p className="photo-note">{detail.photo_note}</p>}
          <p>{detail.description}</p>
          <div className="dish-bottom">
            <strong>{money(detail.price)}</strong>
            <button className="button" disabled={!detail.available} onClick={() => add(detail)}>
              Ajouter au panier <Icon name="bag" />
            </button>
          </div>
        </Modal>
      )}
    </>
  );
}
