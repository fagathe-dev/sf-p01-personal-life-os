const ROUTES = {
  AUTH: {
    PROFILE: {
      UPLOAD_AVATAR: '/auth/profile/upload-avatar',
    },
  },
  TODO: {
    TOGGLE_COMPLETED: '/ajax/todo/{id}/toggle-completed',
    TOGGLE_PINNED: '/ajax/todo/{id}/toggle-pinned',
  },
  NOTE: {
    QUICK_ACTIONS: '/ajax/note/{id}/{action}',
  },
  DRIVE: {
    FILE: {
      UPLOAD: '/ajax/drive/file/upload',
      ACTION: '/ajax/drive/file/edit/{id}/{action}',
    },
    FOLDER: {
      ADD: '/ajax/drive/folder/add',
      ACTION: '/ajax/drive/folder/{id}/{action}',
      DELETE: '/ajax/drive/folder/{id}/delete',
    },
  },
  ARCHIVE_TRASH: {
    FILE_RESTORE: '/ajax/archive-trash/file/{id}/restore',
    NOTE_RESTORE: '/ajax/archive-trash/note/{id}/restore',
    FILE_PURGE: '/ajax/archive-trash/file/{id}/purge',
    NOTE_PURGE: '/ajax/archive-trash/note/{id}/purge',
    RESTORE_ALL: '/ajax/archive-trash/{context}/restore-all',
    EMPTY_TRASH: '/ajax/archive-trash/empty-trash',
  },
} as const;

export { ROUTES };
