import { $, insertElementToDOM, fetchAPI, router } from 'core-ts';
import { CustomSelector } from '@/features';
import { ROUTES } from '@/constantes';

console.log('Todo page script is being executed.');

document.addEventListener('DOMContentLoaded', (): void => {
  console.log('Todo page script loaded.');
  // 1. Initialisations CustomSelector existantes...
  const priorityContainer = $<HTMLElement>('[data-input-id="todo_priority"]') as HTMLElement | null;
  if (priorityContainer) {
    const mode = (priorityContainer.getAttribute('data-cds-mode') as any) || 'single';
    const placeholder = priorityContainer.getAttribute('data-cds-placeholder') || 'Sélectionner une priorité...';
    new CustomSelector(priorityContainer, { mode, placeholder });
  }

  const tagsContainer = $<HTMLElement>('[data-input-id="todo_tag"]') as HTMLElement | null;
  if (tagsContainer) {
    const mode = (tagsContainer.getAttribute('data-cds-mode') as any) || 'single-nullable';
    const placeholder = tagsContainer.getAttribute('data-cds-placeholder') || 'Ajouter des étiquettes...';
    new CustomSelector(tagsContainer, { mode, placeholder });
  }

  // ==========================================
  // ACTION : TOGGLE COMPLETED (Via Checkbox)
  // ==========================================
  const completedTogglerList = $('[data-todo-completed-toggler]', true) as NodeListOf<HTMLInputElement>;
  
  if (completedTogglerList) {
    (completedTogglerList as NodeListOf<HTMLInputElement>).forEach((checkbox) => {
      checkbox.addEventListener('change', async (e) => {
        console.log('Checkbox toggled:', e);
        const target = e.target as HTMLInputElement;
        const taskId = target.getAttribute('data-task-id');
        const taskRow = target.closest('[data-task-container]') as HTMLElement;

        if (!taskId || !taskRow) return;

        try {
          const res = await fetchAPI<{ success: boolean; is_completed: boolean }>(
            router(ROUTES.TODO.TOGGLE_COMPLETED, { id: taskId }), 
            { method: 'POST' }
          );

          if (res.data.success) {
            const isCompleted = res.data.is_completed;
            const titleElement = $<HTMLElement>('h6', false, taskRow);

            // -- ÉTAPE A : Mise à jour visuelle --
            // Le checkbox est déjà coché/décoché nativement par l'utilisateur.
            // On a juste à barrer le titre.
            if (isCompleted) {
              (titleElement as HTMLElement)?.classList.add('text-decoration-line-through', 'text-muted', 'opacity-75');
              (titleElement as HTMLElement)?.classList.remove('text-body');
            } else {
              (titleElement as HTMLElement)?.classList.remove('text-decoration-line-through', 'text-muted', 'opacity-75');
              (titleElement as HTMLElement)?.classList.add('text-body');
            }

            // -- ÉTAPE B : Shunshin no Jutsu (Déplacement DOM) --
            const activeList = $<HTMLElement>('#active-tasks-list');
            const completedList = $<HTMLElement>('#completed-tasks-list');
            
            if (isCompleted && completedList) {
                insertElementToDOM(taskRow, 'afterbegin', completedList as HTMLElement);
            } else if (!isCompleted && activeList) {
                insertElementToDOM(taskRow, 'beforeend', activeList as HTMLElement);
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
    });
  }
});

// ==========================================
// ACTION : ÉPINGLER (Délégation globale conservée)
// ==========================================
document.addEventListener('click', async (e: MouseEvent) => {
  const target = e.target as HTMLElement;

  const taskRow = target.closest('[data-task-container]') as HTMLElement;
  if (!taskRow) return;

  const taskId = taskRow.getAttribute('data-task-id');
  if (!taskId) return;

  const isPinIcon = target.className.includes('ri-pushpin-2');
  const btnPin = isPinIcon ? target.closest('a') : target.closest('a:has([class*="ri-pushpin-2"])');

  if (btnPin && taskRow.contains(btnPin as Node)) {
    e.preventDefault();
    e.stopPropagation();
    
    const icon = $<HTMLElement>('i', false, btnPin as HTMLElement);

    try {
      const res = await fetchAPI<{ success: boolean; is_pinned: boolean }>(
        router(ROUTES.TODO.TOGGLE_PINNED, { id: taskId }), 
        { method: 'POST' }
      );

      if (res.data.success && icon) {
        if (res.data.is_pinned) {
          (btnPin as HTMLElement).classList.replace('text-muted', 'text-warning');
          (icon as HTMLElement).className = 'ri-pushpin-2-fill';
        } else {
          (btnPin as HTMLElement).classList.replace('text-warning', 'text-muted');
          (icon as HTMLElement).className = 'ri-pushpin-2-line';
        }
      }
    } catch (error) {
      console.error('Erreur lors de l\'épinglage:', error);
    }
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
        const activeTasksCount = (activeList as HTMLElement).querySelectorAll('.list-group-item:not(#empty-active-state)').length;
        if (activeTasksCount === 0) {
            (emptyActiveState as HTMLElement).classList.remove('d-none');
        } else {
            (emptyActiveState as HTMLElement).classList.add('d-none');
        }
    }

    if (completedList && completedContainer && countBadge) {
        const completedTasksCount = (completedList as HTMLElement).querySelectorAll('.list-group-item').length;
        (countBadge as HTMLElement).textContent = completedTasksCount.toString();

        if (completedTasksCount === 0) {
            (completedContainer as HTMLElement).classList.add('d-none');
        } else {
            (completedContainer as HTMLElement).classList.remove('d-none');
        }
    }
}