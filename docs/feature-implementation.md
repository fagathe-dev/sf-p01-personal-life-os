# 🚀 Guide : Implémenter une nouvelle fonctionnalité

Ce guide présente le workflow complet pour ajouter une nouvelle fonctionnalité dans **Personal Life OS** en suivant les bonnes pratiques de l'architecture existante.

---

## 📋 Résumé du workflow

1. **Valider les Entités Doctrine existantes** — Partir du modèle déjà en place
2. **Créer la Migration** — Évoluer le schéma de base de données
3. **Créer/valider le Repository** — Requêtes spécialisées + méthodes `save`/`remove`
4. **Créer les Enums** — Énumérations si des valeurs contrôlées sont nécessaires
5. **Créer les FormTypes** — Validation et rendu des formulaires
6. **Créer le Service** — Logique métier centralisée
7. **Créer le Controller** — Points d'entrée HTTP
8. **Créer les Templates Twig** — Présentation
9. **Créer les composants TypeScript** — Interactivité (optionnel)
10. **Créer les routes constants** — Exporter les URLs vers le front
11. **Compiler et tester** — Build et validation

---

## 🎯 Cas d'étude : Implémenter `/app/capsule`

La **Capsule** est une entité existante (capsule temporelle), mais nous allons détailler le processus complet d'implémentation.

### **Contexte:** 
- Entités: `Capsule` et `CapsuleMedia` (relation parent/enfants déjà en place)
- Route: `/app/capsule`
- Contrôleur: `CapsuleController`
- Fonctionnalités: CRUD + gestion des médias + affichage conditionnel selon la date

---

## ⏯️ ÉTAPE 1 : Valider le modèle Doctrine existant

Dans ce projet, les entités sont déjà créées en amont.

**Fichiers existants :**
- `src/Entity/Capsule.php`
- `src/Entity/CapsuleMedia.php`

**Ce qu'il faut valider avant d'implémenter la feature `/app/capsule`:**
- La relation `Capsule (1) -> (N) CapsuleMedia` est bien définie.
- `CapsuleMedia` hérite de `AbstractFile` (métadonnées fichier + chemin + MIME + taille).
- Le propriétaire (`owner`) est présent sur `Capsule` pour filtrer les données utilisateur.
- Les champs minimum de `Capsule` sont exploitables côté UI: `title`, `unlockAt`, `isOpened`, `secret`.

**Important :**
- Cette étape ne crée pas d'entité.
- On part des entités existantes et on construit la couche applicative autour (Repository, Service, Controller, Form, Templates, TS).

---

## ⏯️ ÉTAPE 2 : Créer une Migration Doctrine (uniquement si le schéma évolue)

**Commande:**
```bash
php bin/console doctrine:make:migration
```

Cela génère automatiquement un fichier dans `migrations/`. Vérifier et ajuster si nécessaire.

Si les entités `Capsule` et `CapsuleMedia` ne changent pas, cette étape est à ignorer.

**Exécuter la migration:**
```bash
php bin/console doctrine:migrations:migrate
```

---

## ⏯️ ÉTAPE 3 : Créer le Repository (si nécessaire)

**Fichier:** `src/Repository/CapsuleRepository.php`

