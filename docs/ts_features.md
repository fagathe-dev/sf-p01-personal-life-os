# 🎨 Documentation des Composants UI (Personal Life OS)

Ce document centralise le fonctionnement des composants d'interface riches utilisés dans l'application. L'approche globale est le **Progressive Enhancement** (Amélioration Progressive) : Symfony gère la source de vérité (les `<input>` et `<select>` natifs et cachés), et TypeScript s'occupe uniquement de la surcouche visuelle et de la synchronisation.

---

## 1. Custom Dropdown Select (`CustomSelector`)
Un composant inspiré de GitHub et Select2, permettant une sélection riche avec recherche intégrée, affichage de couleurs, de descriptions, ou d'avatars.

### Modes supportés (`data-cds-mode`)
- `single` : Sélection unique obligatoire.
- `single-nullable` *(défaut)* : Sélection unique, cliquable pour décocher.
- `multiple` : Sélection multiple (au moins 1 élément obligatoire).
- `multiple-nullable` : Sélection multiple classique, peut être vide.

### 1.1 Utilisation (Côté PHP / FormType)
Le composant s'attend à lire les données personnalisées via l'option `choice_attr` de Symfony. L'option `expanded` doit impérativement être à `false` pour générer un `<select>` natif.

```php
$builder->add('tags', EntityType::class, [
    'class' => Tag::class,
    'multiple' => true,
    'expanded' => false, // ⚠️ Requis : génère un <select> natif caché
    'choice_attr' => function (Tag $tag) {
        return [
            'data-color' => $tag->getColor()->value, // Pour la pastille de couleur
            'data-description' => $tag->getDescription() // Sous-titre
            // 'data-avatar' => $user->getAvatar() // Idéal pour un sélecteur d'utilisateurs
        ];
    },
]);
```

### 1.2 Utilisation (Côté Twig)

Il suffit d'inclure le composant générique en lui passant le champ de formulaire. Les attributs de mode et de placeholder sont optionnels (le composant possède des valeurs par défaut).

```twig
{% include 'components/_custom-dropdown-select.html.twig' with {
    'field': form.tags,
    'cds_mode': 'multiple-nullable',
    'cds_placeholder': 'Sélectionnez vos étiquettes...',
    'manage_url': path('auth_profile_index', {'t': 'tags'}) {# Optionnel : affiche un lien de gestion en bas du dropdown #}
} %}
```

### 1.3 Initialisation (Côté TypeScript)

Le script `custom-selector.ts` est totalement agnostique. Il cible les conteneurs `.js-dropdown-select`, lit le mode défini en HTML, et maintient le `<select>` natif de Symfony synchronisé avec l'interface utilisateur.

```typescript
// Exemple d'initialisation (app.ts)
import { CustomSelector, $ } from 'core-ts';

document.addEventListener('DOMContentLoaded', () => {
  const dropdownSelectContainers = $<HTMLElement>('.js-dropdown-select', true) as NodeListOf<HTMLElement> | null;

  if (dropdownSelectContainers) {
    dropdownSelectContainers.forEach((container) => {
      const mode = container.getAttribute('data-cds-mode') as any || 'multiple-nullable';
      const placeholder = container.getAttribute('data-cds-placeholder') || 'Sélectionner...';

      new CustomSelector(container, { mode, placeholder });
    });
  }
});
```

---

## 2. Selectable Card (`SelectableField`)

Utilisé pour transformer des boutons `input[type="radio"]` ou des `input[type="checkbox"]` classiques en éléments visuels riches et cliquables (exemple : le sélecteur de couleurs "Finder-style" ou les cartes de statuts).

### Modes supportés

* `radio` : Sélection exclusive, impossible de décocher l'élément actif (1 choix obligatoire).
* `multiple` : Comportement de checkbox classique (toggle libre).

### 2.1 Utilisation (Côté PHP / FormType)

Le composant s'attend à recevoir un champ étendu pour que Symfony génère physiquement chaque balise `<input>`.

```php
$builder->add('color', EnumType::class, [
    'class' => TagColorEnum::class,
    'expanded' => true,  // ⚠️ Requis : génère des boutons radios / checkboxes
    'multiple' => false, // false = mode radio, true = mode checkbox
]);
```

### 2.2 Utilisation (Côté Twig)

Contrairement au Dropdown, il faut boucler manuellement sur les enfants du champ en Twig pour masquer l'input natif (`.form-selectable-input`) et styliser le label (`.form-selectable-label`). Le conteneur parent doit posséder la classe `.js-selectable-container`.

```twig
<div class="js-selectable-container d-flex gap-2">
  {% for child in form.color %}
    <div class="form-selectable-item">
      
      {{ form_widget(child, {'attr': {'class': 'form-selectable-input d-none'}}) }}
      
      <label class="form-selectable-label color-swatch bg-{{ child.vars.value }}" 
             for="{{ child.vars.id }}"
             title="{{ child.vars.label }}">
      </label>
      
    </div>
  {% endfor %}
</div>
```

### 2.3 Initialisation (Côté TypeScript)

Le script `selectable.ts` écoute les clics sur les éléments `.form-selectable-item`. Selon le mode défini, il modifie la propriété `.checked` de l'input caché correspondant et gère la classe CSS `.active` sur l'élément parent pour déclencher les animations SCSS.

```typescript
// Exemple d'initialisation (app.ts)
import { SelectableField, $ } from 'core-ts';

document.addEventListener('DOMContentLoaded', () => {
  const container = $<HTMLElement>('.js-selectable-container') as HTMLElement | null;
  
  if (container) {
    // Mode radio par défaut. Modifier en { mode: 'multiple' } si besoin.
    new SelectableField(container, { mode: 'radio' });
  }
});
```
