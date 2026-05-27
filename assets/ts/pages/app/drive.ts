import { ROUTES } from '@/constantes/routes';
import { fetchAPI, $, router } from 'core-ts';
import { FILE_ICON_MAP, DEFAULT_ICON } from 'core-ts'; // Ton utilitaire de mapping d'icônes

export class DriveUploader {
  private fileToUploadQueue: File[] = [];
  private isUploading: boolean = false;
  private uploadedCount: number = 0;

  // Éléments du DOM
  private toasterEl = $('#drive-upload-toaster', false) as HTMLElement;
  private listEl = $('#upload-toaster-list', false) as HTMLElement;
  private titleEl = $('#upload-toaster-title', false) as HTMLElement;
  private template = $('#upload-item-template', false) as HTMLTemplateElement;

  constructor() {
    this.initListeners();
  }

  private initListeners() {
    // Gère la réduction (collapse) du toaster
    ($('#upload-toaster-minimize', false) as HTMLElement).addEventListener(
      'click',
      () => {
        this.listEl.classList.toggle('d-none');
      },
    );

    // Gère la fermeture complète
    ($('#upload-toaster-close', false) as HTMLElement).addEventListener(
      'click',
      () => {
        this.toasterEl.classList.add('d-none');
        // Optionnel : vider la liste visuelle ici si tout est fini
      },
    );
  }

  /**
   * Point d'entrée : Ajoute des fichiers à la file d'attente
   */
  public addFiles(files: FileList | File[]) {
    const fileArray = Array.from(files);
    if (fileArray.length === 0) return;

    this.fileToUploadQueue.push(...fileArray);
    this.toasterEl.classList.remove('d-none'); // Affiche le toaster
    this.listEl.classList.remove('d-none'); // Assure qu'il n'est pas réduit

    // Prépare le rendu visuel pour chaque fichier
    fileArray.forEach((file) => this.createFileRow(file));
    this.updateTitle();

    // Lance le moteur si ce n'est pas déjà le cas
    if (!this.isUploading) {
      this.processQueue();
    }
  }

  /**
   * Traite la file d'attente fichier par fichier (Séquentiel)
   */
  private async processQueue() {
    if (this.fileToUploadQueue.length === 0) {
      this.isUploading = false;
      this.titleEl.textContent = `${this.uploadedCount} fichier(s) importé(s)`;
      this.uploadedCount = 0; // Reset
      return;
    }

    this.isUploading = true;

    // Extrait le premier fichier de la file d'attente
    const currentFile = this.fileToUploadQueue.shift();
    if (currentFile) {
      await this.uploadSingleFile(currentFile);
      this.uploadedCount++;
    }

    // Boucle récursive sur le prochain fichier
    this.processQueue();
  }

  /**
   * Upload effectif avec suivi de progression (via Axios / fetchAPI)
   */
  private async uploadSingleFile(file: File) {
    // Retrouve la ligne HTML correspondant au fichier
    const rowId = this.generateRowId(file);
    const row = document.getElementById(rowId);

    if (!row) return;

    const progressBar = row.querySelector('.progress-bar') as HTMLElement;
    const percentageText = row.querySelector('.file-percentage') as HTMLElement;
    const statusIcon = row.querySelector('.file-status-icon') as HTMLElement;

    // Affiche le spinner pendant l'upload
    statusIcon.classList.remove('d-none');
    percentageText.classList.add('d-none');

    const formData = new FormData();
    formData.append('file', file);
    // formData.append('folderId', currentFolderId); // Si tu gères les sous-dossiers

    try {
      const response = await fetchAPI(router(ROUTES.DRIVE.FILE.ADD), {
        method: 'POST',
        body: formData,
        onUploadProgress: (event: ProgressEvent) => {
          if (event.lengthComputable) {
            const percent = Math.round((event.loaded * 100) / event.total);
            progressBar.style.width = `${percent}%`;
            percentageText.textContent = `${percent}%`;

            // Si le serveur met du temps à répondre après le 100%
            if (percent === 100) {
              percentageText.classList.remove('d-none');
              statusIcon.classList.add('d-none');
            }
          }
        },
      });

      if (response.data && response.data.success) {
        // Succès : On passe la barre en vert et on met un check
        progressBar.classList.replace('bg-primary', 'bg-success');
        statusIcon.innerHTML =
          '<i class="ri-checkbox-circle-fill text-success fs-5"></i>';
        statusIcon.classList.remove('d-none');
        percentageText.classList.add('d-none');

        // Optionnel : Injecter le composant retourné par Symfony dans ton vrai tableau Drive
        // if (response.data.html) {
        //    $('#b-drive-files-list').insertAdjacentHTML('afterbegin', response.data.html);
        // }
      }
    } catch (error) {
      // Erreur : On passe la barre en rouge
      progressBar.classList.replace('bg-primary', 'bg-danger');
      statusIcon.innerHTML =
        '<i class="ri-error-warning-fill text-danger fs-5" title="Erreur"></i>';
      statusIcon.classList.remove('d-none');
      percentageText.classList.add('d-none');
    }
  }

  /**
   * Génère la ligne HTML dans le Toaster à partir du template
   */
  private createFileRow(file: File) {
    const clone = this.template.content.cloneNode(true) as DocumentFragment;
    const row = clone.querySelector('.upload-item') as HTMLElement;

    // Génère un ID unique basé sur le nom du fichier pour le retrouver facilement
    row.id = this.generateRowId(file);

    // Map l'icône en fonction de l'extension
    const ext = file.name.split('.').pop()?.toLowerCase() || '';
    const iconData = FILE_ICON_MAP[ext] || DEFAULT_ICON;

    const iconEl = clone.querySelector('.file-icon-wrapper i') as HTMLElement;
    iconEl.className = `${iconData.icon} text-${iconData.color} fs-2`;

    // Injecte le nom
    const nameEl = clone.querySelector('.file-name') as HTMLElement;
    nameEl.textContent = file.name;
    nameEl.title = file.name;

    // Ajoute au DOM en haut de la liste
    this.listEl.prepend(clone);
  }

  private updateTitle() {
    const total = this.fileToUploadQueue.length + this.uploadedCount;
    this.titleEl.textContent = `Importation de ${total} fichier(s)...`;
  }

  private generateRowId(file: File): string {
    // Enlève les caractères bizarres pour en faire un ID HTML valide
    return 'upload-row-' + file.name.replace(/[^a-zA-Z0-9]/g, '-');
  }
}
