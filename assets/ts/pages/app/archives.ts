import { ROUTES } from '@/constantes';
import { fetchAPI, $, router } from 'core-ts';

/**
 * Gère la délégation d'événements unitaires pour l'onglet Fichiers et Notes archivés
 */
const initArchivePageActions = () => {
  const archiveRoot = $('#b-archives-root', false);
  if (!(archiveRoot instanceof HTMLElement)) return;

  archiveRoot.addEventListener('click', async (e: MouseEvent) => {
    const target = e.target as HTMLElement;

    // --- 📂 CONTEXTE FICHIERS ---
    const fileActionEl = target.closest(
      '[data-drive-action]',
    ) as HTMLElement | null;
    if (fileActionEl instanceof HTMLElement) {
      const driveAction = fileActionEl.getAttribute('data-drive-action');
      const fileId = fileActionEl.getAttribute('data-item-id');
      const row = fileActionEl.closest(
        'tr[data-drive-item="file"]',
      ) as HTMLElement | null;

      if (!fileId || !(row instanceof HTMLTableRowElement)) return;

      if (driveAction === 'restore-file') {
        e.preventDefault();
        try {
          const response = await fetchAPI(
            router(ROUTES.ARCHIVE_TRASH.FILE_RESTORE, { id: fileId }),
            {
              method: 'POST',
            },
          );

          if (response.data && response.data.success) {
            animateAndRemoveItem(row);
          }
        } catch (err) {
          console.error(err);
        }
      }

      if (driveAction === 'trash-file') {
        e.preventDefault();
        try {
          const response = await fetchAPI(
            router(ROUTES.DRIVE.FILE.ACTION, {
              id: fileId,
              action: 'trash',
            }),
            {
              method: 'POST',
            },
          );

          if (response.data && response.data.success) {
            animateAndRemoveItem(row);
          }
        } catch (err) {
          console.error(err);
        }
      }
    }

    // --- 📝 CONTEXTE NOTES ---
    const noteActionEl = target.closest('[data-action]') as HTMLElement | null;
    if (noteActionEl instanceof HTMLElement) {
      const action = noteActionEl.getAttribute('data-action');
      const noteId = noteActionEl.getAttribute('data-id');
      const noteCard = noteActionEl.closest(
        '[data-note-id]',
      ) as HTMLElement | null;

      if (!noteId || !(noteCard instanceof HTMLElement)) return;

      // 👈 Alignement sur l'attribut cohérent restore-note
      if (action === 'restore-note') {
        e.preventDefault();
        try {
          const response = await fetchAPI(
            router(ROUTES.ARCHIVE_TRASH.NOTE_RESTORE, { id: noteId }),
            {
              method: 'POST',
            },
          );

          if (response.data && response.data.success) {
            animateAndRemoveItem(noteCard);
          }
        } catch (err) {
          console.error(err);
        }
      }

      if (action === 'trash') {
        e.preventDefault();
        try {
          const response = await fetchAPI(
            router(ROUTES.NOTE.QUICK_ACTIONS, {
              id: noteId,
              action: 'trash',
            }),
            {
              method: 'POST',
            },
          );

          if (response.data && response.data.success) {
            animateAndRemoveItem(noteCard);
          }
        } catch (err) {
          console.error(err);
        }
      }
    }
  });
};

/**
 * Action de masse : Tout restaurer l'espace d'archivage
 */
const initArchiveMassActions = () => {
  const restoreAllBtn = $('[data-archive-action="restore-all"]', false);
  if (!(restoreAllBtn instanceof HTMLButtonElement)) return;

  restoreAllBtn.addEventListener('click', async (e: MouseEvent) => {
    e.preventDefault();

    try {
      const response = await fetchAPI(
        '/ajax/archive-trash/archive/restore-all',
        {
          method: 'POST',
        },
      );

      if (response.data && response.data.success) {
        const filesList = document.getElementById('b-archive-files-list');
        const notesList = document.getElementById('b-archive-notes-list');

        if (filesList instanceof HTMLElement) {
          filesList.innerHTML =
            '<tr id="empty-file-row"><td colspan="4" class="text-center text-muted py-4">Aucun fichier archivé</td></tr>';
        }
        if (notesList instanceof HTMLElement) {
          notesList.innerHTML =
            '<div class="col-12 text-center text-muted py-5 bg-white rounded shadow-sm border border-light" id="empty-note-row"><i class="ri-sticky-note-line fs-1 text-light d-block mb-2"></i>Aucune note archivée</div>';
        }

        restoreAllBtn.remove();
      }
    } catch (err) {
      console.error(err);
    }
  });
};

const animateAndRemoveItem = (element: HTMLElement) => {
  element.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
  element.style.opacity = '0';
  element.style.transform = 'scale(0.95)';
  setTimeout(() => element.remove(), 200);
};

// Initialisation synchrone et cloisonnée (Zéro export)
initArchivePageActions();
initArchiveMassActions();
