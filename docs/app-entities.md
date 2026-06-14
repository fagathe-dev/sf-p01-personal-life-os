# Documentation des entités Symfony

## User
- **id** : int — Identifiant unique de l'utilisateur
- **email** : string — Adresse email
- **roles** : array — Rôles de l'utilisateur
- **password** : string — Mot de passe hashé
- **username** : string — Nom d'utilisateur
- **created_at** : DateTimeImmutable — Date de création
- **updated_at** : DateTimeImmutable|null — Date de dernière modification
- **avatar** : string|null — URL ou chemin de l'avatar
- **is_verified** : bool|null — Statut de vérification
- **verified_at** : DateTimeImmutable|null — Date de vérification
- **preferences** : array|null — Préférences utilisateur

**Relations :**
- 1:N `UserRequest` (demandes liées à l'utilisateur)
- 1:N `Tag` (tags créés)
- 1:N `Note` (notes créées)
- 1:N `Task` (tâches créées)
- 1:N `Folder` (dossiers)
- 1:N `Location` (lieux)
- 1:1 `Journal` (journal personnel)
- 1:N `DriveDocument` (documents)
- 1:N `Capsule` (capsules temporelles)

---

## Note (hérite de AbstractTextEntry)
- **title** : string|null — Titre de la note
- **state** : ContentStateEnum|string|null — État de la note
- **color** : NoteColorEnum|string|null — Couleur de la note
- **deleted_at** : DateTimeImmutable|null — Date de suppression

**Relations :**
- N:1 `User` (propriétaire)
- N:1 `Tag` (tag associé)

---

## Task
- **id** : int — Identifiant unique
- **title** : string — Titre de la tâche
- **description** : string|null — Description
- **position** : int|null — Position dans la liste
- **is_completed** : bool|null — Statut d'achèvement
- **completed_at** : DateTimeImmutable|null — Date d'achèvement
- **created_at** : DateTimeImmutable — Date de création
- **updated_at** : DateTimeImmutable|null — Date de modification
- **due_date** : DateTimeImmutable|null — Date d'échéance
- **priority** : TaskPriorityEnum|string|null — Priorité
- **state** : TaskStateEnum|string|null — État
- **is_pinned** : bool|null — Épinglée ou non

**Relations :**
- N:1 `User` (propriétaire)
- N:1 `Tag` (tag associé)

---

## Journal
- **id** : int — Identifiant unique
- **label** : string — Nom du journal
- **secret** : string — Clé secrète

**Relations :**
- 1:1 `User` (propriétaire)
- 1:N `JournalEntry` (entrées du journal)

---

## JournalEntry (hérite de AbstractTextEntry)
- **entryDate** : DateTimeImmutable — Date de l'entrée
- **mood** : MoodEnum|string|null — Humeur
- **title** : string|null — Titre

**Relations :**
- N:1 `Journal` (journal parent)
- N:1 `Location` (lieu)
- 1:N `JournalMedia` (médias associés)

---

## Tag
- **id** : int — Identifiant unique
- **name** : string — Nom du tag
- **description** : string|null — Description
- **color** : ColorEnum|string|null — Couleur
- **created_at** : DateTimeImmutable — Date de création
- **updated_at** : DateTimeImmutable|null — Date de modification

**Relations :**
- N:1 `User` (propriétaire)
- 1:N `Note` (notes associées)
- 1:N `Task` (tâches associées)

---

## Folder
- **id** : int — Identifiant unique
- **name** : string — Nom du dossier

**Relations :**
- N:1 `User` (propriétaire)
- N:1 `Folder` (dossier parent)
- 1:N `Folder` (sous-dossiers)
- 1:N `DriveDocument` (documents)

---

## DriveDocument (hérite de AbstractFile)
- **state** : ContentStateEnum|string|null — État du document

**Relations :**
- N:1 `User` (propriétaire)
- N:1 `Folder` (dossier parent)

---

## Capsule
- **id** : int — Identifiant unique
- **title** : string — Titre
- **unlockAt** : DateTimeImmutable — Date d'ouverture
- **isOpened** : bool — Statut d'ouverture
- **secret** : string|null — Clé secrète

**Relations :**
- N:1 `User` (propriétaire)
- 1:N `CapsuleMedia` (médias associés)

---

## CapsuleMedia (hérite de AbstractFile)
**Relations :**
- N:1 `Capsule` (capsule associée)

---

## JournalMedia (hérite de AbstractFile)
**Relations :**
- N:1 `JournalEntry` (entrée du journal associée)

---

## Location
- **id** : int — Identifiant unique
- **name** : string — Nom du lieu
- **latitude** : float — Latitude
- **longitude** : float — Longitude
- **street** : string|null — Rue
- **city** : string|null — Ville
- **country** : string|null — Pays

**Relations :**
- N:1 `User` (propriétaire)

---

## UserRequest
- **id** : int — Identifiant unique
- **type** : UserRequestTypeEnum|string|null — Type de requête
- **token** : string — Jeton de validation
- **created_at** : DateTimeImmutable — Date de création
- **updated_at** : DateTimeImmutable|null — Date de modification
- **expires_at** : DateTimeImmutable|null — Date d'expiration
- **used_at** : DateTimeImmutable|null — Date d'utilisation
- **is_used** : bool|null — Statut d'utilisation
- **content** : array|null — Contenu additionnel

**Relations :**
- N:1 `User` (utilisateur concerné)

---

## File
- **id** : int — Identifiant unique
- **deleted_at** : DateTimeImmutable|null — Date de suppression

---

## AbstractTextEntry (classe abstraite)
- **id** : int — Identifiant unique
- **content** : string|null — Contenu textuel
- **created_at** : DateTimeImmutable — Date de création
- **updated_at** : DateTimeImmutable|null — Date de modification
- **is_pinned** : bool|null — Épinglée ou non

---

## AbstractFile (classe abstraite)
- **id** : int — Identifiant unique
- **originalName** : string — Nom d'origine du fichier uploadé
- **niceName** : string|null — Nom lisible par l'utilisateur
- **filePath** : string — Chemin du fichier
- **mimeType** : string — Type MIME
- **type** : FileTypeEnum|string|null — Type de fichier
- **extension** : string — Extension
- **size** : int — Taille en octets
- **created_at** : DateTimeImmutable — Date de création
- **updated_at** : DateTimeImmutable|null — Date de modification
- **isPinned** : bool — Fichier épinglé ou non

---
