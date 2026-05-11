import { ROUTES } from '@/constantes/routes';
import { FileUploader, SelectableField, $ } from 'core-ts';
import { CustomSelector } from '@/features';

document.addEventListener('DOMContentLoaded', (): void => {
  const inputElement = $<HTMLInputElement>(
    '#profile-img-file-input',
  ) as HTMLInputElement | null;

  if (!inputElement) {
    return;
  }

  new FileUploader({
    uploadUrl: ROUTES.AUTH.PROFILE.UPLOAD_AVATAR,
    inputElement,
    fieldName: 'avatar',
    maxSize: '15M',
    allowedMimes: [
      'image/bmp',
      'image/webp',
      'image/svg+xml',
      'image/tiff',
      'image/png',
      'image/gif',
      'image/x-icon',
      'image/jpeg',
    ],
    onPreview(base64Url: string): void {
      // ----------------------------------------------------
      // 1. MISE À JOUR DE L'AVATAR SUR LA PAGE PROFIL
      // ----------------------------------------------------
      const profileUser = $<HTMLElement>('.profile-user') as HTMLElement | null;
      if (profileUser) {
        const existingImg = $<HTMLImageElement>(
          '.user-profile-image',
          false,
          profileUser,
        ) as HTMLImageElement | null;

        if (existingImg) {
          existingImg.src = base64Url;
        } else {
          // Cas du fallback (initiales) : créer l'image et supprimer le div fallback
          const img = document.createElement('img');
          img.src = base64Url;
          img.alt = 'user-profile-image';
          img.classList.add(
            'rounded-circle',
            'avatar-xl',
            'img-thumbnail',
            'user-profile-image',
          );

          const fileInputWrapper = inputElement.closest('.profile-photo-edit');
          if (fileInputWrapper) {
            profileUser.insertBefore(img, fileInputWrapper);
          } else {
            profileUser.prepend(img);
          }

          const fallback = $<HTMLElement>(
            '.avatar-xl.shadow.rounded-circle',
            false,
            profileUser,
          ) as HTMLElement | null;
          if (fallback) {
            fallback.remove();
          }
        }
      }

      // ----------------------------------------------------
      // 2. MISE À JOUR DE L'AVATAR DANS LA NAVBAR (TOPBAR)
      // ----------------------------------------------------
      const navbarAvatarContainer = $<HTMLElement>(
        '#navbar-user-avatar',
      ) as HTMLElement | null;
      if (navbarAvatarContainer) {
        // On cherche l'image existante avec sa classe spécifique à la navbar
        const navbarImg = $<HTMLImageElement>(
          '.header-profile-user',
          false,
          navbarAvatarContainer,
        ) as HTMLImageElement | null;

        if (navbarImg) {
          // Si l'image existe déjà, on remplace juste la source
          navbarImg.src = base64Url;
        } else {
          // Cas du fallback (initiales dans la navbar) : créer l'image
          const img = document.createElement('img');
          img.src = base64Url;
          img.alt = 'Header Avatar';
          img.classList.add('rounded-circle', 'header-profile-user');

          // On l'ajoute au tout début du conteneur (avant le span contenant le nom)
          navbarAvatarContainer.prepend(img);

          // On cherche et on supprime le bloc contenant les initiales
          const fallback = $<HTMLElement>(
            '.avatar-xs',
            false,
            navbarAvatarContainer,
          ) as HTMLElement | null;
          if (fallback) {
            fallback.remove();
          }
        }
      }
    },
    onError(message: string): void {
      alert(message);
    },
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const container = $<HTMLElement>(
    '.js-selectable-container',
  ) as HTMLElement | null;
  if (container) {
    new SelectableField(container, { mode: 'radio' });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  // Initialise tous les sélecteurs custom de la page
  const dropdownSelectContainers = $<HTMLElement>(
    '.js-dropdown-select',
    true,
  ) as NodeListOf<HTMLElement> | null;

  if (dropdownSelectContainers) {
    dropdownSelectContainers.forEach((container) => {
      // Astuce : tu peux même lire le mode désiré via un attribut de données (data-mode="single" par exemple) pour rendre le composant plus générique et réutilisable dans d'autres contextes que le profil !
      // pour ne pas figer le composant en multiple !
      const mode =
        (container.getAttribute('data-dcs-mode') as any) || 'multiple-nullable';

      new CustomSelector(container, {
        mode: mode,
        placeholder:
          container.getAttribute('data-dcs-placeholder') || 'Sélectionner...',
      });
    });
  }
});
