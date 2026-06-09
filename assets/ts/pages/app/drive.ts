import { ROUTES } from '@/constantes';
import {
  fetchAPI,
  $,
  router,
  SelectableField,
  FILE_ICON_MAP,
  DEFAULT_ICON,
  FormManager,
} from 'core-ts'; // Ajout de SelectableField ici

type FileActionType = 'rename' | 'move' | 'trash' | 'archive' | 'pin';
type FolderActionType = 'rename' | 'move';

const urlAddFolder = router(ROUTES.DRIVE.FOLDER.ADD);
const urlFileUpload = router(ROUTES.DRIVE.FILE.ADD);

const folderItemsListContainer = $<HTMLElement>(
  '#b-drive-folders-list',
) as HTMLElement | null;
const fileItemsListContainer = $<HTMLElement>(
  '#b-drive-files-list',
) as HTMLElement | null;

class DriveUploader {
  private fileToUploadQueue: File[] = [];
  private isUploading: boolean = false;
  private uploadedCount: number = 0;

  private toasterEl = $<HTMLElement>('#drive-upload-toaster') as HTMLElement;
  private listEl = $<HTMLElement>('#upload-toaster-list') as HTMLElement;
  private titleEl = $<HTMLElement>('#upload-toaster-title') as HTMLElement;
  private template = $<HTMLTemplateElement>(
    '#upload-item-template',
  ) as HTMLTemplateElement;

  constructor() {
    this.initListeners();
  }

  private initListeners() {
    const minimizeBtn = $<HTMLElement>('#upload-toaster-minimize');
    if (minimizeBtn instanceof HTMLElement) {
      minimizeBtn.addEventListener('click', () => {
        this.listEl.classList.toggle('d-none');
      });
    }

    const closeBtn = $<HTMLElement>('#upload-toaster-close');
    if (closeBtn instanceof HTMLElement) {
      closeBtn.addEventListener('click', () => {
        this.toasterEl.classList.add('d-none');
      });
    }
  }

  public addFiles(files: FileList | File[]) {
    const fileArray = Array.from(files);
    if (fileArray.length === 0) return;

    this.fileToUploadQueue.push(...fileArray);
    this.toasterEl.classList.remove('d-none');
    this.listEl.classList.remove('d-none');

    fileArray.forEach((file) => this.createFileRow(file));
    this.updateTitle();

    if (!this.isUploading) {
      this.processQueue();
    }
  }

  private async processQueue() {
    if (this.fileToUploadQueue.length === 0) {
      this.isUploading = false;
      this.titleEl.textContent = `${this.uploadedCount} fichier(s) importé(s)`;
      this.uploadedCount = 0;
      return;
    }

    this.isUploading = true;

    const currentFile = this.fileToUploadQueue.shift();
    if (currentFile) {
      await this.uploadSingleFile(currentFile);
      this.uploadedCount++;
    }

    this.processQueue();
  }

