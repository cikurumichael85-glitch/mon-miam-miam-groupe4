// Prise en main : Michael ; relecture croisée du binôme B.
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles/tokens.css';
import './styles/layout.css';
import './styles/catalogue.css';
import './styles/workspace.css';

const root = document.getElementById('root');
if (!root) {
  throw new Error('Élément racine manquant.');
}
createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
