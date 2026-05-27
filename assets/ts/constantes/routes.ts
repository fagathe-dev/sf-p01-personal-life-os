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
      ADD: '/ajax/drive/file/add',
      DELETE: '/ajax/drive/file/{id}/delete',
      RENAME: '/ajax/drive/file/{id}/rename',
      MOVE: '/ajax/drive/file/{id}/move',
    },
    FOLDER: {
      ADD: '/ajax/drive/folder/add',
      DELETE: '/ajax/drive/folder/{id}/delete',
      RENAME: '/ajax/drive/folder/{id}/rename',
    },
  },
} as const;

export { ROUTES };
