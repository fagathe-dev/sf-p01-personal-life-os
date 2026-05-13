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
} as const;

export { ROUTES };