  private async uploadSingleFile(file: File) {
    const rowId = this.generateRowId(file);
    const row = document.getElementById(rowId);

    if (!row) return;

    const progressBar = row.querySelector('.progress-bar') as HTMLElement;
    const percentageText = row.querySelector('.file-percentage') as HTMLElement;
    const statusIcon = row.querySelector('.file-status-icon') as HTMLElement;

    statusIcon.classList.remove('d-none');
    percentageText.classList.add('d-none');

    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await fetchAPI(router(ROUTES.DRIVE.FILE.ADD), {
        method: 'POST',
        body: formData,
        onUploadProgress: (event: ProgressEvent) => {
          if (event.lengthComputable) {
            const percent = Math.round((event.loaded * 100) / event.total);
            progressBar.style.width = `${percent}%`;
            percentageText.textContent = `${percent}%`;

            if (percent === 100) {
              percentageText.classList.remove('d-none');
              statusIcon.classList.add('d-none');
            }
          }
        },
      });

      if (response.data && response.data.success) {
        progressBar.classList.replace('bg-primary', 'bg-success');
        statusIcon.innerHTML =
          '<i class="ri-checkbox-circle-fill text-success fs-5"></i>';
        statusIcon.classList.remove('d-none');
        percentageText.classList.add('d-none');
      }
    } catch (error) {
      progressBar.classList.replace('bg-primary', 'bg-danger');
      statusIcon.innerHTML =
        '<i class="ri-error-warning-fill text-danger fs-5" title="Erreur"></i>';
      statusIcon.classList.remove('d-none');
      percentageText.classList.add('d-none');
    }
  }

  private createFileRow(file: File) {
    const clone = this.template.content.cloneNode(true) as DocumentFragment;
    const row = clone.querySelector('.upload-item') as HTMLElement;

    row.id = this.generateRowId(file);

    const ext = file.name.split('.').pop()?.toLowerCase() || '';
    const iconData = FILE_ICON_MAP[ext] || DEFAULT_ICON;

    const iconEl = clone.querySelector('.file-icon-wrapper i') as HTMLElement;
    iconEl.className = `${iconData.icon} text-${iconData.color} fs-2`;

    const nameEl = clone.querySelector('.file-name') as HTMLElement;
    nameEl.textContent = file.name;
    nameEl.title = file.name;

    this.listEl.prepend(clone);
  }

  private updateTitle() {
    const total = this.fileToUploadQueue.length + this.uploadedCount;
    this.titleEl.textContent = `Importation de ${total} fichier(s)...`;
  }

  private generateRowId(file: File): string {
    return 'upload-row-' + file.name.replace(/[^a-zA-Z0-9]/g, '-');
  }
}

/**
 * Initialise la modale de création / modification de dossier
 */
const initFolderModal = () => {
  const modalEl = $<HTMLElement>(
    '#modalFolderForm',
    false,
  ) as HTMLElement | null;
  const formEl = $<HTMLFormElement>(
    '#folderModalForm',
    false,
  ) as HTMLFormElement | null;
  const titleEl = $<HTMLElement>(
    '#modalFolderFormLabel',
    false,
  ) as HTMLElement | null;

  if (!modalEl || !formEl || !titleEl) return;

  // Initialisation de ton FormManager
  const formManager = new FormManager({ form: formEl });

  // 1. Gérer l'ouverture (Hydratation)
  modalEl.addEventListener('show.bs.modal', (event: any) => {
    const triggerBtn = event.relatedTarget as HTMLElement;
    if (!triggerBtn) return;

    const actionType = triggerBtn.getAttribute('data-drive-action');

    if (actionType === 'rename-folder') {
      const folderId = triggerBtn.getAttribute('data-item-id');
      const folderName = triggerBtn.getAttribute('data-item-name') || '';

      titleEl.textContent = 'Renommer le dossier';

      // 👈 Utilisation de fillData() au lieu de cibler l'input manuellement
      formManager.fillData({ name: folderName.trim() });

      const action: FolderActionType = 'rename';
      formEl.action = router(ROUTES.DRIVE.FOLDER.ACTION, {
        id: folderId!,
        action,
      });

      formEl.setAttribute('data-folder-id', folderId!);
      formEl.setAttribute('data-form-action', 'edit');
    } else {
      titleEl.textContent = 'Créer un dossier';
      formEl.action = urlAddFolder;
      formEl.setAttribute('data-form-action', 'add');
    }
  });

  // 2. Gérer la fermeture (Nettoyage complet via le Manager)
  modalEl.addEventListener('hidden.bs.modal', () => {
    formManager.reset(); // 👈 Supprime les valeurs ET les classes is-invalid/is-valid
  });

  // 3. Soumission AJAX
  formEl.addEventListener('submit', async (e) => {
    e.preventDefault();

    // 👈 Extraction propre des données au format Objet
    const data = formManager.getData();
    const url = formEl.getAttribute('action') as string;

    console.log(url);

    try {
      const response = await fetchAPI(url as string, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
      });

      if (response.data && response.data.success) {
        const action = formEl.getAttribute('data-form-action');

        if (action === 'edit') {
          // Mise à jour du nom dans le DOM
          const folderId = formEl.getAttribute('data-folder-id');
          const row = document.querySelector(
            `tr[data-drive-item="folder"][data-item-id="${folderId}"]`,
          );

          if (row) {
            const nameSpan = row.querySelector('.item-name');
            // On récupère le nouveau nom directement depuis les données du FormManager
            if (nameSpan) nameSpan.textContent = String(data.name).trim();

            const btn = row.querySelector(
              '[data-drive-action="rename-folder"]',
            );
            if (btn)
              btn.setAttribute('data-item-name', String(data.name).trim());
          }
        } else {
          // MODE CRÉATION : Injection du HTML
          const listContainer = document.getElementById('b-drive-folders-list');
          const emptyRow = document.getElementById('empty-folder-row');

          if (listContainer && response.data.html) {
            if (emptyRow) emptyRow.remove();
            listContainer.insertAdjacentHTML('beforeend', response.data.html);
          }
        }

        // Fermeture de la modale en cas de succès
        const closeBtn = modalEl.querySelector('.btn-close') as HTMLElement;
        if (closeBtn) closeBtn.click();
      } else if (response.data && response.data.violations) {
        // 👈 La magie opère : On passe directement les violations de Symfony au FormManager !
        formManager.validateData(response.data.violations);
      }
    } catch (error) {
      console.error('Erreur formulaire dossier :', error);
    }
  });
};

