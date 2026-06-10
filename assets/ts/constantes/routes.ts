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
} as const;

export { ROUTES };
