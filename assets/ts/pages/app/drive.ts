import { ROUTES } from '@/constantes';
import {
  fetchAPI,
  $,
  router,
  SelectableField,
  FormManager,
  FILE_ICON_MAP,
  DEFAULT_ICON,
} from 'core-ts';

// Déclaration stricte des types attendus
type FileActionType =
  | 'rename'
  | 'move'
  | 'trash'
  | 'archive'
  | 'pin'
  | 'tag'
  | 'download';
type FolderActionType = 'rename' | 'move';

// Centralisation des constantes d'URL demandées
const urlAddFolder = router(ROUTES.DRIVE.FOLDER.ADD);
const urlFileUpload = router(ROUTES.DRIVE.FILE.UPLOAD);

/**
 * Type Guard pour valider les actions de fichiers
 */
const isFileAction = (action: string | null): action is FileActionType => {
  return [
    'rename',
    'move',
    'trash',
    'archive',
    'pin',
    'tag',
    'download',
  ].includes(action || '');
};

/**
 * Type Guard pour valider les actions de dossiers
 */
const isFolderAction = (action: string | null): action is FolderActionType => {
  return ['rename', 'move'].includes(action || '');
};

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

    const driveRoot = $('#b-drive-root', false);
    const currentFolderId =
      driveRoot instanceof HTMLElement
        ? driveRoot.getAttribute('data-current-folder-id')
        : '';

    const formData = new FormData();
    formData.append('file', file);

    if (currentFolderId) {
      formData.append('folder_id', currentFolderId);
    }

    try {
      const response = await fetchAPI(urlFileUpload, {
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

        const fileListContainer = document.getElementById('b-drive-files-list');
        const emptyFileRow = document.getElementById('empty-file-row');

        if (fileListContainer && response.data.html) {
          if (emptyFileRow) emptyFileRow.remove();
          fileListContainer.insertAdjacentHTML('beforeend', response.data.html);
        }
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
 * Écoute le changement sur l'input d'importation de fichier
 */
const initDriveUpload = () => {
  const fileInput = $<HTMLInputElement>('#fileAddInput', false);
  if (!(fileInput instanceof HTMLInputElement)) return;

  const uploader = new DriveUploader();
  fileInput.addEventListener('change', () => {
    if (fileInput.files) {
      uploader.addFiles(fileInput.files);
    }
  });
};

/**
 * Initialise la modale de création / modification de dossier
 */
const initFolderModal = () => {
  const modalEl = $<HTMLElement>('#modalFolderForm', false);
  const formEl = $<HTMLFormElement>('#folderModalForm', false);
  const titleEl = $<HTMLElement>('#modalFolderFormLabel', false);

  if (
    !(modalEl instanceof HTMLElement) ||
    !(formEl instanceof HTMLFormElement) ||
    !(titleEl instanceof HTMLElement)
  )
    return;

  const formManager = new FormManager({ form: formEl });

  modalEl.addEventListener('show.bs.modal', (event: any) => {
    const triggerBtn = event.relatedTarget as HTMLElement;
    if (!triggerBtn) return;

    const actionType = triggerBtn.getAttribute('data-drive-action');

    if (actionType === 'rename-folder') {
      const folderId = triggerBtn.getAttribute('data-item-id');
      const folderName = triggerBtn.getAttribute('data-item-name') || '';

      titleEl.textContent = 'Renommer le dossier';
      formManager.fillData({ name: folderName.trim() });

      const action = 'rename';
      if (isFolderAction(action)) {
        formEl.action = router(ROUTES.DRIVE.FOLDER.ACTION, {
          id: folderId!,
          action,
        });
      }

      formEl.setAttribute('data-folder-id', folderId!);
      formEl.setAttribute('data-form-action', 'edit');
    } else {
      titleEl.textContent = 'Créer un dossier';
      formEl.action = urlAddFolder;
      formEl.setAttribute('data-form-action', 'add');

      const driveRoot = $('#b-drive-root', false);
      const currentFolderId =
        driveRoot instanceof HTMLElement
          ? driveRoot.getAttribute('data-current-folder-id')
          : '';
      formManager.fillData({ parent_id: currentFolderId });
    }
  });

  modalEl.addEventListener('hidden.bs.modal', () => {
    formManager.reset();
  });

  formEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = formManager.getData();

    try {
      const response = await fetchAPI(formEl.action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });

      if (response.data && response.data.success) {
        const action = formEl.getAttribute('data-form-action');

        if (action === 'edit') {
          const folderId = formEl.getAttribute('data-folder-id');
          const row = document.querySelector(
            `tr[data-drive-item="folder"][data-item-id="${folderId}"]`,
          );
          if (row) {
            const nameSpan = row.querySelector('.item-name');
            if (nameSpan) nameSpan.textContent = String(data.name).trim();
            const btn = row.querySelector(
              '[data-drive-action="rename-folder"]',
            );
            if (btn)
              btn.setAttribute('data-item-name', String(data.name).trim());
          }
        } else {
          const listContainer = document.getElementById('b-drive-folders-list');
          const emptyRow = document.getElementById('empty-folder-row');

          if (listContainer && response.data.html) {
            if (emptyRow) emptyRow.remove();
            listContainer.insertAdjacentHTML('beforeend', response.data.html);
          }
        }
        const closeBtn = modalEl.querySelector('.btn-close') as HTMLElement;
        if (closeBtn) closeBtn.click();
      } else if (response.data && response.data.violations) {
        formManager.validateData(response.data.violations);
      }
    } catch (error) {
      console.error('Erreur formulaire dossier :', error);
    }
  });
};

