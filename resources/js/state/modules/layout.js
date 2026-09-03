const state = {
  layoutType: 'vertical',
  layoutWidth: 'fluid',
  sidebarSize: 'lg',
  topbar: 'light',
  mode: 'light',
  position: 'fixed',
  sidebarView: 'default',
  sidebarColor: 'dark',
  sidebarImage: 'none',
  bodybg: 'img-1',
  preloader: 'disable',
  visibility: 'show'
};

export const applyLayoutConfig = (layoutState = state) => {
  if (typeof document === 'undefined') {
    return;
  }

  const html = document.documentElement;

  html.setAttribute('data-layout', layoutState.layoutType || 'vertical');
  html.setAttribute('data-layout-width', layoutState.layoutWidth || 'fluid');
  html.setAttribute('data-sidebar-size', layoutState.sidebarSize || 'lg');
  html.setAttribute('data-topbar', layoutState.topbar || 'light');

  // Important pour désactiver le dark mode
  html.setAttribute('data-bs-theme', layoutState.mode || 'light');
  html.setAttribute('data-layout-mode', layoutState.mode || 'light');

  html.setAttribute('data-layout-position', layoutState.position || 'fixed');
  html.setAttribute('data-layout-style', layoutState.sidebarView || 'default');
  html.setAttribute('data-sidebar', layoutState.sidebarColor || 'dark');
  html.setAttribute('data-sidebar-image', layoutState.sidebarImage || 'none');
  html.setAttribute('data-preloader', layoutState.preloader || 'disable');
  html.setAttribute('data-layout-visibility', layoutState.visibility || 'show');

  if (layoutState.bodybg) {
    html.setAttribute('data-body-image', layoutState.bodybg);
  }

  html.classList.remove('dark');
  document.body.classList.remove('dark');
};

const mutations = {
  CHANGE_LAYOUT(state, layoutType) {
    state.layoutType = layoutType;
  },

  CHANGE_LAYOUT_WIDTH(state, layoutWidth) {
    state.layoutWidth = layoutWidth;
  },

  CHANGE_SIDEBAR_TYPE(state, sidebarSize) {
    state.sidebarSize = sidebarSize;
  },

  CHANGE_TOPBAR(state, topbar) {
    state.topbar = topbar;
  },

  CHANGE_MODE(state, mode) {
    state.mode = mode;
  },

  CHANGE_POSITION(state, position) {
    state.position = position;
  },

  CHANGE_SIDEBAR_VIEW(state, sidebarView) {
    state.sidebarView = sidebarView;
  },

  CHANGE_SIDEBAR_COLOR(state, sidebarColor) {
    state.sidebarColor = sidebarColor;
  },

  CHANGE_SIDEBAR_IMAGE(state, sidebarImage) {
    state.sidebarImage = sidebarImage;
  },

  CHANGE_PRELOADER(state, preloader) {
    state.preloader = preloader;
  },

  CHANGE_VISIBILITY(state, visibility) {
    state.visibility = visibility;
  }
};

const actions = {
  changeLayoutType({ commit, state }, { layoutType }) {
    commit('CHANGE_LAYOUT', layoutType);
    document.body.removeAttribute('style');
    applyLayoutConfig(state);
  },

  changeLayoutWidth({ commit, state }, { layoutWidth }) {
    commit('CHANGE_LAYOUT_WIDTH', layoutWidth);
    applyLayoutConfig(state);
  },

  changeSidebarSize({ commit, state }, { sidebarSize }) {
    commit('CHANGE_SIDEBAR_TYPE', sidebarSize);
    applyLayoutConfig(state);
  },

  changeTopbar({ commit, state }, { topbar }) {
    commit('CHANGE_TOPBAR', topbar);
    applyLayoutConfig(state);
  },

  changeMode({ commit, state }, { mode }) {
    commit('CHANGE_MODE', mode);
    applyLayoutConfig(state);
  },

  changePosition({ commit, state }, { position }) {
    commit('CHANGE_POSITION', position);
    applyLayoutConfig(state);
  },

  changeSidebarView({ commit, state }, { sidebarView }) {
    commit('CHANGE_SIDEBAR_VIEW', sidebarView);
    applyLayoutConfig(state);
  },

  changeSidebarColor({ commit, state }, { sidebarColor }) {
    commit('CHANGE_SIDEBAR_COLOR', sidebarColor);
    applyLayoutConfig(state);
  },

  changeSidebarImage({ commit, state }, { sidebarImage }) {
    commit('CHANGE_SIDEBAR_IMAGE', sidebarImage);
    applyLayoutConfig(state);
  },

  changePreloader({ commit, state }, { preloader }) {
    commit('CHANGE_PRELOADER', preloader);
    applyLayoutConfig(state);
  },

  changeVisibility({ commit, state }, { visibility }) {
    commit('CHANGE_VISIBILITY', visibility);
    applyLayoutConfig(state);
  }
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
};