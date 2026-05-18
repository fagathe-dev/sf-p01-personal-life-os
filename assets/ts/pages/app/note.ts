import { $, isEmpty, SelectableField } from 'core-ts'; // Importe ton composant

document.addEventListener('DOMContentLoaded', () => {
  // Initialisation du filtre de couleur visuel
  const colorFilterContainer = $('#color-filter-selectable', false);
  if (colorFilterContainer) {
    // Mode 'radio' pour une seule sélection possible à la fois
    new SelectableField(colorFilterContainer as HTMLElement, { mode: 'radio' });
  }

  const form = $('#note-search-filter-form', false) as HTMLFormElement | null;
  if (form instanceof HTMLFormElement) {
    // Soumission du formulaire à chaque changement de filtre

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const t = form.querySelector(
        'input[name="t"]:checked',
      ) as HTMLInputElement | null;
      const c = form.querySelector(
        'input[name="c"]:checked',
      ) as HTMLInputElement | null;
      const q = form.querySelector(
        'input[name="q"]',
      ) as HTMLInputElement | null;

      const params = new URLSearchParams();
      if (t && t.value && !isEmpty(t.value)) params.append('t', t.value);
      if (c && c.value && !isEmpty(c.value)) params.append('c', c.value);
      if (q && q.value && !isEmpty(q.value)) params.append('q', q.value);

      // Redirection vers l'URL avec les paramètres de filtre
      window.location.href = params.toString() === '' ? form.action : `${form.action}?${params.toString()}`;
      // Empêche la soumission par défaut du formulaire
      return false;
    });
  }
});
