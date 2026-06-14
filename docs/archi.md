# 🏗️ Architecture de Personal Life OS

## Vue d'ensemble

**Personal Life OS** est une application **Symfony** full-stack avec une architecture **Progressive Enhancement** :
- **Backend** : Symfony (PHP) + Doctrine ORM — source de vérité
- **Frontend** : TypeScript + SCSS — enrichissement interactif du HTML
- **Approche** : Les templates Twig génèrent le HTML structure (formulaires, données), TypeScript ajoute l'interactivité et l'UX sans toucher à la source de vérité

---

## 📂 Structure des fichiers

### 🔷 `src/` — Logique métier (Backend)

#### `src/Entity/` — Modélisation des données (Doctrine ORM)
Représentation abstraite et persistante des données de l'application via des entités Doctrine.

**Entités principales:**
- **`User`** : Utilisateur authentifié (email, roles, avatar, preferences)
  - Propriétaire de toutes les autres données personnelles
  - Relations 1:N vers Note, Task, Folder, Tag, Journal, Capsule, DriveDocument, Location
- **`Note`** (hérité de `AbstractTextEntry`) : Notes simples (titre, contenu, couleur, état)
  - Relation N:1 vers `Tag`, N:1 vers `User`
  - États: brouillon, actif, archivé
- **`Task`** (hérité de `AbstractTextEntry`) : Tâches/todos (titre, description, priorité, état)
  - Relation N:1 vers `Tag`, `User`
  - Propriétés: `is_completed`, `due_date`, `priority` (enum), `is_pinned`
- **`Journal`** : Journal personnel (label, secret)
  - Relation 1:1 vers `User`
  - Relations 1:N vers `JournalEntry`, `JournalMedia`
- **`JournalEntry`** (hérité de `AbstractTextEntry`) : Entrées du journal (date, mood, titre)
  - Relation N:1 vers `Journal`, `Location`
  - Relation 1:N vers `JournalMedia`
- **`Tag`** : Étiquettes thématiques (couleur, description)
  - Relations N:1 vers `User`
  - Relations 1:N vers `Note`, `Task`
