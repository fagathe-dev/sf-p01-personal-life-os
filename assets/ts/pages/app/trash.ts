import { ROUTES } from '@/constantes';
import { fetchAPI, $, router } from 'core-ts';

/**
 * Pilote la délégation d'événements pour l'onglet Fichiers et Notes de la corbeille
 */
const initTrashPageActions = () => {
  const trashRoot = $('#b-trash-root', false);
  if (!(trashRoot instanceof HTMLElement)) return;

  trashRoot.addEventListener('click', async (e: MouseEvent) => {
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
      console.log('drive action :', driveAction);

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
            animateAndRemoveTrashRow(row, 'files');
          }
        } catch (err) {
          console.error(err);
        }
      }

      if (driveAction === 'trash-file') {
        e.preventDefault();
        const fileName =
          row.querySelector('.item-name')?.textContent?.trim() || 'ce fichier';

        if (
          !confirm(
            `⚠️ SUPPRESSION DÉFINITIVE ⚠️\n\nVoulez-vous rayer à jamais le fichier "${fileName}" de votre stockage ?\nCette action est irréversible et détruira physiquement le fichier.`,
          )
        ) {
          return;
        }

        try {
          const response = await fetchAPI(
            router(ROUTES.ARCHIVE_TRASH.FILE_PURGE, { id: fileId }),
            {
              method: 'DELETE',
            },
          );

          if (response.data && response.data.success) {
            animateAndRemoveTrashRow(row, 'files');
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

      console.log('note action :', action);

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
            animateAndRemoveTrashRow(noteCard, 'notes');
          }
        } catch (err) {
          console.error(err);
        }
      }

      if (action === 'trash') {
        e.preventDefault();
        const noteTitle =
          noteCard.querySelector('.card-title')?.textContent?.trim() ||
          'cette note';

        if (
          !confirm(
            `🔥 PURGE DÉFINITIVE 🔥\n\nSupprimer définitivement la note "${noteTitle}" ?\nSon contenu textuel sera de la base de données sans possibilité de récupération.`,
          )
        ) {
          return;
        }

        try {
          const response = await fetchAPI(
            router(ROUTES.ARCHIVE_TRASH.NOTE_PURGE, { id: noteId }),
            {
              method: 'DELETE',
            },
          );

          if (response.data && response.data.success) {
            animateAndRemoveTrashRow(noteCard, 'notes');
          }
        } catch (err) {
          console.error(err);
        }
      }
    }
  });
};

/**
 * Gère les actions globales de haut de page (Restauration totale et vidage complet)
 */
const initTrashMassActions = () => {
  const restoreAllBtn = $('[data-trash-action="restore-all"]', false);
  const emptyTrashBtn = $('#emptyTrashBtn', false);

  if (restoreAllBtn instanceof HTMLButtonElement) {
    restoreAllBtn.addEventListener('click', async (e: MouseEvent) => {
      e.preventDefault();
      try {
        const response = await fetchAPI(
          router(ROUTES.ARCHIVE_TRASH.RESTORE_ALL, { context: 'trash' }),
          {
            method: 'POST',
          },
        );

        if (response.data && response.data.success) {
          clearAllTrashViews();
          restoreAllBtn.remove();
          if (emptyTrashBtn instanceof HTMLButtonElement)
            emptyTrashBtn.remove();
        }
      } catch (err) {
        console.error(err);
      }
    });
  }

  if (emptyTrashBtn instanceof HTMLButtonElement) {
    emptyTrashBtn.addEventListener('click', async (e: MouseEvent) => {
      e.preventDefault();

      const massPurgeWording = `🛑 DANGER - DESTRUCTION IMMÉDIATE 🛑\n\nÊtes-vous absolument sûr de vouloir VIDER ENTIÈREMENT votre corbeille ?\n\nTous les fichiers et toutes les notes vont être anéantis. L'espace disque sera libéré et aucun retour en arrière ne sera possible.`;

      if (!confirm(massPurgeWording)) return;

      try {
        const response = await fetchAPI(
          router(ROUTES.ARCHIVE_TRASH.EMPTY_TRASH),
          {
            method: 'DELETE',
          },
        );

        if (response.data && response.data.success) {
          clearAllTrashViews();
          emptyTrashBtn.remove();
          if (restoreAllBtn instanceof HTMLButtonElement)
            restoreAllBtn.remove();
        }
      } catch (err) {
        console.error(err);
      }
    });
  }
};

const animateAndRemoveTrashRow = (
  element: HTMLElement,
  type: 'files' | 'notes',
) => {
  element.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
  element.style.opacity = '0';
  element.style.transform = 'scale(0.95)';

  setTimeout(() => {
    element.remove();
    const badge = document.getElementById(`badge-${type}-count`);
    if (badge instanceof HTMLElement) {
      const count = parseInt(badge.textContent || '0', 10);
      badge.textContent = String(Math.max(0, count - 1));
    }
  }, 200);
};

const clearAllTrashViews = () => {
  const filesList = document.getElementById('b-trash-files-list');
  const notesList = document.getElementById('b-trash-notes-list');
  const badgeFiles = document.getElementById('badge-files-count');
  const badgeNotes = document.getElementById('badge-notes-count');

  if (filesList instanceof HTMLElement) {
    filesList.innerHTML =
      '<tr id="empty-file-row"><td colspan="4" class="text-center text-muted py-4">La corbeille ne contient aucun fichier</td></tr>';
  }
  if (notesList instanceof HTMLElement) {
    notesList.innerHTML =
      '<div class="col-12 text-center text-muted py-5 bg-white rounded shadow-sm border border-light" id="empty-note-row"><i class="ri-delete-bin-6-line fs-1 text-light d-block mb-2"></i>La corbeille ne contient aucune note</div>';
  }
  if (badgeFiles instanceof HTMLElement) badgeFiles.textContent = '0';
  if (badgeNotes instanceof HTMLElement) badgeNotes.textContent = '0';
};

// Lancement synchrone local (Aucun export global)
initTrashPageActions();
initTrashMassActions();
