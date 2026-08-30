# Spécifications fonctionnelles — état actuel

> Photo fonctionnelle de la version présente dans le dépôt.  
> Ce document décrit ce qui est implémenté dans `src/`, `templates/` et
> `assets/`. Il ne constitue pas une spécification des évolutions graphiques
> ou fonctionnelles à venir.

## 1. Périmètre actuel

Personal Life OS est une application web Symfony rendue côté serveur avec
Twig. Les fonctionnalités actuellement exposées sont :

- l'accès à l'application par authentification ;
- la création de compte et la confirmation d'adresse e-mail ;
- la récupération et la réinitialisation du mot de passe ;
- la consultation et la modification du profil ;
- la gestion de l'avatar ;
- la gestion des préférences d'affichage ;
- la gestion des étiquettes personnelles ;
- la gestion de tâches/todos ;
- des actions rapides asynchrones sur les tâches ;
- des fonctions d'administration pour les utilisateurs, les logs et la
  prévisualisation des e-mails.

Les domaines « notes », « drive/documents », « journal », « capsules »,
« lieux » et leurs médias ne sont pas présents dans les contrôleurs, entités ou
templates de cette version, même s'ils sont mentionnés dans certains documents
historiques.

## 2. Acteurs et accès

### Visiteur non authentifié

Un visiteur peut :

- consulter la page d'accueil ;
- afficher le formulaire de connexion ;
- créer un compte ;
- demander un lien de réinitialisation de mot de passe ;
- utiliser un lien de confirmation de compte ou de réinitialisation valide.

Les pages d'authentification redirigent un utilisateur déjà connecté vers
l'application.

### Utilisateur authentifié

Un utilisateur peut accéder à son profil, à ses étiquettes et à ses propres
tâches. Les ressources personnelles sont associées au compte connecté.

### Administrateur

Un utilisateur disposant de `ROLE_ADMIN` voit les entrées d'administration dans
la barre latérale et peut gérer les comptes utilisateurs, consulter les logs
et prévisualiser les e-mails.

## 3. Authentification et compte

### Connexion et déconnexion

- Route de connexion : `/auth/login`.
- Connexion par identifiant (champ nommé `username`) et mot de passe.
- Option « se souvenir de moi ».
- Les erreurs d'authentification sont affichées dans le formulaire.
- Le dernier identifiant saisi est reproposé après un échec.
- Déconnexion par `/auth/logout`.

### Inscription

- Route : `/auth/registration`.
- Champs : nom d'utilisateur, adresse e-mail, mot de passe avec confirmation
  et acceptation des conditions.
- La création est effectuée par le service utilisateur.
- Un e-mail de confirmation est envoyé dans le cadre du processus de compte.
- Route de confirmation : `/auth/registration/confirm-account/{token}`.

### Mot de passe oublié

Le parcours principal est disponible sous `/auth/forgot-password` :

1. l'utilisateur saisit son adresse e-mail ;
2. un lien avec jeton est envoyé lorsque nécessaire ;
3. l'utilisateur ouvre `/auth/forgot-password/action/{token}` ;
4. il saisit et confirme un nouveau mot de passe ;
5. il est redirigé vers la connexion.