```php
<?php

namespace App\Repository;

use App\Entity\Capsule;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Capsule>
 */
final class CapsuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Capsule::class);
    }

  /**
   * Persiste une capsule.
   */
  public function save(Capsule $capsule, bool $flush = true): bool
  {
    try {
      $this->getEntityManager()->persist($capsule);

      if ($flush) {
        $this->getEntityManager()->flush();
      }

      return true;
    } catch (\Throwable) {
      return false;
    }
  }

  /**
   * Supprime une capsule.
   */
  public function remove(Capsule $capsule, bool $flush = true): bool
  {
    try {
      $this->getEntityManager()->remove($capsule);

      if ($flush) {
        $this->getEntityManager()->flush();
      }

      return true;
    } catch (\Throwable) {
      return false;
    }
  }

    /**
     * Récupère toutes les capsules d'un utilisateur
     */
    public function findByUser(User $user, ?string $sortBy = 'unlockAt'): array
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('c.' . $sortBy, 'DESC');

        return $query->getQuery()->getResult();
    }

    /**
     * Récupère les capsules déverrouillées (date passée et non encore ouvertes)
     */
    public function findUnlockedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->andWhere('c.unlockAt <= :now')
            ->andWhere('c.isOpened = false')
            ->setParameter('owner', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.unlockAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les capsules verrouillées (date future)
     */
    public function findLockedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->andWhere('c.unlockAt > :now')
            ->setParameter('owner', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.unlockAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

**Points clés:**
- Requêtes spécialisées pour les cas d'usage
- Filterage par User (sécurité)
- Filtrage par état (verrouillée, déverrouillée, ouverte)
- Chaque repository métier doit exposer `save()` et `remove()` pour standardiser l'écriture/suppression.

### ✅ Vérification obligatoire sur chaque Repository

Avant de considérer une feature terminée, vérifier que chaque repository métier concerné contient:

- Une méthode `save(Entity $entity, bool $flush = true): bool`
- Une méthode `remove(Entity $entity, bool $flush = true): bool`
- Une gestion d'erreur cohérente (try/catch + retour booléen)

Commande utile pour audit rapide:

```bash
rg "function save\(|function remove\(" src/Repository/*.php
```

Si un repository ne possède pas ces méthodes (ex: nouveau repository), les ajouter avant l'implémentation du service.

---

## ⏯️ ÉTAPE 4 : Créer les Enums (si nécessaire)

**Fichier:** `src/Enum/Capsule/CapsuleStateEnum.php`

```php
<?php

namespace App\Enum\Capsule;

enum CapsuleStateEnum: string
{
    case LOCKED = 'locked';           // Date future, capsule verrouillée
    case READY_TO_OPEN = 'ready';     // Date passée, pas encore ouverte
    case OPENED = 'opened';           // Capsule ouverte

    public function label(): string
    {
        return match ($this) {
            self::LOCKED => '🔒 Verrouillée',
            self::READY_TO_OPEN => '🔓 Prête à ouvrir',
            self::OPENED => '✅ Ouverte',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOCKED => 'primary',
            self::READY_TO_OPEN => 'warning',
            self::OPENED => 'success',
        };
    }
}
```

---

## ⏯️ ÉTAPE 5 : Créer les FormTypes

### FormType principal : `CapsuleType`

**Fichier:** `src/Form/App/Capsule/CapsuleType.php`

```php
<?php

namespace App\Form\App\Capsule;

use App\Entity\Capsule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CapsuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la capsule',
                'attr' => [
                    'placeholder' => 'Ex: Message pour moi en 2030',
                    'maxlength' => 255,
                    'class' => 'form-control',
                ],
            ])
            ->add('unlockAt', DateTimeType::class, [
                'label' => 'Date et heure d\'ouverture',
                'widget' => 'single_text',
                'attr' => [
                    'type' => 'datetime-local',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\GreaterThan([
                        'value' => 'now',
                        'message' => 'La date d\'ouverture doit être dans le futur.',
                    ]),
                ],
            ])
            ->add('secret', TextareaType::class, [
                'label' => 'Message secret (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Écrivez votre message secret...',
                    'rows' => 5,
                    'class' => 'form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Capsule::class,
        ]);
    }
}
```

### FormType rapide : `CapsuleQuickAddType` (optionnel)

```php
<?php

namespace App\Form\App\Capsule;

use App\Entity\Capsule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CapsuleQuickAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'Nouvelle capsule...'],
            ])
            ->add('unlockAt', DateTimeType::class, [
                'label' => false,
                'widget' => 'single_text',
                'attr' => ['type' => 'datetime-local'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Capsule::class]);
    }
}
```

---

## ⏯️ ÉTAPE 6 : Créer le Service

**Fichier:** `src/Service/CapsuleService.php`

```php
<?php

namespace App\Service;

use App\Entity\Capsule;
use App\Entity\User;
use App\Enum\Capsule\CapsuleStateEnum;
use App\Repository\CapsuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Fagathe\CorePhp\Breadcrumb\Breadcrumb;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

final class CapsuleService
{
    use LoggerTrait, DatetimeTrait;

    public function __construct(
        private readonly CapsuleRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Récupère l'utilisateur courant
     */
    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Liste toutes les capsules de l'utilisateur
     */
    public function manage(array $filters = []): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return ['capsules' => []];
        }

        $all_capsules = $this->repository->findByUser($user);

        // Grouper par état
        $locked = [];
        $ready = [];
        $opened = [];

        foreach ($all_capsules as $capsule) {
            $state = $this->getCapsuleState($capsule);
            match ($state) {
                CapsuleStateEnum::LOCKED => $locked[] = $capsule,
                CapsuleStateEnum::READY_TO_OPEN => $ready[] = $capsule,
                CapsuleStateEnum::OPENED => $opened[] = $capsule,
            };
        }

        return [
            'lockedCapsules' => $locked,
            'readyCapsules' => $ready,
            'openedCapsules' => $opened,
            'totalCapsules' => count($all_capsules),
        ];
    }

    /**
     * Détermine l'état d'une capsule
     */
    public function getCapsuleState(Capsule $capsule): CapsuleStateEnum
    {
        if ($capsule->isOpened()) {
            return CapsuleStateEnum::OPENED;
        }

        if (new \DateTimeImmutable() >= $capsule->getUnlockAt()) {
            return CapsuleStateEnum::READY_TO_OPEN;
        }

        return CapsuleStateEnum::LOCKED;
    }

    /**
     * Crée et valide une nouvelle capsule
     */
    public function createCapsule(Capsule $capsule): bool
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                $this->logger->error('Utilisateur introuvable pour créer une capsule');
                return false;
            }

            $capsule->setOwner($user);
            $this->em->persist($capsule);
            $this->em->flush();

            $this->logger->info('Capsule créée', ['id' => $capsule->getId(), 'userId' => $user->getId()]);
            return true;
        } catch (Throwable $e) {
            $this->logger->error('Erreur lors de la création de la capsule', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Met à jour une capsule
     */
    public function updateCapsule(Capsule $capsule): bool
    {
        try {
            $capsule->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();

            $this->logger->info('Capsule mise à jour', ['id' => $capsule->getId()]);
            return true;
        } catch (Throwable $e) {
            $this->logger->error('Erreur lors de la mise à jour', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Ouvre une capsule (marque comme ouverte)
     */
    public function openCapsule(Capsule $capsule): bool
    {
        if (!$capsule->canBeOpened()) {
            $this->logger->warning('Tentative d\'ouverture d\'une capsule verrouillée', ['id' => $capsule->getId()]);
            return false;
        }

        try {
            $capsule->setIsOpened(true);
            $capsule->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();

            $this->logger->info('Capsule ouverte', ['id' => $capsule->getId()]);
            return true;
        } catch (Throwable $e) {
            $this->logger->error('Erreur lors de l\'ouverture', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Supprime une capsule
     */
    public function deleteCapsule(Capsule $capsule): bool
    {
        try {
            $this->em->remove($capsule);
            $this->em->flush();

            $this->logger->info('Capsule supprimée', ['id' => $capsule->getId()]);
            return true;
        } catch (Throwable $e) {
            $this->logger->error('Erreur lors de la suppression', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Génère le breadcrumb
     */
    public function breadcrumb(array $items = []): array
    {
        $breadcrumb = new Breadcrumb();
        $breadcrumb->addItem(new BreadcrumbItem('Accueil', $this->urlGenerator->generate('app_default_index')))
                   ->addItem(new BreadcrumbItem('Capsules', $this->urlGenerator->generate('app_capsule_manage')))
                   ->addItems($items);

        return $breadcrumb->toArray();
    }
}
```

**Points clés:**
- Traits réutilisables (`LoggerTrait`, `DatetimeTrait`)
- Gestion d'erreurs et logging
- Vérification de sécurité (propriétaire)
- Retour simple (bool) pour les actions, tableau pour les listes

---

## ⏯️ ÉTAPE 7 : Créer le Controller

**Fichier:** `src/Controller/App/CapsuleController.php`

```php
<?php

namespace App\Controller\App;

use App\Entity\Capsule;
use App\Form\App\Capsule\CapsuleType;
use App\Service\CapsuleService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/app/capsule', name: 'app_capsule_')]
final class CapsuleController extends AbstractController
{
    public function __construct(
        private readonly CapsuleService $capsuleService,
    ) {
    }

    /**
     * Affiche la liste de toutes les capsules
     */
    #[Route(path: '', name: 'manage', methods: ['GET'])]
    public function manage(): Response
    {
        $data = $this->capsuleService->manage();
        $data['breadcrumb'] = $this->capsuleService->breadcrumb();

        return $this->render('app/capsule/index.html.twig', $data);
    }

    /**
     * Formulaire de création d'une nouvelle capsule
     */
    #[Route(path: '/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $capsule = new Capsule();
        $form = $this->createForm(CapsuleType::class, $capsule);
        $form->handleRequest($request);

        $breadcrumb = $this->capsuleService->breadcrumb([
            new \Fagathe\CorePhp\Breadcrumb\BreadcrumbItem('Créer une capsule'),
        ]);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->capsuleService->createCapsule($capsule)) {
                $this->addFlash('success', 'Capsule créée avec succès !');
                return $this->redirectToRoute('app_capsule_manage');
            }
            $this->addFlash('danger', 'Erreur lors de la création de la capsule.');
        }

        return $this->render('app/capsule/create.html.twig', compact('form', 'breadcrumb'));
    }

    /**
     * Formulaire d'édition d'une capsule
     */
    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        #[MapEntity(mapping: ['id' => 'id'])] Capsule $capsule,
        Request $request,
    ): Response {
        // Vérification de sécurité
        if ($capsule->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette capsule.');
        }

        $form = $this->createForm(CapsuleType::class, $capsule);
        $form->handleRequest($request);

        $breadcrumb = $this->capsuleService->breadcrumb([
            new \Fagathe\CorePhp\Breadcrumb\BreadcrumbItem('Éditer'),
        ]);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->capsuleService->updateCapsule($capsule)) {
                $this->addFlash('success', 'Capsule mise à jour !');
                return $this->redirectToRoute('app_capsule_manage');
            }
            $this->addFlash('danger', 'Erreur lors de la mise à jour.');
        }

        return $this->render('app/capsule/edit.html.twig', compact('form', 'capsule', 'breadcrumb'));
    }

    /**
     * Détail d'une capsule (avec option d'ouverture si prête)
     */
    #[Route(path: '/{id}', name: 'view', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function view(#[MapEntity(mapping: ['id' => 'id'])] Capsule $capsule): Response
    {
        if ($capsule->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $state = $this->capsuleService->getCapsuleState($capsule);
        $breadcrumb = $this->capsuleService->breadcrumb([
            new \Fagathe\CorePhp\Breadcrumb\BreadcrumbItem($capsule->getTitle()),
        ]);

        return $this->render('app/capsule/view.html.twig', compact('capsule', 'state', 'breadcrumb'));
    }

    /**
     * Ouvrir une capsule (AJAX ou POST)
     */
    #[Route(path: '/{id}/open', name: 'open', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function open(#[MapEntity(mapping: ['id' => 'id'])] Capsule $capsule, Request $request): Response
    {
        if ($capsule->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->capsuleService->openCapsule($capsule)) {
            $this->addFlash('success', '🎉 Capsule ouverte !');
        } else {
            $this->addFlash('warning', 'Cette capsule n\'est pas prête à être ouverte.');
        }

        return $this->redirectToRoute('app_capsule_view', ['id' => $capsule->getId()]);
    }

    /**
     * Supprimer une capsule
     */
    #[Route(path: '/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(#[MapEntity(mapping: ['id' => 'id'])] Capsule $capsule): Response
    {
        if ($capsule->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->capsuleService->deleteCapsule($capsule)) {
            $this->addFlash('success', 'Capsule supprimée.');
            return $this->redirectToRoute('app_capsule_manage');
        }

        $this->addFlash('danger', 'Erreur lors de la suppression.');
        return $this->redirectToRoute('app_capsule_view', ['id' => $capsule->getId()]);
    }
}
```

**Points clés:**
- Routes bien nommées (`app_capsule_manage`, `app_capsule_create`, etc.)
- Vérification d'appartenance à l'utilisateur
- Délégation au service
- Feedback utilisateur (flash messages)

---

## ⏯️ ÉTAPE 8 : Créer les Templates Twig

### Template principal : `templates/app/capsule/index.html.twig`

```twig
{# Affiche toutes les capsules groupées par état #}
{% extends 'layouts/dashboard.html.twig' %}

{% block title %}Mes Capsules Temporelles{% endblock %}

{% block content %}
  <div class="container-fluid py-4" style="max-width: 1200px;">
    
    {# En-tête #}
    <div class="row mb-4 align-items-center">
      <div class="col">
        <h1 class="h3 mb-0 text-gray-800">🕰️ Capsules Temporelles</h1>
      </div>
      <div class="col-auto">
        <a href="{{ path('app_capsule_create') }}" class="btn btn-primary shadow-sm">
          <i class="ri-add-line align-middle me-1"></i> Nouvelle capsule
        </a>
      </div>
    </div>

    {# Capsules verrouillées (futures) #}
    {% if lockedCapsules %}
      <section class="mb-5">
        <h5 class="text-muted mb-3">🔒 Verrouillées ({{ lockedCapsules|length }})</h5>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          {% for capsule in lockedCapsules %}
            {% include 'app/capsule/_card.html.twig' with {'capsule': capsule, 'state': 'locked'} %}
          {% endfor %}
        </div>
      </section>
    {% endif %}

    {# Capsules prêtes à ouvrir #}
    {% if readyCapsules %}
      <section class="mb-5">
        <h5 class="text-warning mb-3">🔓 Prêtes à ouvrir ({{ readyCapsules|length }})</h5>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          {% for capsule in readyCapsules %}
            {% include 'app/capsule/_card.html.twig' with {'capsule': capsule, 'state': 'ready'} %}
          {% endfor %}
        </div>
      </section>
    {% endif %}

    {# Capsules ouvertes #}
    {% if openedCapsules %}
      <section class="mb-5">
        <h5 class="text-success mb-3">✅ Ouvertes ({{ openedCapsules|length }})</h5>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          {% for capsule in openedCapsules %}
            {% include 'app/capsule/_card.html.twig' with {'capsule': capsule, 'state': 'opened'} %}
          {% endfor %}
        </div>
      </section>
    {% endif %}

    {# Aucune capsule #}
    {% if totalCapsules == 0 %}
      <div class="alert alert-info text-center py-5">
        <h5>Pas de capsules pour le moment</h5>
        <p>Commencez par <a href="{{ path('app_capsule_create') }}">créer votre première capsule</a> ✨</p>
      </div>
    {% endif %}
  </div>
{% endblock %}
```

### Composant card : `templates/app/capsule/_card.html.twig`

```twig
{# Affiche une capsule sous forme de carte #}
<div class="col">
  <div class="card shadow-sm h-100 border-{{ state == 'locked' ? 'primary' : (state == 'ready' ? 'warning' : 'success') }}">
    
    <div class="card-body">
      <h6 class="card-title">{{ capsule.title }}</h6>
      <p class="card-text text-muted small">
        🕐 {{ capsule.unlockAt|format_datetime(locale='fr', pattern="dd MMM yyyy à HH:mm") }}
      </p>
      
      {% if capsule.secret %}
        <p class="card-text small">
          <i class="ri-lock-line"></i> Contient un message secret
        </p>
      {% endif %}
    </div>

    <div class="card-footer bg-light">
      <a href="{{ path('app_capsule_view', {'id': capsule.id}) }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="ri-eye-line"></i> Voir
      </a>
      
      {% if state != 'opened' %}
        <a href="{{ path('app_capsule_edit', {'id': capsule.id}) }}" class="btn btn-sm btn-outline-primary">
          <i class="ri-edit-line"></i> Éditer
        </a>
      {% endif %}
    </div>
  </div>
</div>
```

### Page de détail : `templates/app/capsule/view.html.twig`

```twig
{% extends 'layouts/dashboard.html.twig' %}

{% block title %}{{ capsule.title }}{% endblock %}

{% block content %}
  <div class="container-fluid py-4" style="max-width: 800px;">
    
    {# Détail de la capsule #}
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2>{{ capsule.title }}</h2>
        
        <div class="alert alert-{{ state == 'locked' ? 'info' : (state == 'ready' ? 'warning' : 'success') }} mt-3">
          {% if state == 'locked' %}
            🔒 Cette capsule s'ouvrira le {{ capsule.unlockAt|format_datetime(locale='fr') }}
          {% elseif state == 'ready' %}
            🔓 Cette capsule est prête à être ouverte !
          {% else %}
            ✅ Capsule ouverte le {{ capsule.updatedAt|format_datetime(locale='fr') }}
          {% endif %}
        </div>

        {% if capsule.secret %}
          <div class="mt-4 p-3 bg-light border rounded">
            <h5>💌 Message secret</h5>
            <p>{{ capsule.secret|nl2br }}</p>
          </div>
        {% endif %}

        {# Médias associés #}
        {% if capsule.medias %}
          <div class="mt-4">
            <h5>📎 Médias ({{ capsule.medias|length }})</h5>
            <div class="row g-3 mt-1">
              {% for media in capsule.medias %}
                <div class="col-md-6">
                  <div class="card">
                    {# Rendu du média selon son type #}
                    <div class="card-body">{{ media.niceName }}</div>
                  </div>
                </div>
              {% endfor %}
            </div>
          </div>
        {% endif %}
      </div>
    </div>

    {# Actions #}
    <div class="d-flex gap-2">
      {% if state == 'ready' %}
        <form method="POST" action="{{ path('app_capsule_open', {'id': capsule.id}) }}" style="display:inline;">
          <button type="submit" class="btn btn-warning">
            <i class="ri-lock-unlock-line"></i> Ouvrir la capsule
          </button>
        </form>
      {% endif %}

      {% if state != 'opened' %}
        <a href="{{ path('app_capsule_edit', {'id': capsule.id}) }}" class="btn btn-primary">
          <i class="ri-edit-line"></i> Éditer
        </a>
      {% endif %}

      <form method="POST" action="{{ path('app_capsule_delete', {'id': capsule.id}) }}" style="display:inline;" 
            onsubmit="return confirm('Êtes-vous sûr ?');">
        <button type="submit" class="btn btn-danger">
          <i class="ri-delete-bin-line"></i> Supprimer
        </button>
      </form>

      <a href="{{ path('app_capsule_manage') }}" class="btn btn-outline-secondary ms-auto">
        <i class="ri-arrow-left-line"></i> Retour
      </a>
    </div>
  </div>
{% endblock %}
```

### Formulaire : `templates/app/capsule/create.html.twig`

```twig
{% extends 'layouts/dashboard.html.twig' %}

{% block title %}Créer une capsule{% endblock %}

{% block content %}
  <div class="container-fluid py-4" style="max-width: 600px;">
    
    <h1 class="h3 mb-4">🕰️ Nouvelle Capsule Temporelle</h1>

    {{ form_start(form, {'attr': {'class': 'card shadow-sm p-4'}}) }}
      {{ form_widget(form) }}
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="ri-check-line"></i> Créer
        </button>
        <a href="{{ path('app_capsule_manage') }}" class="btn btn-outline-secondary">
          Annuler
        </a>
      </div>
    {{ form_end(form) }}
  </div>
{% endblock %}
```

---

## ⏯️ ÉTAPE 9 : Créer les composants TypeScript (optionnel)

**Fichier:** `assets/ts/pages/app/capsule.ts`

```typescript
import { $ } from 'core-ts';

document.addEventListener('DOMContentLoaded', () => {
  // Initialisation spécifique aux capsules
  
  // Exemple: Afficher une notification quand on ouvre une capsule
  const openButtons = $<HTMLElement>('[data-capsule-action="open"]', true);
  if (openButtons) {
    openButtons.forEach((button) => {
      button.addEventListener('click', () => {
        // Afficher une animation ou confirmation
        console.log('Ouverture de la capsule...');
      });
    });
  }

  // Exemple: Format de date locale
  const dateElements = $<HTMLElement>('[data-capsule-unlock-date]', true);
  if (dateElements) {
    dateElements.forEach((el) => {
      const isoDate = el.getAttribute('data-capsule-unlock-date');
      if (isoDate) {
        const date = new Date(isoDate);
        const localeDate = date.toLocaleDateString('fr-FR', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        });
        el.textContent = localeDate;
      }
    });
  }
});
```

**Importer dans** `assets/ts/main.ts` :
```typescript
import '@/pages/app/capsule';
```

---

## ⏯️ ÉTAPE 10 : Exporter les routes dans les constantes

**Fichier:** `assets/ts/constantes/routes.ts`

```typescript
export const ROUTES = {
  APP: {
    CAPSULE: {
      INDEX: '/app/capsule',
      CREATE: '/app/capsule/create',
      EDIT: (id: number) => `/app/capsule/${id}/edit`,
      VIEW: (id: number) => `/app/capsule/${id}`,
      OPEN: (id: number) => `/app/capsule/${id}/open`,
      DELETE: (id: number) => `/app/capsule/${id}/delete`,
    },
    // ... autres routes
  },
} as const;
```

Utilisation en TypeScript:
```typescript
const viewUrl = ROUTES.APP.CAPSULE.VIEW(capsule.id);
```

---

## ⏯️ ÉTAPE 11 : Compiler et tester

### Compiler le TypeScript et SCSS
```bash
npm run build
# ou pour le développement avec watch
npm run dev
```

### Accéder à la route
```
http://localhost:8000/app/capsule
```

### Tester les fonctionnalités
1. ✅ Créer une capsule
2. ✅ Éditer une capsule
3. ✅ Voir une capsule
4. ✅ Vérifier les états (verrouillée, prête, ouverte)
5. ✅ Tester l'ouverture d'une capsule
6. ✅ Supprimer une capsule

---

## 📋 Checklist d'implémentation

- [ ] Entités existantes validées (`Capsule`, `CapsuleMedia`)
- [ ] Migration créée et exécutée
- [ ] Repository avec requêtes spécialisées
- [ ] Enums créés
- [ ] FormTypes créés (complet + quick)
- [ ] Service créé avec logique métier
- [ ] Controller créé avec toutes les actions
- [ ] Templates créées (index, create, edit, view, _card)
- [ ] TypeScript/constantes des routes
- [ ] Tests unitaires (optionnel mais recommandé)
- [ ] Compilation build OK
- [ ] Routes testées manuellement

---

## 🎯 Points clés à retenir

1. **Service = logique métier** — Centralise tout, réutilisable
2. **Controller = coordination** — Prend requête, appelle service, retourne réponse
3. **FormType = validation** — Définit les règles et le rendu
4. **Templates = présentation** — Utilise les données du controller
5. **TypeScript = UX** — Enrichit l'interface sans remplacer le HTML
6. **Sécurité = partout** — Vérifier l'appartenance à l'utilisateur
7. **Logging = traçabilité** — Logguer les actions importantes
8. **Progressive Enhancement** — HTML structuré → TypeScript enrichit

---

## 📚 Références rapides

- **Symfony Docs**: https://symfony.com/doc/current/
- **Doctrine ORM**: https://www.doctrine-project.org/
- **Twig**: https://twig.symfony.com/
- **TypeScript**: https://www.typescriptlang.org/
- **FormTypes**: https://symfony.com/doc/current/forms.html