/**
 * Gère toutes les actions rapides (Fichiers & Dossiers) via délégation d'événements
 */
const initDriveQuickActions = () => {
  document.addEventListener('click', async (e: MouseEvent) => {
    const target = e.target as HTMLElement;

    // --------------------------------------------------------
    // 1. ACTIONS SUR LES FICHIERS
    // --------------------------------------------------------
    const fileActionEl = target.closest(
      '[data-drive-action$="-file"]',
    ) as HTMLElement | null;
    if (fileActionEl) {
      e.preventDefault();

      const actionType = fileActionEl.getAttribute('data-drive-action');
      const fileId = fileActionEl.getAttribute('data-item-id');
      const row = fileActionEl.closest('tr[data-drive-item="file"]');
      if (!fileId || !row) return;

      // --- RENOMMER UN FICHIER ---
      if (actionType === 'rename-file') {
        const currentName = fileActionEl.getAttribute('data-item-name') || '';
        // Utilisation d'un prompt natif pour la simplicité (peut être remplacé par une modale plus tard)
        const newName = prompt('Nouveau nom du fichier :', currentName);

        if (newName && newName.trim() !== '' && newName !== currentName) {
          try {
            const formData = new FormData();
            formData.append('name', newName.trim());

            const response = await fetchAPI(
              router(ROUTES.DRIVE.FILE.ACTION, {
                id: fileId,
                action: 'rename',
              }),
              {
                method: 'POST',
                body: formData,
              },
            );

            if (response.data && response.data.success) {
              const nameSpan = row.querySelector('.item-name');
              if (nameSpan) nameSpan.textContent = newName.trim();
              fileActionEl.setAttribute('data-item-name', newName.trim());
            }
          } catch (error) {
            console.error('Erreur rename :', error);
          }
        }
        return;
      }

      // --- PIN, ARCHIVE, TRASH ---
      const actionMap: Record<string, FileActionType> = {
        'pin-file': 'pin',
        'archive-file': 'archive',
        'trash-file': 'trash',
      };
      const action = actionMap[actionType as string];

      if (action) {
        // Confirmation optionnelle pour la suppression
        if (
          action === 'trash' &&
          !confirm('Placer ce fichier dans la corbeille ?')
        )
          return;

        try {
          const response = await fetchAPI(
            router(ROUTES.DRIVE.FILE.ACTION, { id: fileId, action }),
            {
              method: 'POST',
            },
          );

          if (response.data && response.data.success) {
            if (action === 'pin') {
              // Toggle visuel de l'étoile
              const icon = fileActionEl.querySelector('i');
              const isPinned = response.data.is_pinned; // Ton backend doit retourner cet état boolean
              if (icon) {
                icon.className = isPinned
                  ? 'ri-star-fill text-warning fs-13 align-bottom'
                  : 'ri-star-line text-muted fs-13 align-bottom';
              }
            } else if (action === 'archive' || action === 'trash') {
              // Disparition de la ligne avec petite animation
              if (row instanceof HTMLElement) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
              }
            }
          }
        } catch (error) {
          console.error(`Erreur ${action} :`, error);
        }
      }
    }

    // --------------------------------------------------------
    // 2. ACTIONS SUR LES DOSSIERS (hors modale)
    // --------------------------------------------------------
    const folderActionEl = target.closest(
      '[data-drive-action="trash-folder"]',
    ) as HTMLElement | null;
    if (folderActionEl) {
      e.preventDefault();

      const folderId = folderActionEl.getAttribute('data-item-id');
      const row = folderActionEl.closest(
        'tr[data-drive-item="folder"]',
      ) as HTMLElement | null;
      if (!folderId || !row) return;

      if (
        confirm(
          `Cette action est irréversible et entraînera la suppression définitive de tout le contenu du dossier.
          Voulez-vous vraiment continuer ?`,
        )
      ) {
        try {
          // La route DELETE est distincte pour les dossiers selon ton routes.ts
          const response = await fetchAPI(
            router(ROUTES.DRIVE.FOLDER.DELETE, { id: folderId }),
            {
              method: 'DELETE', // Ou POST selon la config de ton controller Symfony
            },
          );

          if (response.data && response.data.success) {
            if (row instanceof HTMLElement) {
              row.style.transition = 'opacity 0.3s ease';
              row.style.opacity = '0';
              setTimeout(() => row.remove(), 300);
            }
          }
        } catch (error) {
          console.error('Erreur trash folder :', error);
        }
      }
    }

    // Pour l'action "move", on interceptera ici '[data-drive-action="move-file"]' plus tard.
  });
};