/**
 * Gère l'attribution et la synchronisation de la modale des pastilles d'étiquettes
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

  new SelectableField(selectorContainer, { mode: 'nullable' as any });

  let currentTriggerBtn: HTMLElement | null = null;

  modalTagEl.addEventListener('show.bs.modal', (event: any) => {
    currentTriggerBtn = event.relatedTarget as HTMLElement;
    if (!currentTriggerBtn) return;

    const fileId = currentTriggerBtn.getAttribute('data-tag-modal-form-id');
    const currentTagId = currentTriggerBtn.getAttribute(
      'data-tag-modal-form-value',
    );

    const action = 'tag';
    if (isFileAction(action)) {
      tagForm.action = router(ROUTES.DRIVE.FILE.ACTION, {
        id: fileId!,
        action,
      });
    }

    const radios = $(
      'input[name="tag_id"]',
      true,
      tagForm,
    ) as NodeListOf<HTMLInputElement>;
    if (radios) {
      radios.forEach((radio) => {
        radio.checked = currentTagId ? radio.value === currentTagId : false;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }
  });

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
    currentTriggerBtn = null;
  });

  tagForm.addEventListener('change', async (e) => {
    e.preventDefault();
    if (!modalTagEl.classList.contains('show')) return;

    try {
      const response = await fetchAPI(tagForm.action, {
        method: 'POST',
        body: new FormData(tagForm),
      });

      if (response.data && response.data.success) {
        if (currentTriggerBtn) {
          const tag = response.data.tag;

          if (tag) {
            currentTriggerBtn.setAttribute('data-tag-modal-form-value', tag.id);
            currentTriggerBtn.setAttribute('title', tag.name);
            currentTriggerBtn.innerHTML = `<span class="rounded-circle shadow-sm bg-${tag.color}" style="width: 12px; height: 12px;"></span>`;
          } else {
            currentTriggerBtn.setAttribute('data-tag-modal-form-value', '');
            currentTriggerBtn.setAttribute('title', 'Ajouter une étiquette');
            currentTriggerBtn.innerHTML = `<span class="rounded-circle border border-secondary border-dashed opacity-50 hover-opacity-100" style="width: 12px; height: 12px;"></span>`;
          }
        }

        const closeBtn = modalTagEl.querySelector('.btn-close') as HTMLElement;
        if (closeBtn) closeBtn.click();
      }
    } catch (error) {
      console.error("Erreur lors de la modification de l'étiquette :", error);
    }
  });
};

/**
 * Gère toutes les actions rapides des fichiers et dossiers via la délégation d'événements
 */
