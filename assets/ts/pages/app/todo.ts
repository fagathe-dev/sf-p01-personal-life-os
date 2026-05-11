import { $ } from 'core-ts';
import { CustomSelector } from '@/features'; // Ajuste l'import selon ton architecture réelle

document.addEventListener('DOMContentLoaded', (): void => {
  // 1. Initialisation du dropdown de Priorité
  const priorityContainer = $<HTMLElement>(
    '[data-input-id="todo_priority"]',
  ) as HTMLElement | null;

  if (priorityContainer) {
    const mode =
      (priorityContainer.getAttribute('data-cds-mode') as any) || 'single';
    const placeholder =
      priorityContainer.getAttribute('data-cds-placeholder') ||
      'Sélectionner une priorité...';

    new CustomSelector(priorityContainer, {
      mode: mode,
      placeholder: placeholder,
      // Exemple de callback ciblé possible grâce à cette approche :
      // onChange: (values) => console.log('Priorité changée:', values)
    });
  }

  // 2. Initialisation du dropdown des Étiquettes (Tags)
  const tagsContainer = $<HTMLElement>(
    '[data-input-id="todo_tags"]',
  ) as HTMLElement | null;

  if (tagsContainer) {
    const mode =
      (tagsContainer.getAttribute('data-cds-mode') as any) ||
      'multiple-nullable';
    const placeholder =
      tagsContainer.getAttribute('data-cds-placeholder') ||
      'Ajouter des étiquettes...';

    new CustomSelector(tagsContainer, {
      mode: mode,
      placeholder: placeholder,
    });
  }
});