- **`Folder`** : Dossiers hiérarchiques (pour l'organisation)
  - Relation N:1 vers `User`, `Folder` (parent)
  - Relation 1:N vers `Folder` (sous-dossiers), `DriveDocument`
- **`DriveDocument`** (hérité de `AbstractFile`) : Documents uploadés dans les dossiers
  - Relation N:1 vers `Folder`, `User`
- **`Capsule`** : Capsules temporelles (titre, date de déverrouillage, secret)
  - Relation N:1 vers `User`
  - Relation 1:N vers `CapsuleMedia`
- **`Location`** : Lieux géolocalisés (latitude, longitude, address)
  - Relation N:1 vers `User`
- **`UserRequest`** : Requêtes utilisateur pour actions asynchrones (email verification, password reset)
  - Relation N:1 vers `User`
  - Propriétés: token, expires_at, is_used

**Classes abstraites:**
- **`AbstractTextEntry`** : Base pour entrées textuelles (content, created_at, updated_at, is_pinned)
- **`AbstractFile`** : Base pour fichiers uploadés (originalName, niceName, filePath, mimeType, size)

---

#### `src/Controller/` — Points d'entrée HTTP (Routage)
Récupère les requêtes HTTP, appelle les services, retourne les réponses.

**Organisation par domaine:**
- **`Controller/App/`** :
  - `NoteController` → Actions CRUD pour les notes (manage, create, edit, delete, tag_notes)
  - `TodoController` → Actions CRUD pour les tâches
  - `DriveController` → Actions CRUD pour les documents et dossiers
- **`Controller/Auth/`** :
  - `LoginController` → Authentification
  - `RegistrationController` → Création de compte
  - `ProfileController` → Profil utilisateur
  - `ResetPasswordController` → Réinitialisation du mot de passe
  - `UserRequestController` → Gestion des requêtes utilisateur (email verification, etc.)
- **`Controller/Admin/`** :
  - `UserController` → Gestion des utilisateurs
  - `EmailController` → Logs des emails envoyés
  - `LogController` → Logs de l'application
- **`Controller/Ajax/`** :
  - `NoteQuickActionsController` → Actions rapides sur les notes (AJAX)
  - `TodoQuickActionsController` → Actions rapides sur les tâches (AJAX)
  - `AjaxDriveController` → Actions rapides sur les documents (AJAX)
- **`LayoutController`**, **`DefaultController`** : Pages génériques

**Responsabilité:**
- Valider les entrées via les `Request`
- Déléguer à un `Service` pour la logique métier
- Retourner une réponse (HTML, JSON, Redirect)
- Implémenter la sécurité (vérifier que l'utilisateur peut accéder à sa propre ressource)

---

#### `src/Service/` — Logique métier
Centralise la logique complexe, isolée des controllers et repositories.

**Services principaux:**
- **`NoteService`** : CRUD, validation, filtrage des notes
- **`TodoService`** : CRUD, validation, gestion des priorités et états des tâches
- **`DriveService`** : Gestion des dossiers et documents
- **`TagService`** : Gestion des étiquettes
- **`UserService`** : Gestion des utilisateurs (avatar, profil, etc.)
- **`UserRequestService`** : Validation et traitement des UserRequest (email verification, password reset)
- **`LogService`** : Gestion des logs
- **`ArchiveTrashService`** : Archivage et suppression logique

**Traits réutilisables (depuis `core-ts`):**
- `LoggerTrait` : Log structuré
- `DatetimeTrait` : Manipulation de dates
- `ResponseTrait` : Construction de réponses normalisées

---

#### `src/Form/` — Validation et rendu des formulaires
Utilise Symfony FormType pour:
- Définir les champs d'une entité
- Valider les données entrantes
- Générer le HTML du formulaire

**Exemples:**
- **`Form/App/Note/NoteType`** : Formulaire complet d'une note (title, content, tag, color)
- **`Form/App/Note/NoteQuickAddType`** : Formulaire réduit pour ajout rapide
- **`Form/App/TodoType`** → Formulaire complet d'une tâche
- **`Form/Auth/Profile/ProfileInfoType`** → Profil utilisateur
- **`Form/Auth/LoginFormType`** → Connexion

---

#### `src/Repository/` — Accès aux données (Doctrine)
Classes pour requêtes spécialisées au-delà de la simple CRUD.

**Exemple:** `NoteRepository::searchNotesRaw()` — recherche textuelle des notes

---

#### `src/Enum/` — Énumérations (types contrôlés)
Énums Symfony pour les valeurs autorisées.

**Exemples:**
- `NoteColorEnum` : Couleurs disponibles pour les notes
- `TaskPriorityEnum` : Niveaux de priorité
- `TaskStateEnum` : États d'une tâche
- `MoodEnum` : Humeurs disponibles pour un journal
- `FileTypeEnum` : Types de fichiers supportés

---

#### `src/Security/` — Authentification & autorisation
Gestion de la sécurité (login, roles, permissions).

---

#### `src/Command/` — Commandes CLI
Scripts exécutables en ligne de commande (ex: `bin/console create-admin-user`).

---

#### `src/Twig/` — Extensions Twig personnalisées
Filtres et fonctions Twig custom.

---

#### `src/Utils/` — Utilitaires
Fonctions helpers réutilisables.

---

### 🎨 `assets/` — Frontend (TypeScript + SCSS)

#### `assets/ts/` — TypeScript (Interactivité)

**Structure:**
- **`main.ts`** : Point d'entrée, initialise les composants globaux
- **`pages/`** : Fichiers TypeScript spécialisés par page
  - `pages/app/note.ts` → Initialisation du filtre de notes, CustomSelector, etc.
  - `pages/app/todo.ts` → Gestion des actions rapides sur tâches
  - `pages/app/drive.ts` → Gestion des fichiers
  - `pages/auth/profile.ts` → Gestion du profil utilisateur
- **`features/`** : Composants réutilisables
  - `custom-selector.ts` → Dropdown riche avec recherche (sélection simple/multiple)
  - `selectable-field.ts` → Boutons radio/checkbox stylisés (cartes cliquables)
  - `index.ts` → Exports des features
- **`constantes/`** : Constantes applicatives
  - `routes.ts` → URLs des API et pages (générées depuis les routes Symfony)
  - `index.ts` → Autres constantes (messages, valeurs, etc.)

**Dépendances:**
- `core-ts` (package npm personnalisé) : Utilitaires ($, router, convertMarkdownToHtml, fetchAPI, etc.)

**Approche Progressive Enhancement:**
- Les composants TypeScript trouvent des éléments DOM via des sélecteurs (`data-*` attributes)
- Lisent les données du HTML généré par Symfony
- Enrichissent l'interface sans modifier la source de vérité (les `<input>` natifs restent synchronisés)

---

#### `assets/scss/` — Styles (SCSS)

**Fichiers:**
- **`custom.scss`** : Styles globaux personnalisés
- **`_selectable-card.scss`** : Styles pour SelectableField (bouttons radio/checkbox stylisés)
- **`_selectable-dropdown.scss`** : Styles pour CustomSelector (dropdown riche)

**Compilation:** Via `npm run build:scss` (esbuild + sass)

---

### 📝 `templates/` — Vues (Twig)

**Hiérarchie:**
- **`base.html.twig`** : Squelette global (header, nav, footer)
- **`layouts/dashboard.html.twig`** : Layout pour les pages de l'app
- **`auth/`** : Authentification (login, registration, reset password)
- **`admin/`** : Pages admin (users, emails, logs)
- **`app/`** : Pages applicatives
  - `app/note/` → Pages des notes (index, create, edit, _component.twig, _filter-form.twig)
  - `app/todo/` → Pages des tâches
  - `app/drive/` → Pages des fichiers
- **`components/`** : Composants réutilisables (formulaires, listes, filtres)
  - `_custom-dropdown-select.html.twig` → Rendu du CustomSelector
  - `_selectable-card.html.twig` → Rendu d'une carte sélectionnable
  - Etc.
- **`fragments/`** : Fragments Twig pour AJAX
- **`emails/`** : Templates pour les emails

---

### ⚙️ `config/` — Configuration Symfony

**Fichiers clés:**
- **`packages/framework.yaml`** : Configuration HTTP, sessions, formulaires
- **`packages/doctrine.yaml`** : Configuration base de données
- **`packages/security.yaml`** : Authentification, firewalls, access control
- **`packages/routing.yaml`** : Routage HTTP
- **`packages/twig.yaml`** : Configuration moteur template
- **`packages/mailer.yaml`** : Envoi d'emails
- **`packages/translation.yaml`** : Traductions i18n
- **`packages/validator.yaml`** : Validation des formulaires
- **`services.yaml`** : Enregistrement des services (DI container)
- **`routes.yaml`** : Déclaration des routes

---

## 🔄 Flux de données — Exemple: Créer une Note

```
1. Utilisateur accède GET /app/note/create
   └─> NoteController::create()

2. Controller crée un formulaire vide
   └─> $form = $this->createForm(NoteType::class, $note)

3. Template affiche le formulaire
   └─> render('app/note/create.html.twig')

4. Utilisateur remplit et soumet POST /app/note/create
   └─> NoteController::create() handle request

5. FormType valide les données
   └─> if ($form->isValid())

6. Controller appelle le service
   └─> $this->noteService->saveNote($note, true)

7. Service centralise la logique
   └─> NoteService::saveNote()
       ├─> Logs (si nécessaire)
       ├─> Entity Manager flush (persistance)
       └─> Return true/false

8. Controller redirige ou affiche erreur
   └─> if ($result) redirectToRoute(...) else addFlash('danger', ...)

9. TypeScript côté client (optionnel)
   └─> Note ajoutée, possible refresh ou notification
```

---

## 🎯 Principes architecturaux

### 1. **Séparation des responsabilités**
- **Controllers** : Coordination HTTP ↔ Service
- **Services** : Logique métier centralisée
- **Entities** : Représentation des données
- **FormTypes** : Validation des entrées
- **Templates** : Présentation

### 2. **Progressive Enhancement**
- Symfony génère du **HTML structuré** (formulaires, données)
- TypeScript **enrichit** sans remplacer
- Les `<input>` natifs restent la **source de vérité**
- Pas de "state" dupliqué côté client

### 3. **Sécurité par défaut**
- **Authentification** : Firewall Symfony + sessions
- **Autorisation** : Controllers vérifient que la ressource appartient à l'utilisateur
- **CSRF** : Tokens automatiques dans les formulaires
- **Validation** : Côté serveur (FormType + Service)

### 4. **DRY (Don't Repeat Yourself)**
- **Services** réutilisables
- **Components** Twig réutilisables
- **TypeScript features** réutilisables

### 5. **Extensibilité**
- Structure par **domaine** (App, Auth, Admin)
- Services découplés et injectés via DI
- Traits pour la réutilisation de code
- Enums pour les valeurs contrôlées

---

## 📊 Diagramme des dépendances

```
┌─────────────────────────────────────────────────────┐
│               BROWSER (Frontend)                     │
│  ┌──────────────────────────────────────────────────┐│
│  │ HTML (Twig) + TypeScript + SCSS                 ││
│  │ - CustomSelector (dropdown riche)               ││
│  │ - SelectableField (cartes cliquables)           ││
│  │ - Pages spécialisées (note, todo, drive)        ││
│  └──────────────────────────────────────────────────┘│
└────────────────────│──────────────────────────────────┘
                     │ HTTP (AJAX, Forms)
┌────────────────────▼──────────────────────────────────┐
│            SYMFONY APPLICATION (Backend)              │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Controllers (App, Auth, Admin, Ajax)            │ │
│  │ - Route → Handle request → Delegate to Service  │ │
│  └───────────────┬────────────────────────────────┐ │
│                  │                                │ │
│  ┌───────────────▼─────────────────┐ ┌───────────▼─┐ │
│  │ Services (Business Logic)       │ │ FormTypes   │ │
│  │ - NoteService                   │ │ - Validate  │ │
│  │ - TodoService                   │ │ - Render    │ │
│  │ - DriveService, etc.            │ │ - Data Map  │ │
│  └───────────────┬─────────────────┘ └─────────────┘ │
│                  │                                     │
│  ┌───────────────▼──────────────────────────────────┐ │
│  │ Doctrine ORM + Repositories                      │ │
│  │ - Query builder                                  │ │
│  │ - Entity persist/flush                          │ │
│  └───────────────┬──────────────────────────────────┘ │
│                  │                                     │
│  ┌───────────────▼──────────────────────────────────┐ │
│  │ Security (Authentication, Authorization)        │ │
│  │ - Firewall                                       │ │
│  │ - Access control                                 │ │
│  └──────────────────────────────────────────────────┘ │
└─────────────────┬──────────────────────────────────────┘
                  │ SQL
┌─────────────────▼──────────────────────────────────────┐
│            DATABASE                                     │
│ PostgreSQL / MySQL (Doctrine ORM)                       │
└────────────────────────────────────────────────────────┘
```

---

## 📦 Dépendances externes majeures

**Backend (Composer):**
- `symfony/*` : Framework HTTP
- `doctrine/orm` : ORM
- `symfony/security-bundle` : Authentification
- `twig/twig` : Moteur de templates
- `knplabs/knp-paginator` : Pagination
- `symfony/mailer` : Envoi d'emails
- `fagathe-dev/core-ts` : Library personnalisée avec Uploader, Breadcrumb, Traits, etc.

**Frontend (NPM):**
- `core-ts` (package GitHub) : Utilitaires ($, router, fetchAPI, convertMarkdownToHtml, SelectableField, etc.)
- `esbuild` : Compilateur TypeScript
- `sass` : Compilateur SCSS
- `concurrently` : Exécution parallèle des tasks

---

## 🚀 Points d'entrée principaux

- **Web public** : `public/index.php` (bootstrape Symfony via `Kernel.php`)
- **CLI** : `bin/console` (Symfony console)
- **Frontend** : `assets/ts/main.ts` compilé en `public/js/app.js`
- **Styles** : `assets/scss/custom.scss` compilé en `public/css/app.css`

---

## ✨ À retenir

- **Symfony** gère toute la logique applicative
- **Twig** génère le HTML structuré
- **TypeScript** enrichit uniquement (pas de replacement)
- **Services** centralisent la logique métier
- **Progressive Enhancement** = robustesse + UX moderne
- **Sécurité** = authentification + autorisation au niveau Symfony
- **DI Container** = injection de dépendances pour la flexibilité