Le message de demande est volontairement identique qu'un compte existe ou non
(protection contre l'énumération des comptes). Les jetons invalides ou expirés
sont refusés.

Les anciennes routes `/auth/reset-password/request` et
`/auth/reset-password/{token}` sont conservées pour compatibilité. Elles
reproduisent le même parcours et sont marquées comme dépréciées dans le code.

## 4. Profil utilisateur

Le profil est accessible sous `/auth/profile`. Il affiche l'avatar, le nom
d'utilisateur et quatre onglets :

### Informations

Le formulaire permet de modifier le nom d'utilisateur, le prénom et le nom.
La sauvegarde est validée côté serveur et accompagnée d'un message flash.

### Préférences

Un sélecteur propose les thèmes disponibles (`auto` et les valeurs définies par
`ThemePreferenceEnum`). La valeur affichée est lue depuis les préférences
utilisateur.

Dans l'état actuel du code, ce sélecteur ne possède pas de traitement de
sauvegarde côté serveur ou côté TypeScript : l'affichage de la préférence est
présent, mais sa modification n'est pas fonctionnellement branchée.

### Paramètres de sécurité

- demande de changement d'adresse e-mail ;
- envoi d'un lien de confirmation à la nouvelle adresse ;
- confirmation par `/auth/profile/confirm-email/{token}` ;
- reconnexion automatique après confirmation réussie ;
- changement du mot de passe avec contrôle du mot de passe actuel ;
- reconnexion de la session après changement du mot de passe ;
- suppression définitive du compte avec confirmation navigateur et jeton CSRF.

La suppression invalide la session et redirige vers la connexion. Elle entraîne
la perte irréversible des données du compte.

### Avatar

L'utilisateur peut sélectionner une image depuis son profil. L'interface
TypeScript :

- limite le fichier à 15 Mo ;
- accepte BMP, WebP, SVG, TIFF, PNG, GIF, ICO et JPEG ;
- envoie le fichier vers `/auth/profile/upload/avatar` ;
- met à jour immédiatement l'aperçu du profil et celui de la barre supérieure ;
- affiche une erreur en cas d'échec.

## 5. Étiquettes personnelles

Les étiquettes sont gérées depuis l'onglet « Étiquettes » du profil.

Une étiquette possède un nom, une description facultative et une couleur
issue de `ColorEnum`. Les opérations disponibles sont :

- lister les étiquettes du compte, triées par nom ;
- créer une étiquette ;
- modifier une étiquette ;
- supprimer une étiquette après confirmation ;
- retirer l'étiquette des tâches qui la référencent lors de sa suppression.

Les formulaires utilisent le rendu Symfony. Le formulaire de sélection enrichi
permet une sélection unique ou multiple selon le contexte, avec recherche et
synchronisation du `<select>` natif.

## 6. Tâches / Tasks

### Liste et navigation

La page principale est `/app/todo`.

- Les tâches de l'utilisateur connecté sont affichées.
- Elles sont séparées visuellement en tâches actives et terminées.
- Les tâches actives sont triées par échéance croissante ; sans échéance, les
  plus récentes sont prioritaires.
- Les tâches terminées sont placées après les actives et triées par date de
  création décroissante.
- Un état vide est affiché lorsqu'il n'y a aucune tâche active.
- La section des tâches terminées peut être repliée.
- Un filtre permet d'afficher toutes les tâches ou celles d'une étiquette.
- Le filtre par étiquette est disponible sous `/app/todo/tag/{id}`.

### Création

Deux modes existent :

- ajout rapide depuis la liste, avec le titre et soumission du formulaire ;
- formulaire complet sous `/app/todo/create`.

Le formulaire complet permet de renseigner :

- le titre ;
- la description ;
- l'état d'achèvement ;
- une échéance prédéfinie (« Aujourd'hui », « Demain », « Cette semaine »,
  « La semaine prochaine ») ;
- la priorité (« Faible », « Moyenne », « Haute », « Critique ») ;
- l'état (« À faire », « En cours », « Terminé ») ;
- une étiquette facultative.

Les échéances prédéfinies sont converties en date à 23:59:59 dans le fuseau
`Europe/Paris`.

### Modification et suppression

- Une tâche est modifiable sous `/app/todo/{id}/edit`.
- Le clic sur le contenu d'une tâche ouvre son formulaire d'édition.
- La suppression est disponible dans le menu d'actions de la tâche.
- La suppression exige une requête POST et un jeton CSRF.
- Une tâche ne peut être modifiée ou supprimée que par son propriétaire.

### Actions rapides

Le script `assets/ts/pages/app/todo.ts` enrichit la liste sans rechargement
complet :

- cocher/décocher une tâche appelle
  `/ajax/todo/{id}/toggle-completed` ;
- le titre est barré ou réactivé ;
- la tâche est déplacée entre les listes active et terminée ;
- les compteurs et états vides sont recalculés ;
- épingler/désépingler appelle `/ajax/todo/{id}/toggle-pinned` et met à jour
  l'icône ;
- une erreur réseau ou métier restaure l'état visuel de la case.

L'endpoint `/ajax/todo/quick-add` accepte un payload JSON contenant `title` et
retourne le HTML Twig d'une tâche nouvellement créée. Il est disponible côté
backend, mais le formulaire actuellement affiché utilise encore le POST HTML
classique et ne l'appelle pas depuis le TypeScript livré.

## 7. Administration

Les routes d'administration sont protégées par le rôle administrateur via la
configuration de sécurité.

### Utilisateurs

Sous `/admin/user`, l'administrateur peut :

- consulter la liste paginée des utilisateurs ;
- créer un utilisateur avec nom d'utilisateur, e-mail et rôles ;
- modifier un utilisateur ;
- supprimer un utilisateur avec confirmation/jeton CSRF.

La liste affiche l'identifiant, le nom, l'e-mail, le statut de vérification,
les rôles et les actions disponibles.

### Logs

Sous `/admin/log`, l'administrateur peut :

- lister les dates pour lesquelles des logs existent ;
- ouvrir l'arborescence d'une date ;
- consulter le contenu d'un fichier de log.

### Prévisualisation des e-mails

Sous `/admin/emails`, l'administrateur peut lister les types d'e-mails
disponibles et afficher le rendu HTML d'un e-mail sous
`/admin/emails/preview/{type}`. Cette fonction sert à l'intégration et au
débogage des templates d'e-mails.

## 8. Interface et comportement frontend

- Twig produit le HTML initial et les formulaires Symfony.
- TypeScript applique une amélioration progressive : les champs natifs restent
  la source de vérité.
- `CustomSelector` fournit des sélecteurs enrichis avec recherche, couleurs et
  modes simple/multiple.
- `SelectableField` transforme les radios/checkboxes en contrôles visuels
  sélectionnables.
- Bootstrap, les icônes Remix/Material et les styles SCSS personnalisés
  constituent la base visuelle actuelle.
- Les pages dashboard partagent une barre supérieure, une barre latérale, les
  messages flash, le fil d'Ariane et le pied de page.
- Des pages d'erreur dédiées sont prévues pour les codes HTTP usuels, notamment
  401, 403, 404, 405, 418, 500, 501 et 503.

## 9. Notifications et e-mails

Les opérations utilisateur utilisent des messages flash de succès ou d'erreur.
Les e-mails fonctionnels actuellement prévus couvrent :

- confirmation de compte ;
- réinitialisation du mot de passe ;
- confirmation d'un changement d'adresse e-mail ;
- création d'un compte administrateur.

Les templates d'e-mails partagent un layout et des composants Twig dédiés
(texte, lien, bouton, boîte, carte, liste et séparateur).

## 10. Règles transverses observables

- Les formulaires sont validés côté serveur par les `FormType` Symfony.
- Les actions destructives utilisent POST et CSRF.
- Les tâches et étiquettes sont rattachées à l'utilisateur connecté lors de
  leur création.
- Les contrôleurs de tâches vérifient explicitement la propriété de la
  ressource pour les actions sensibles.
- Les services centralisent la persistance, les tris métier et la journalisation
  des opérations.
- Les réponses AJAX renvoient du JSON et, lorsque nécessaire, un fragment HTML
  rendu par Twig.

## 11. Limites fonctionnelles de cette version

- Aucun module de notes, fichiers, journal, capsule ou localisation n'est
  actuellement accessible dans l'interface.
- La préférence de thème est affichée mais sa sauvegarde n'est pas raccordée.
- L'ajout rapide AJAX existe côté backend mais n'est pas utilisé par le
  formulaire courant.
- La réorganisation par glisser-déposer et les sous-tâches ne sont pas
  implémentées.
- Les données personnelles ne disposent pas d'une corbeille ou d'un archivage
  fonctionnel dans le périmètre actuellement livré.