const initDriveQuickActions = () => {
  document.addEventListener('click', async (e: MouseEvent) => {
    const target = e.target as HTMLElement;

    // 1. GESTION ACTIONS RAPIDES FICHIERS
    const fileActionEl = target.closest(
      '[data-drive-action$="-file"]',
    ) as HTMLElement | null;
    if (fileActionEl) {
      e.preventDefault();

      const rawAction = fileActionEl
        .getAttribute('data-drive-action')
        ?.replace('-file', '');
      const fileId = fileActionEl.getAttribute('data-item-id');
      const row = fileActionEl.closest(
        'tr[data-drive-item="file"]',
      ) as HTMLElement | null;

      if (!fileId || !row || !rawAction) return;

      if (!isFileAction(rawAction)) {
        console.error(`Action de fichier non autorisée : ${rawAction}`);
        return;
      }

      if (rawAction === 'rename') {
        const currentName = fileActionEl.getAttribute('data-item-name') || '';
        const newName = prompt('Nouveau nom du fichier :', currentName);

        if (newName && newName.trim() !== '' && newName !== currentName) {
          try {
            const formData = new FormData();
            formData.append('name', newName.trim());

            const response = await fetchAPI(
              router(ROUTES.DRIVE.FILE.ACTION, {
                id: fileId,
                action: rawAction,
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
          } catch (err) {
            console.error(err);
          }
        }
        return;
      }

      if (['pin', 'archive', 'trash', 'download'].includes(rawAction)) {
        if (
          rawAction === 'trash' &&
          !confirm('Placer ce fichier dans la corbeille ?')
        )
          return;

        try {
          const response = await fetchAPI(
            router(ROUTES.DRIVE.FILE.ACTION, { id: fileId, action: rawAction }),
            {
              method: 'POST',
            },
          );

          if (response.data && response.data.success) {
            if (rawAction === 'download') {
              const link = document.createElement('a');
              link.href = response.data.url;

              const nameSpan = row.querySelector('.item-name');
              link.download = nameSpan
                ? nameSpan.textContent?.trim() || ''
                : '';

              document.body.appendChild(link);
              link.click();
              link.remove();
            } else if (rawAction === 'pin') {
              const icon = fileActionEl.querySelector('i');
              const isPinned = response.data.is_pinned;
              if (icon) {
                icon.className = isPinned
                  ? 'ri-star-fill text-warning fs-13 align-bottom'
                  : 'ri-star-line text-muted fs-13 align-bottom';
              }
            } else {
              row.style.transition = 'opacity 0.3s ease';
              row.style.opacity = '0';
              setTimeout(() => row.remove(), 300);
            }
          }
        } catch (err) {
          console.error(err);
        }
      }
    }

    // 2. GESTION SUPPRESSION DIRECTE DOSSIER
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
          "Attention action irréversible : Supprimer ce dossier enverra l'ensemble de ses fichiers à plat dans la corbeille. Confirmer ?",
        )
      ) {
        try {
          const response = await fetchAPI(
            router(ROUTES.DRIVE.FOLDER.DELETE, { id: folderId }),
            {
              method: 'DELETE',
            },
          );

          if (response.data && response.data.success) {
            row.style.transition = 'opacity 0.3s ease';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
          }
        } catch (err) {
          console.error(err);
        }
      }
    }
  });
};

// Lancement global de la réactivité locale (Aucun export)
initDriveTags();
initFolderModal();
initDriveQuickActions();
initDriveUpload();
