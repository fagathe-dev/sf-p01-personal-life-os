import { $, insertElementToDOM, fetchAPI, router } from 'core-ts';
import { CustomSelector } from '@/features';
import { ROUTES } from '@/constantes';

console.log('Todo page script is being executed.');

document.addEventListener('DOMContentLoaded', (): void => {
  console.log('Todo page script loaded.');
  const tagsContainer = $<HTMLElement>(
    '[data-input-id="todo_tag"]',
  ) as HTMLElement | null;
  if (tagsContainer) {
    const mode =
      (tagsContainer.getAttribute('data-cds-mode') as any) || 'single-nullable';
    const placeholder =
      tagsContainer.getAttribute('data-cds-placeholder') ||
      'Ajouter des étiquettes...';
    new CustomSelector(tagsContainer, { mode, placeholder });
  }

  // ==========================================
  // ACTION : TOGGLE COMPLETED (Via Checkbox)
  // ==========================================
  const completedTogglerList = $(
    '[data-todo-completed-toggler]',
    true,
  ) as NodeListOf<HTMLInputElement>;

  if (completedTogglerList) {
    (completedTogglerList as NodeListOf<HTMLInputElement>).forEach(
      (checkbox) => {
        checkbox.addEventListener('change', async (e) => {
          console.log('Checkbox toggled:', e);
          const target = e.target as HTMLInputElement;
          const taskId = target.getAttribute('data-task-id');
          const taskRow = target.closest(
            '[data-task-container]',
          ) as HTMLElement;

          if (!taskId || !taskRow) return;

          try {
            const res = await fetchAPI<{
              success: boolean;
              is_completed: boolean;
            }>(router(ROUTES.TODO.TOGGLE_COMPLETED, { id: taskId }), {
              method: 'POST',
            });

            if (res.data.success) {
              const isCompleted = res.data.is_completed;
              const titleElement = $<HTMLElement>('h6', false, taskRow);

              // -- ÉTAPE A : Mise à jour visuelle --
              // Le checkbox est déjà coché/décoché nativement par l'utilisateur.
              // On a juste à barrer le titre.
              if (isCompleted) {
                (titleElement as HTMLElement)?.classList.add(
                  'text-decoration-line-through',
                  'text-muted',
                  'opacity-75',
                );
                (titleElement as HTMLElement)?.classList.remove('text-body');
              } else {
                (titleElement as HTMLElement)?.classList.remove(
                  'text-decoration-line-through',
                  'text-muted',
                  'opacity-75',
                );
                (titleElement as HTMLElement)?.classList.add('text-body');
              }

              // -- ÉTAPE B : Shunshin no Jutsu (Déplacement DOM) --
              const activeList = $<HTMLElement>('#active-tasks-list');
              const completedList = $<HTMLElement>('#completed-tasks-list');

              if (isCompleted && completedList) {
                insertElementToDOM(
                  taskRow,
                  'afterbegin',
                  completedList as HTMLElement,
                );
              } else if (!isCompleted && activeList) {
                insertElementToDOM(
                  taskRow,
                  'beforeend',
                  activeList as HTMLElement,
                );
              }

              // -- ÉTAPE C : Recalcul de l'interface --
              updateTodoUIState();
            } else {
              // Rollback visuel de la coche en cas d'erreur métier
              target.checked = !target.checked;
            }
          } catch (error) {
            console.error('Erreur lors du changement de statut:', error);
            // Rollback visuel de la coche en cas d'erreur API
            target.checked = !target.checked;
          }
        });
      },
    );
  }
});

/**
 * Fonction utilitaire pour recalculer les compteurs et l'affichage des conteneurs
 */
function updateTodoUIState() {
  const activeList = $<HTMLElement>('#active-tasks-list');
  const completedList = $<HTMLElement>('#completed-tasks-list');
  const emptyActiveState = $<HTMLElement>('#empty-active-state');
  const completedContainer = $<HTMLElement>('#completed-tasks-container');
  const countBadge = $<HTMLElement>('#completed-tasks-count');

  if (activeList && emptyActiveState) {
    const activeTasksCount = (activeList as HTMLElement).querySelectorAll(
      '.list-group-item:not(#empty-active-state)',
    ).length;
    if (activeTasksCount === 0) {
      (emptyActiveState as HTMLElement).classList.remove('d-none');
    } else {
      (emptyActiveState as HTMLElement).classList.add('d-none');
    }
  }

  if (completedList && completedContainer && countBadge) {
    const completedTasksCount = (completedList as HTMLElement).querySelectorAll(
      '.list-group-item',
    ).length;
    (countBadge as HTMLElement).textContent = completedTasksCount.toString();

    if (completedTasksCount === 0) {
      (completedContainer as HTMLElement).classList.add('d-none');
    } else {
      (completedContainer as HTMLElement).classList.remove('d-none');
    }
  }
}
