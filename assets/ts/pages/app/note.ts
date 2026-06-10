import { $, convertMarkdownToHtml, fetchAPI, isEmpty, router, SelectableField } from 'core-ts'; // Importe ton composant
import { CustomSelector } from '@/features';
import { ROUTES } from '@/constantes';

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
      window.location.href =
        params.toString() === ''
          ? form.action
          : `${form.action}?${params.toString()}`;
      // Empêche la soumission par défaut du formulaire
      return false;
    });
  }

  const tagsContainer = $<HTMLElement>(
    '[data-input-id="note_tag"]',
  ) as HTMLElement | null;
  if (tagsContainer) {
    const mode =
      (tagsContainer.getAttribute('data-cds-mode') as any) || 'single-nullable';
    const placeholder =
      tagsContainer.getAttribute('data-cds-placeholder') ||
      'Sélectionner une étiquette...';

    new CustomSelector(tagsContainer, { mode, placeholder });
  }

  const colorContainer = $('.js-color-selectable-container', false);
  if (colorContainer) {
    // 👈 'nullable' permet de cliquer sur la pastille active pour la décocher
    new SelectableField(colorContainer as HTMLElement, { mode: 'nullable' });
  }

  // ==========================================
  // PARSING MARKDOWN DES APERÇUS DE NOTES
  // ==========================================
  const notePreviews = $(
    '.note-content-preview',
    true,
  ) as NodeListOf<HTMLElement>;

  notePreviews.forEach((preview) => {
    // 1. On récupère le contenu brut
    const rawContent = preview.getAttribute('data-note-raw-content');

    if (rawContent) {
      // 2. On le convertit en HTML via ta librairie
      const htmlContent = convertMarkdownToHtml(rawContent);

      // 3. On l'injecte dans la div
      preview.innerHTML = htmlContent;

      // 4. On supprime l'attribut pour nettoyer le DOM (sécurité et propreté)
      preview.removeAttribute('data-note-raw-content');
    }
  });

  // ==========================================
  // GESTION DES QUICK ACTIONS VIA DELEGATION (AJAX)
  // ==========================================
  document.addEventListener('click', async (e: MouseEvent) => {
    const target = e.target as HTMLElement;

    // On cherche si l'élément cliqué (ou son parent) possède un data-action
    const actionElement = target.closest('[data-action="pinned"], [data-action="archive"], [data-action="trash"]') as HTMLElement | null;
    if (!actionElement) return;

    // Empêche le comportement par défaut (ex: href="#")
    e.preventDefault();
    e.stopPropagation();

    type NoteAction = 'pinned' | 'archive' | 'trash';

    const action = actionElement.getAttribute('data-action') as NoteAction;
    const noteId = actionElement.getAttribute('data-id');
    const cardContainer = target.closest('[data-note-container]') as HTMLElement | null;
    const itemWrapper = target.closest('[data-note-item-wrapper]') as HTMLElement | null;

    if (!noteId || !cardContainer || !itemWrapper) return;

    try {
      // Construction dynamique de l'URL via ton utilitaire router()
      const url = router(ROUTES.NOTE.QUICK_ACTIONS, { id: noteId, action });
      
      const res = await fetchAPI<{ success: boolean; is_pinned: boolean; state: string }>(url, {
        method: 'PUT' // Conforme à l'annotation du contrôleur Symfony
      });

      if (res.data.success) {
        
        if (action === 'pinned') {
          // --- TRAITEMENT DE L'ÉPINGLAGE ---
          const isPinned = res.data.is_pinned;
          const icon = cardContainer.querySelector('.pin-button i') as HTMLElement | null;
          const pinButton = cardContainer.querySelector('.pin-button') as HTMLButtonElement | null;

          const pinnedList = $('#pinned-note-lists', false) as HTMLElement | null;
          const unpinnedList = $('#note-lists', false) as HTMLElement | null;

          if (isPinned) {
            // Changement visuel d'icône vers l'état activé
            if (icon) icon.className = 'ri-pushpin-2-fill text-warning fs-5';
            if (pinButton) pinButton.title = 'Désépingler';
            
            // Déplacement physique vers le bloc des notes épinglées
            if (pinnedList) pinnedList.prepend(itemWrapper);
          } else {
            // Changement visuel d'icône vers l'état désactivé
            if (icon) icon.className = 'ri-pushpin-2-line text-muted fs-5';
            if (pinButton) pinButton.title = 'Épingler';
            
            // Déplacement physique vers la liste normale
            if (unpinnedList) unpinnedList.prepend(itemWrapper);
          }
        } else {
          // --- TRAITEMENT DE L'ARCHIVE OU DE LA CORBEILLE ---
          // La note quitte la vue courante : on la supprime proprement du DOM
          itemWrapper.remove();
        }

        // Dans tous les cas de succès, on recalcule la visibilité des conteneurs
        updateNotesUIState();
      }
    } catch (error) {
      console.error(`Erreur lors de l'action ${action} sur la note :`, error);
    }
  });

  /**
   * Recalcule dynamiquement la visibilité des blocs Épinglés / Autres notes
   */
  function updateNotesUIState(): void {
    const pinnedContainer = $('#pinned-notes-container', false) as HTMLElement | null;
    const pinnedList = $('#pinned-note-lists', false) as HTMLElement | null;
    const unpinnedTitle = $('#unpinned-notes-title', false) as HTMLElement | null;
    const unpinnedList = $('#note-lists', false) as HTMLElement | null;

    if (pinnedList && pinnedContainer && unpinnedTitle) {
      // Compte le nombre de wrappers de note restants dans le bloc épinglé
      const pinnedCount = pinnedList.querySelectorAll('[data-note-item-wrapper]').length;

      if (pinnedCount > 0) {
        pinnedContainer.classList.remove('d-none');
        unpinnedTitle.classList.remove('d-none');
      } else {
        pinnedContainer.classList.add('d-none');
        unpinnedTitle.classList.add('d-none'); // Cache le titre "Autres notes" si rien n'est épinglé
      }
    }

    // Gestion optionnelle de l'état complètement vide (si toutes les notes sont archivées/supprimées)
    if (unpinnedList) {
      const totalNotes = document.querySelectorAll('[data-note-item-wrapper]').length;
      if (totalNotes === 0) {
        // Recharge la page ou affiche le bloc "empty state" si tu en as un
        window.location.reload();
      }
    }
  }
});