/**
 * Initialise la gestion globale des tags du Drive
 */
const initDriveTags = () => {
  const modalTagEl = $<HTMLElement>('#modalTagForm', false);
  const tagForm = $<HTMLFormElement>('#driveFileTagForm', false);
  const selectorContainer = $<HTMLElement>(
    '.js-tag-selectable-container',
    false,
    modalTagEl as HTMLElement,
  );

  if (
    !(modalTagEl instanceof HTMLElement) ||
    !(tagForm instanceof HTMLFormElement) ||
    !(selectorContainer instanceof HTMLElement)
  )
    return;

  // 1. Instanciation du SelectableField
  new SelectableField(selectorContainer, { mode: 'nullable' as any });

  // 2. Hydratation de l'état interne à l'ouverture de la modale
  modalTagEl.addEventListener('show.bs.modal', (event: any) => {
    const triggerBtn = event.relatedTarget as HTMLElement;
    if (!triggerBtn) return;

    const fileId = triggerBtn.getAttribute('data-tag-modal-form-id');
    const currentTagId = triggerBtn.getAttribute('data-tag-modal-form-value');

    // Définition de l'URL d'action cible dynamique
    tagForm.action = `/ajax/drive/file/${fileId}/tag`;

    // Coche la bonne pastille en fonction du tag actuel (et décoche les autres)
    const radios = $(
      'input[name="tag_id"]',
      true,
      tagForm,
    ) as NodeListOf<HTMLInputElement>;

    if (radios) {
      radios.forEach((radio) => {
        // Si currentTagId est vide (''), radio.checked passera à false
        radio.checked = currentTagId ? radio.value === currentTagId : false;

        // ⚠️ Modification ici : On déclenche l'événement inconditionnellement
        // Cela force SelectableField à retirer la surbrillance des anciens tags
        radio.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }
  });

  // 2.bis Reset complet à la fermeture de la modale (pour éviter l'effet mémoire)
  modalTagEl.addEventListener('hidden.bs.modal', () => {
    const radios = $(
      'input[name="tag_id"]',
      true,
      tagForm,
    ) as NodeListOf<HTMLInputElement>;

    if (radios) {
      radios.forEach((radio) => {
        radio.checked = false;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }
  });

  // 3. Soumission AJAX instantanée dès qu'une pastille est cliquée ou décochée
  tagForm.addEventListener('change', async (e) => {
    e.preventDefault();

    // Protection pour éviter une soumission fantôme pendant l'hydratation initiale ou le reset
    if (!modalTagEl.classList.contains('show')) return;

    try {
      const response = await fetchAPI(tagForm.action, {
        method: 'POST',
        body: new FormData(tagForm),
      });

      if (response.data && response.data.success) {
        window.location.reload();
      }
    } catch (error) {
      console.error("Erreur lors de la modification de l'étiquette :", error);
    }
  });
};

initDriveTags();
initFolderModal();
initDriveQuickActions();
