import { $ } from 'core-ts';

export type SelectorMode = 'single' | 'single-nullable' | 'multiple' | 'multiple-nullable';

export interface CustomSelectorOptions {
  mode: SelectorMode;
  placeholder?: string;
  onChange?: (values: string[]) => void;
}

export class CustomSelector {
  private nativeSelect: HTMLSelectElement | null;
  private buttonContent: HTMLElement | null;
  private options: NodeListOf<HTMLElement> | null;
  private searchInput: HTMLInputElement | null;
  
  // L'état interne (mémoire) du composant
  private selectedValues: Set<string> = new Set();

  constructor(private container: HTMLElement, private config: CustomSelectorOptions) {
    // Ciblage des éléments via ta fonction $()
    this.nativeSelect = $<HTMLSelectElement>('select', false, container) as HTMLSelectElement | null;
    this.buttonContent = $<HTMLElement>('.js-selector-button-content', false, container) as HTMLElement | null;
    this.options = $<HTMLElement>('.js-selector-option', true, container) as NodeListOf<HTMLElement> | null;
    this.searchInput = $<HTMLInputElement>('.js-dropdown-search', false, container) as HTMLInputElement | null;

    if (!this.nativeSelect || !this.options) {
        console.warn('CustomSelector: Éléments manquants pour l\'initialisation', container);
        return;
    }

    this.init();
  }

  private init(): void {
    // 1. Lire l'état initial envoyé par Symfony (les tags déjà sauvegardés)
    Array.from(this.nativeSelect!.options).forEach(opt => {
      if (opt.selected) this.selectedValues.add(opt.value);
    });

    // 2. Écouter les clics sur chaque option
    this.options!.forEach(option => {
      option.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Empêche la fermeture du menu Bootstrap si on est en mode multiple
        if (this.config.mode.includes('multiple')) {
            e.stopPropagation(); 
        }
        
        const value = option.getAttribute('data-value');
        if (value) this.handleSelection(value);
      });
    });

    // 3. Écouter la barre de recherche
    if (this.searchInput) {
        this.searchInput.addEventListener('input', (e) => {
            const query = (e.target as HTMLInputElement).value.toLowerCase().trim();
            this.handleSearch(query);
        });
    }

    // 4. Premier rendu visuel
    this.render();
  }

  /**
   * Le cerveau métier : Gère l'ajout/suppression selon le mode défini
   */
  private handleSelection(value: string): void {
    const isAlreadySelected = this.selectedValues.has(value);

    switch (this.config.mode) {
      case 'single':
        if (isAlreadySelected) return; // Sélection obligatoire, on ne peut pas décocher
        this.selectedValues.clear();
        this.selectedValues.add(value);
        break;

      case 'single-nullable':
        this.selectedValues.clear();
        if (!isAlreadySelected) this.selectedValues.add(value); // Comportement toggle
        break;

      case 'multiple':
        if (isAlreadySelected) {
          // Désélection permise uniquement s'il reste au moins 1 élément
          if (this.selectedValues.size > 1) {
            this.selectedValues.delete(value);
          }
        } else {
          this.selectedValues.add(value);
        }
        break;

      case 'multiple-nullable':
        // Toggle libre
        if (isAlreadySelected) {
          this.selectedValues.delete(value);
        } else {
          this.selectedValues.add(value);
        }
        break;
    }

    // On répercute sur le DOM et on met à jour l'UI
    this.syncNativeSelect();
    this.render();

    if (this.config.onChange) {
      this.config.onChange(Array.from(this.selectedValues));
    }
  }

  /**
   * Filtre les options visibles en fonction de la recherche
   */
  private handleSearch(query: string): void {
      if (!this.options) return;

      this.options.forEach(option => {
          // On cherche dans tout le texte de l'option (Titre + Description)
          const textContent = option.textContent?.toLowerCase() || '';
          
          // Toggle une classe Bootstrap 'd-none' pour cacher/afficher
          if (textContent.includes(query)) {
              option.classList.remove('d-none');
              option.classList.add('d-flex'); // On remet le d-flex de notre design
          } else {
              option.classList.remove('d-flex');
              option.classList.add('d-none');
          }
      });
  }

  /**
   * Synchronise l'interface visuelle (Vitrine) avec notre Set interne
   */
  private render(): void {
    if (!this.options) return;

    // 1. Mettre à jour les attributs aria-selected des options (qui pilotent le SCSS)
    this.options.forEach(option => {
      const value = option.getAttribute('data-value');
      const isSelected = value ? this.selectedValues.has(value) : false;
      option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
    });

    // 2. Mettre à jour le texte du bouton déclencheur
    if (this.buttonContent) {
      const buttonTextSpan = $<HTMLElement>('span:first-child', false, this.buttonContent) as HTMLElement | null;
      
      if (!buttonTextSpan) return;

      if (this.selectedValues.size === 0) {
        buttonTextSpan.textContent = this.config.placeholder || 'Sélectionner...';
        buttonTextSpan.className = 'text-muted';
        return;
      }

      if (this.config.mode.startsWith('single')) {
        // En mode single, on affiche le nom du tag sélectionné
        const selectedValue = Array.from(this.selectedValues)[0];
        const activeOption = Array.from(this.options).find(opt => opt.getAttribute('data-value') === selectedValue);
        
        // On récupère uniquement le titre (le premier div avec .fw-medium)
        const titleElement = activeOption?.querySelector('.fw-medium');
        buttonTextSpan.textContent = titleElement?.textContent?.trim() || '1 élément';
        buttonTextSpan.className = 'text-body fw-medium';
      } 
      else {
        // En mode multiple, on affiche un compteur simple
        buttonTextSpan.textContent = `${this.selectedValues.size} étiquette(s) sélectionnée(s)`;
        buttonTextSpan.className = 'text-body fw-medium';
      }
    }
  }

  /**
   * Met à jour le <select> caché pour la soumission du formulaire Symfony
   */
  private syncNativeSelect(): void {
    if (!this.nativeSelect) return;

    let hasChanged = false;
    Array.from(this.nativeSelect.options).forEach(opt => {
      const shouldBeSelected = this.selectedValues.has(opt.value);
      if (opt.selected !== shouldBeSelected) {
        opt.selected = shouldBeSelected;
        hasChanged = true;
      }
    });

    if (hasChanged) {
      this.nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }
}