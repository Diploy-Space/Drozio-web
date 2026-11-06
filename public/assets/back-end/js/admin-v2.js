(function () {
  'use strict';

  const ROOT = document.querySelector('.app-v2');
  if (!ROOT) return;

  const LS = {
    get: (k, f) => { try { const v = localStorage.getItem(k); return v == null ? f : JSON.parse(v); } catch { return f; } },
    set: (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch {} }
  };

  const V2API = window.V2 = window.V2 || {};
  V2API._readyCallbacks = V2API._readyCallbacks || [];
  V2API.ready = V2API.ready === true;
  V2API.onReady = function (cb) {
    if (typeof cb !== 'function') return;
    if (V2API.ready) {
      try { cb(); } catch (e) { console.error('v2:ready callback failed', e); }
    } else {
      V2API._readyCallbacks.push(cb);
    }
  };

  const FS_KEY = '6v_v2_immersive';

  const SERVER_PINS = Array.isArray(window.__v2InitialPins) ? window.__v2InitialPins.slice() : null;

  const state = {
    activeSection: LS.get('6v_v2_section', null),
    expanded:     new Set(LS.get('6v_v2_expanded', [])),
    collapsed:    LS.get('6v_v2_collapsed', false),
    pins:         SERVER_PINS !== null ? SERVER_PINS : LS.get('6v_v2_pins', []),
    theme:        LS.get('6v_v2_theme', 'light'),
    fullscreen:   LS.get(FS_KEY, false),
    paletteOpen:  false,
    paletteQuery: '',
    paletteIdx:   0,
    paletteItems: [],
    paletteFiltered: []
  };

  const $root  = (sel) => ROOT.querySelector(sel);
  const $$root = (sel) => Array.from(ROOT.querySelectorAll(sel));
  const $anyById = (id) => document.getElementById(id);

  function persist() {
    LS.set('6v_v2_section',  state.activeSection);
    LS.set('6v_v2_expanded', [...state.expanded]);
    LS.set('6v_v2_collapsed', state.collapsed);
    LS.set('6v_v2_pins', state.pins);
    LS.set('6v_v2_theme', state.theme);
  }

  let pinSyncAbort = null;
  function persistPinsToServer() {
    const url = window.__v2PinsReplaceUrl;
    if (!url) return;
    if (pinSyncAbort) { try { pinSyncAbort.abort(); } catch (e) {} }
    pinSyncAbort = ('AbortController' in window) ? new AbortController() : null;
    fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': window.__v2PinsCsrf || ''
      },
      body: JSON.stringify({ pins: state.pins }),
      signal: pinSyncAbort ? pinSyncAbort.signal : undefined
    }).catch(err => {
      if (err && err.name === 'AbortError') return;
      console.warn('v2 pin sync failed', err);
    });
  }

  function applyTheme() {
    ROOT.classList.toggle('v2-theme-dark', state.theme === 'dark');
  }
  function toggleTheme() {
    state.theme = state.theme === 'dark' ? 'light' : 'dark';
    persist();
    applyTheme();
  }

  function nativeRequest() {
    const el = document.documentElement;
    try { (el.requestFullscreen || el.webkitRequestFullscreen)?.call(el); }
    catch (e) { }
  }
  function nativeExit() {
    const doc = document;
    try { (doc.exitFullscreen || doc.webkitExitFullscreen)?.call(doc); }
    catch (e) {}
  }
  function toggleFullscreen() {
    state.fullscreen = !state.fullscreen;
    LS.set(FS_KEY, state.fullscreen);
    applyFullscreenIcon();
    if (state.fullscreen) nativeRequest();
    else if (document.fullscreenElement || document.webkitFullscreenElement) nativeExit();
  }
  function applyFullscreenIcon() {
    const wantsFullscreen = !!state.fullscreen ||
      !!(document.fullscreenElement || document.webkitFullscreenElement);
    document.querySelectorAll('.v2-fs-enter').forEach(el => { el.style.display = wantsFullscreen ? 'none' : ''; });
    document.querySelectorAll('.v2-fs-exit').forEach(el  => { el.style.display = wantsFullscreen ? '' : 'none'; });
    const fsBtn = document.getElementById('v2-fullscreen-toggle');
    if (fsBtn) {
      const tip = wantsFullscreen
        ? fsBtn.getAttribute('data-v2-tip-exit')
        : fsBtn.getAttribute('data-v2-tip-enter');
      if (tip) {
        fsBtn.setAttribute('data-v2-tooltip', tip);
        fsBtn.setAttribute('aria-label', tip);
      }
    }
  }
  function primeFullscreenResume() {
    if (!state.fullscreen) return;
    if (document.fullscreenElement || document.webkitFullscreenElement) return;
    const handler = () => {
      document.removeEventListener('click', handler, true);
      nativeRequest();
    };
    document.addEventListener('click', handler, true);
  }
  let isUnloading = false;
  window.addEventListener('beforeunload', () => { isUnloading = true; });
  function onNativeFsChange() {
    const native = !!(document.fullscreenElement || document.webkitFullscreenElement);
    if (!native && state.fullscreen && !isUnloading) {
      state.fullscreen = false;
      LS.set(FS_KEY, state.fullscreen);
    }
    applyFullscreenIcon();
  }
  document.addEventListener('fullscreenchange', onNativeFsChange);
  document.addEventListener('webkitfullscreenchange', onNativeFsChange);
  applyFullscreenIcon();
  primeFullscreenResume();

  function activateSection(id) {
    const previous = $root('.v2-ctxpanel .v2-ctx-section.v2-is-on')?.dataset.section || null;
    state.activeSection = id;
    persist();
    $$root('.v2-rail-btn').forEach(b => b.classList.toggle('v2-is-active', b.dataset.section === id));
    $$root('.v2-ctx-section').forEach(s => s.classList.toggle('v2-is-on', s.dataset.section === id));
    if (previous !== id) {
      document.dispatchEvent(new CustomEvent('v2:section:change', { detail: { id, previous } }));
    }
  }

  function pickActiveSectionFromURL() {
    const url = window.location.pathname;
    let best = null;
    $$root('.v2-ctx-section').forEach(sec => {
      const id = sec.dataset.section;
      const matches = Array.from(sec.querySelectorAll('.v2-nav-btn[href], a.v2-nav-item[href], a.v2-nav-child[href]'))
        .map(a => a.getAttribute('href'))
        .filter(Boolean)
        .some(href => url.startsWith(href.replace(/^https?:\/\/[^/]+/, '')));
      if (matches) best = id;
    });
    return best;
  }

  function hydrateActive() {
    let section = pickActiveSectionFromURL();
    if (!section) {
      const activeItem = $root('.v2-ctx-section .v2-nav-item.v2-is-active, .v2-ctx-section .v2-nav-child.v2-is-on');
      if (activeItem) section = activeItem.closest('.v2-ctx-section')?.dataset.section;
    }
    if (!section) section = state.activeSection;
    if (!section) {
      const firstVisible = $$root('.v2-rail-btn[data-section]')[0];
      section = firstVisible?.dataset.section || 'home';
    }
    activateSection(section);
  }

  function isExpanded(id) { return state.expanded.has(id); }
  function siblingExpandables(id) {
    const item = ROOT.querySelector(`.v2-nav-item[data-item="${CSS.escape(id)}"]`);
    const section = item?.closest('.v2-ctx-section');
    if (!section) return [];
    return Array.from(section.querySelectorAll('.v2-nav-item.v2-has-children'))
      .filter(el => el.dataset.item !== id);
  }
  function setExpanded(id, open) {
    if (open) {
      siblingExpandables(id).forEach(other => state.expanded.delete(other.dataset.item));
      state.expanded.add(id);
    } else {
      state.expanded.delete(id);
    }
    persist();
    applyExpanded();
  }
  function toggleExpanded(id) { setExpanded(id, !isExpanded(id)); }

  function toggleExpandedScoped(parentItem) {
    if (!parentItem) return;
    const id = parentItem.dataset.item;
    const inPinSection = !!parentItem.closest('.v2-ctx-group.v2-is-pinned');
    let kc;
    if (inPinSection) {
      kc = parentItem.nextElementSibling;
      if (!kc || !kc.classList.contains('v2-nav-children') || kc.dataset.childrenFor !== id) {
        kc = null;
      }
    } else {
      const sec = parentItem.closest('.v2-ctx-section');
      kc = sec ? sec.querySelector(`.v2-nav-children[data-children-for="${CSS.escape(id)}"]:not([data-v2-pinned])`) : null;
    }
    if (!kc) return;
    const isOpenNow = !kc.classList.contains('v2-is-collapsed');
    const willOpen = !isOpenNow;
    kc.classList.toggle('v2-is-collapsed', !willOpen);
    const chev = parentItem.querySelector('.v2-nav-chev');
    if (chev) chev.classList.toggle('v2-is-open', willOpen);
    if (!inPinSection) {
      if (willOpen) state.expanded.add(id);
      else state.expanded.delete(id);
      if (willOpen) {
        siblingExpandables(id).forEach(other => state.expanded.delete(other.dataset.item));
      }
      persist();
      applyExpanded();
    }
  }

  function applyExpanded() {
    $$root('.v2-nav-item.v2-has-children:not([data-v2-pinned])').forEach(item => {
      const id = item.dataset.item;
      const open = isExpanded(id);
      const chev = item.querySelector('.v2-nav-chev');
      if (chev) chev.classList.toggle('v2-is-open', open);
      const kc = ROOT.querySelector(`.v2-nav-children[data-children-for="${CSS.escape(id)}"]:not([data-v2-pinned])`);
      if (kc) kc.classList.toggle('v2-is-collapsed', !open);
    });
  }

  function autoExpandActive() {
    state.expanded = new Set();
    $$root('.v2-ctx-section .v2-nav-item.v2-has-children').forEach(it => {
      const id = it.dataset.item;
      const hasActiveChild = it.classList.contains('v2-is-active') ||
        !!ROOT.querySelector(`.v2-nav-children[data-children-for="${CSS.escape(id)}"] .v2-nav-child.v2-is-on`);
      if (hasActiveChild) state.expanded.add(id);
    });
    persist();
    applyExpanded();
  }

  function togglePin(id) {
    const wasOn = state.pins.includes(id);

    const parentEl = ROOT.querySelector(`.v2-nav-item.v2-has-children[data-item="${CSS.escape(id)}"]:not([data-v2-pinned])`);
    let parentId = null;
    let siblingChildIds = [];
    if (parentEl) {
      parentId = id;
      const kc = ROOT.querySelector(`.v2-nav-children[data-children-for="${CSS.escape(id)}"]:not([data-v2-pinned])`);
      if (kc) {
        siblingChildIds = Array.from(kc.querySelectorAll('.v2-nav-child[href]'))
          .map(c => 'child:' + c.getAttribute('href'));
      }
    } else {
      const childEl = ROOT.querySelector(`.v2-nav-child[data-pin="${CSS.escape(id)}"]:not([data-v2-pinned])`);
      const kc = childEl ? childEl.closest('.v2-nav-children:not([data-v2-pinned])') : null;
      parentId = kc ? kc.dataset.childrenFor : null;
      if (kc) {
        siblingChildIds = Array.from(kc.querySelectorAll('.v2-nav-child[href]'))
          .map(c => 'child:' + c.getAttribute('href'));
      }
    }

    if (wasOn) {
      if (parentEl) {
        const drop = new Set([id, ...siblingChildIds]);
        state.pins = state.pins.filter(p => !drop.has(p));
      } else {
        const drop = new Set([id]);
        if (parentId && state.pins.includes(parentId)) drop.add(parentId);
        state.pins = state.pins.filter(p => !drop.has(p));
      }
    } else {
      state.pins.push(id);
      if (parentEl) {
        siblingChildIds.forEach(cid => {
          if (!state.pins.includes(cid)) state.pins.push(cid);
        });
      } else if (parentId && siblingChildIds.length > 0) {
        const allPinned = siblingChildIds.every(cid => state.pins.includes(cid));
        if (allPinned && !state.pins.includes(parentId)) state.pins.push(parentId);
      }
    }
    persist();
    persistPinsToServer();
    applyPins();
  }
  function injectChildPinButtons() {
    $$root('.v2-nav-child').forEach(child => {
      if (child.querySelector(':scope > .v2-pin-btn')) return;
      const href = child.getAttribute('href') || '';
      if (!href || href.startsWith('javascript:') || href === '#') return;
      const pinId = 'child:' + href;
      child.dataset.pin = pinId;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'v2-pin-btn v2-pin-btn-child';
      btn.dataset.pin = pinId;
      btn.setAttribute('aria-label', 'Pin');
      const count = child.querySelector(':scope > .v2-nav-child-count');
      if (count) child.insertBefore(btn, count);
      else child.appendChild(btn);
    });
  }
  function applyPins() {
    document.querySelectorAll('.v2-pin-btn').forEach(btn => {
      btn.classList.toggle('v2-is-on', state.pins.includes(btn.dataset.pin));
    });
    $$root('.v2-ctx-section').forEach(sec => {
      const pinGroup = sec.querySelector('.v2-ctx-group.v2-is-pinned');
      if (!pinGroup) return;
      const host = pinGroup.querySelector('.v2-ctx-group-body');
      if (!host) return;
      host.innerHTML = '';
      const pinnedParentIds = new Set();
      state.pins.forEach(id => {
        const p = sec.querySelector(`.v2-nav-item.v2-has-children[data-item="${CSS.escape(id)}"]`);
        if (p) pinnedParentIds.add(id);
      });
      const childGroupContainers = new Map();
      let any = false;
      state.pins.forEach(id => {
        const parent = sec.querySelector(`.v2-nav-item[data-item="${CSS.escape(id)}"]`);
        if (parent && !parent.closest('.v2-ctx-group.v2-is-pinned')) {
          const cloneParent = parent.cloneNode(true);
          cloneParent.dataset.v2Pinned = '1';
          host.appendChild(cloneParent);
          if (parent.classList.contains('v2-has-children')) {
            const kids = sec.querySelector(`.v2-nav-children[data-children-for="${CSS.escape(id)}"]`);
            if (kids) {
              const cloneKids = kids.cloneNode(true);
              cloneKids.dataset.v2Pinned = '1';
              cloneKids.querySelectorAll('.v2-nav-child').forEach(c => c.dataset.v2Pinned = '1');
              host.appendChild(cloneKids);
              const hasActiveChild = parent.classList.contains('v2-is-active') ||
                !!cloneKids.querySelector('.v2-nav-child.v2-is-on');
              cloneKids.classList.toggle('v2-is-collapsed', !hasActiveChild);
              const chev = cloneParent.querySelector('.v2-nav-chev');
              if (chev) chev.classList.toggle('v2-is-open', hasActiveChild);
            }
          }
          any = true;
          return;
        }
        const child = sec.querySelector(`.v2-nav-child[data-pin="${CSS.escape(id)}"]`)
                   || sec.querySelector(`.v2-nav-child:has(> .v2-pin-btn[data-pin="${CSS.escape(id)}"])`);
        if (child && !child.closest('.v2-ctx-group.v2-is-pinned')) {
          const kidsContainer = child.closest('.v2-nav-children');
          const parentItemId = kidsContainer ? kidsContainer.dataset.childrenFor : null;
          if (!parentItemId) return;
          if (pinnedParentIds.has(parentItemId)) return;

          let groupChildren = childGroupContainers.get(parentItemId);
          if (!groupChildren) {
            const parentItem = sec.querySelector(`.v2-nav-item[data-item="${CSS.escape(parentItemId)}"]`);
            if (!parentItem) return;
            const cloneParent = parentItem.cloneNode(true);
            cloneParent.dataset.v2Pinned = '1';
            const chev = cloneParent.querySelector('.v2-nav-chev');
            if (chev) chev.classList.remove('v2-is-open');
            host.appendChild(cloneParent);

            groupChildren = document.createElement('div');
            groupChildren.className = 'v2-nav-children v2-is-collapsed';
            groupChildren.dataset.childrenFor = parentItemId;
            groupChildren.dataset.v2Pinned = '1';
            host.appendChild(groupChildren);
            childGroupContainers.set(parentItemId, groupChildren);
          }

          const cloneChild = child.cloneNode(true);
          cloneChild.dataset.v2Pinned = '1';
          groupChildren.appendChild(cloneChild);
          any = true;
        }
      });
      childGroupContainers.forEach((kids) => {
        const hasActiveChild = !!kids.querySelector('.v2-nav-child.v2-is-on');
        kids.classList.toggle('v2-is-collapsed', !hasActiveChild);
        const cloneParent = kids.previousElementSibling;
        if (cloneParent) {
          const chev = cloneParent.querySelector('.v2-nav-chev');
          if (chev) chev.classList.toggle('v2-is-open', hasActiveChild);
        }
      });
      pinGroup.style.display = any ? '' : 'none';
    });

    const fly = flyout();
    if (fly && fly.classList.contains('v2-is-open') && flyoutSection) {
      const source = ROOT.querySelector(`.v2-ctx-section[data-section="${CSS.escape(flyoutSection)}"]`);
      if (source) {
        fly.innerHTML = source.innerHTML;
        fly.querySelectorAll('.v2-nav-children').forEach(ch => ch.classList.remove('v2-is-collapsed'));
        fly.querySelectorAll('.v2-nav-chev').forEach(ch => ch.classList.add('v2-is-open'));
      }
    }
  }

  function isMobileViewport() { return window.innerWidth < 992; }
  function toggleSidebar() {
    if (isMobileViewport()) {
      ROOT.classList.toggle('v2-is-open-mobile');
      hideFlyout(0);
      return;
    }
    state.collapsed = !state.collapsed;
    persist();
    applyCollapsed();
    hideFlyout(0);
  }
  function closeMobileSidebar() { ROOT.classList.remove('v2-is-open-mobile'); }
  document.addEventListener('click', (e) => {
    if (!isMobileViewport()) return;
    if (!ROOT.classList.contains('v2-is-open-mobile')) return;
    if (e.target.closest('.v2-rail, .v2-ctxpanel, .v2-rail-flyout, #v2-sidebar-toggle')) return;
    closeMobileSidebar();
  });
  document.addEventListener('click', (e) => {
    if (!isMobileViewport()) return;
    if (!ROOT.classList.contains('v2-is-open-mobile')) return;
    if (e.target.closest('.v2-nav-chev')) return;
    const child = e.target.closest('.v2-nav-child');
    if (child) { closeMobileSidebar(); return; }
    const item = e.target.closest('.v2-nav-item');
    if (item && !item.classList.contains('v2-has-children')) {
      closeMobileSidebar();
    }
  });
  window.addEventListener('resize', () => {
    if (!isMobileViewport()) closeMobileSidebar();
  });
  function applyCollapsed() {
    ROOT.classList.toggle('v2-collapsed', state.collapsed);
    const tBtn = document.getElementById('v2-sidebar-toggle');
    if (tBtn) {
      const expandedTip = tBtn.getAttribute('data-v2-tip-expanded');
      const collapsedTip = tBtn.getAttribute('data-v2-tip-collapsed');
      const tip = state.collapsed ? collapsedTip : expandedTip;
      if (tip) {
        tBtn.setAttribute('data-v2-tooltip', tip);
        tBtn.setAttribute('aria-label', tip);
      }
    }
  }
  let flyoutTimer = null;
  let flyoutSection = null;

  function flyout() { return $anyById('v2-rail-flyout'); }

  function showFlyout(id) {
    if (!state.collapsed) return;
    const source = ROOT.querySelector(`.v2-ctx-section[data-section="${CSS.escape(id)}"]`);
    const host = flyout();
    if (!source || !host) return;
    clearTimeout(flyoutTimer);
    flyoutSection = id;
    host.innerHTML = source.innerHTML;
    host.querySelectorAll('.v2-nav-children').forEach(ch => ch.classList.remove('v2-is-collapsed'));
    host.querySelectorAll('.v2-nav-chev').forEach(ch => ch.classList.add('v2-is-open'));
    host.classList.add('v2-is-open');
    $$root('.v2-rail-btn').forEach(b => b.classList.remove('v2-is-peeking'));
    ROOT.querySelector(`.v2-rail-btn[data-section="${CSS.escape(id)}"]`)?.classList.add('v2-is-peeking');
  }
  function hideFlyout(delay = 140) {
    clearTimeout(flyoutTimer);
    flyoutTimer = setTimeout(() => {
      flyoutSection = null;
      flyout()?.classList.remove('v2-is-open');
      $$root('.v2-rail-btn').forEach(b => b.classList.remove('v2-is-peeking'));
    }, delay);
  }

  const tooltip = {
    el: null,
    showTimer: null,
    current: null,
    ensure() {
      if (this.el) return this.el;
      const el = document.createElement('div');
      el.className = 'v2-tooltip';
      el.setAttribute('role', 'tooltip');
      document.body.appendChild(el);
      this.el = el;
      return el;
    },
    showFor(target) {
      const label = target.getAttribute('data-label') || target.getAttribute('data-v2-tooltip');
      if (!label) return;
      const el = this.ensure();
      el.textContent = label;
      const rect = target.getBoundingClientRect();
      const isRTL = document.documentElement.getAttribute('dir') === 'rtl';
      const isHeader = !!target.closest('.v2-header');
      el.classList.toggle('v2-tooltip-below', isHeader);
      if (isHeader) {
        el.style.top = (rect.bottom + 8) + 'px';
        el.style.left = 'auto';
        el.style.right = 'auto';
        el.style.transform = '';
        el.offsetWidth;
        const popW = el.offsetWidth;
        let left = rect.left + rect.width / 2 - popW / 2;
        const margin = 8;
        if (left + popW > window.innerWidth - margin) left = window.innerWidth - popW - margin;
        if (left < margin) left = margin;
        el.style.left = left + 'px';
      } else {
        el.style.top = (rect.top + rect.height / 2) + 'px';
        el.style.transform = 'translateY(-50%)';
        if (isRTL) {
          el.style.right = (window.innerWidth - rect.left + 10) + 'px';
          el.style.left  = 'auto';
        } else {
          el.style.left  = (rect.right + 10) + 'px';
          el.style.right = 'auto';
        }
      }
      el.offsetWidth;
      el.classList.add('v2-is-visible');
    },
    hide() {
      clearTimeout(this.showTimer);
      this.current = null;
      if (this.el) this.el.classList.remove('v2-is-visible');
    },
    schedule(target, delay = 180) {
      clearTimeout(this.showTimer);
      this.current = target;
      this.showTimer = setTimeout(() => {
        if (this.current === target) this.showFor(target);
      }, delay);
    }
  };

  function ensureTruncatedTooltip(target) {
    let label = null;
    if (target.classList.contains('v2-nav-item') || target.classList.contains('v2-nav-child')) {
      label = target.querySelector('.v2-nav-label, .v2-nav-child-label');
    } else if (target.matches('.v2-crumbs > a:not(.v2-crumb-current), .v2-crumbs > span:not(.v2-crumb-current)')) {
      label = target;
    } else if (target.matches('.v2-crumbs .v2-crumb-text')) {
      label = target;
    }
    if (!label) return true;
    const truncated = label.scrollWidth > label.clientWidth + 1;
    if (!truncated) {
      target.removeAttribute('data-v2-tooltip');
      return false;
    }
    target.setAttribute('data-v2-tooltip', label.textContent.trim());
    return true;
  }

  function bindTooltips() {
    const tooltipSelector = '.v2-rail-btn[data-label], .v2-rail-setup[data-label], [data-v2-tooltip], .v2-nav-item, .v2-nav-child, .v2-crumbs > a:not(.v2-crumb-current), .v2-crumbs > span:not(.v2-crumb-current), .v2-crumbs .v2-crumb-text';
    document.addEventListener('mouseover', (e) => {
      if (isMobileViewport()) return;
      const target = e.target.closest && e.target.closest(tooltipSelector);
      if (!target) return;
      if (!target.closest('.app-v2, .v2-rail-flyout, .v2-setup-popover')) return;
      if (tooltip.current === target) return;
      if (!ensureTruncatedTooltip(target)) return;
      tooltip.schedule(target, 180);
    });
    document.addEventListener('mouseout', (e) => {
      if (isMobileViewport()) { tooltip.hide(); return; }
      const target = e.target.closest && e.target.closest(tooltipSelector);
      if (!target) return;
      const related = e.relatedTarget;
      if (related && target.contains(related)) return;
      if (related && related.closest && related.closest(tooltipSelector) === target) return;
      tooltip.hide();
    });
    ROOT.addEventListener('scroll', () => tooltip.hide(), true);
    window.addEventListener('scroll', () => tooltip.hide(), true);
    window.addEventListener('resize', () => tooltip.hide());
  }

  function closeAllDropdowns(except) {
    $$root('.v2-dropdown.v2-is-open').forEach(d => {
      if (d === except) return;
      d.classList.remove('v2-is-open');
      d.querySelectorAll('details[open]').forEach(section => { section.open = false; });
    });
  }
  function positionDropdownMenu(btn) {
    const menu = btn.closest('.v2-dropdown')?.querySelector(':scope > .v2-dropdown-menu');
    if (!menu) return;
    if (menu.classList.contains('v2-orders-menu') && isMobileViewport()) return;
    if (menu.classList.contains('v2-profile-card')) return;
    const r = btn.getBoundingClientRect();
    const isRTL = getComputedStyle(document.documentElement).direction === 'rtl';
    menu.style.top = (r.bottom + 8) + 'px';
    if (isRTL) {
      menu.style.removeProperty('right');
      menu.style.left = r.left + 'px';
    } else {
      menu.style.removeProperty('left');
      menu.style.right = (window.innerWidth - r.right) + 'px';
    }
  }
  function bindDropdowns() {
    $$root('.v2-dropdown > [data-v2-dropdown-toggle]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const parent = btn.closest('.v2-dropdown');
        const isOpen = parent.classList.contains('v2-is-open');
        closeAllDropdowns();
        if (!isOpen) {
          positionDropdownMenu(btn);
          parent.classList.add('v2-is-open');
          if (isMobileViewport() && !parent.classList.contains('v2-rail-profile')) closeMobileSidebar();
        }
      });
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.v2-dropdown')) closeAllDropdowns();
    });
  }

  function buildPaletteIndex() {
    state.paletteItems = $$root('.v2-ctx-section .v2-nav-item[data-item], .v2-ctx-section .v2-nav-child[data-child-item]').map(el => {
      const label = (el.querySelector('.v2-nav-label, .v2-nav-child-label')?.textContent || '').trim();
      const href  = el.querySelector('a[href], .v2-nav-btn[href]')?.getAttribute('href') ||
                    el.getAttribute('href') || null;
      const section = el.closest('.v2-ctx-section')?.dataset.section || '';
      const group = (el.closest('.v2-ctx-group')?.querySelector('.v2-ctx-group-head > span, .v2-ctx-group-head')?.textContent || '').trim();
      return { label, href, section, group };
    }).filter(x => x.label && x.href);
  }
  function v2PaletteRenderMenu(query) {
    const q = (query || '').trim().toLowerCase();
    const filtered = q
      ? state.paletteItems.filter(x =>
          x.label.toLowerCase().includes(q) ||
          x.group.toLowerCase().includes(q) ||
          x.section.toLowerCase().includes(q)).slice(0, 25)
      : state.paletteItems.slice(0, 12);
    state.paletteFiltered = filtered;
    if (state.paletteIdx >= filtered.length) state.paletteIdx = 0;
    const list = $anyById('v2-palette-list');
    if (!list) return;
    if (!filtered.length) { list.innerHTML = '<div class="v2-palette-empty">No matches</div>'; return; }
    list.innerHTML = filtered.map((it, i) => `
      <div class="v2-palette-row ${i === state.paletteIdx ? 'v2-is-active' : ''}" data-p-idx="${i}" data-href="${it.href}">
        <div class="v2-palette-row-text">
          <span class="v2-palette-row-label">${escapeHtml(it.label)}</span>
          <span class="v2-palette-row-path">${escapeHtml(it.section)} › ${escapeHtml(it.group)}</span>
        </div>
      </div>
    `).join('');
    const activeRow = list.querySelector('.v2-palette-row.v2-is-active');
    if (activeRow) activeRow.scrollIntoView({ block: 'nearest' });
  }
  function renderPalette() {
    const scrim = $anyById('v2-palette-scrim');
    if (!scrim) return;
    if (!state.paletteOpen) {
      scrim.hidden = true;
      return;
    }
    scrim.hidden = false;
    const q = (state.paletteQuery || '').trim();
    v2PaletteRenderMenu(q);
  }
  function openPalette() {
    state.paletteOpen = true;
    state.paletteQuery = '';
    state.paletteIdx = 0;
    buildPaletteIndex();
    renderPalette();
    const input = $anyById('v2-palette-input');
    if (input) { input.value = ''; setTimeout(() => input.focus(), 20); }
    if (isMobileViewport()) closeMobileSidebar();
  }
  function closePalette() { state.paletteOpen = false; renderPalette(); }
  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
  function escapeAttr(s) { return escapeHtml(s); }

  const TRAIL_KEY = '6v_v2_trail';
  const SEP = '<span class="v2-crumb-sep" aria-hidden="true">›</span>';

  function isUsableHref(href) {
    return href && href !== '#' && !href.startsWith('javascript:');
  }
  function firstHrefIn(section) {
    if (!section) return '';
    const el = section.querySelector('a.v2-nav-item[href], .v2-nav-btn[href], a.v2-nav-child[href]');
    return el ? el.getAttribute('href') || '' : '';
  }
  function itemHref(item) {
    if (!item) return '';
    if (item.tagName === 'A' && item.hasAttribute('href')) return item.getAttribute('href');
    return item.querySelector('.v2-nav-btn[href]')?.getAttribute('href') || '';
  }
  function normalizePath(href) {
    if (!href) return '';
    const clean = href.replace(/^https?:\/\/[^/]+/, '').split('?')[0].split('#')[0];
    return clean.replace(/\/$/, '');
  }
  function isOnMenuPage() {
    const current = normalizePath(window.location.pathname);
    const menuHrefs = new Set();
    $$root('a.v2-nav-item[href], .v2-nav-btn[href], a.v2-nav-child[href]').forEach(a => {
      const h = normalizePath(a.getAttribute('href'));
      if (h) menuHrefs.add(h);
    });
    return menuHrefs.has(current);
  }
  function getCurrentPageLabel() {
    const h1 = document.querySelector('.v2-main h1, .v2-main .page-title, .main-content h1');
    if (h1 && h1.textContent.trim()) return h1.textContent.trim().replace(/\s+/g, ' ').slice(0, 80);
    const title = (document.title || '').trim();
    const stripped = title.replace(/\s*[\|\-–—]\s*[^|\-–—]+$/, '').trim();
    if (stripped) return stripped.slice(0, 80);
    const seg = window.location.pathname.split('/').filter(Boolean).pop() || '';
    return seg.replace(/[-_]+/g, ' ');
  }
  function readTrail() {
    try { return JSON.parse(sessionStorage.getItem(TRAIL_KEY)) || null; } catch { return null; }
  }
  function writeTrail(trail) {
    try { sessionStorage.setItem(TRAIL_KEY, JSON.stringify(trail)); } catch {}
  }
  function crumbAnchor(cls, href, label) {
    const isCurrent = cls.indexOf('v2-crumb-current') !== -1;
    const inner = isCurrent
      ? `<span class="v2-crumb-text">${escapeHtml(label)}</span>`
      : escapeHtml(label);
    if (isUsableHref(href)) return `<a class="${cls}" href="${escapeAttr(href)}">${inner}</a>`;
    return `<span class="${cls}">${inner}</span>`;
  }
  function buildMenuBreadcrumb() {
    const activeSection = ROOT.querySelector(`.v2-ctx-section[data-section="${CSS.escape(state.activeSection || '')}"]`);
    const sectionLabel = activeSection?.querySelector('.v2-ctx-title')?.textContent?.trim() ||
                         ROOT.querySelector(`.v2-rail-btn[data-section="${CSS.escape(state.activeSection || '')}"]`)?.dataset.label || '';
    const sectionHref  = firstHrefIn(activeSection);

    const activeChild = activeSection?.querySelector('.v2-nav-child.v2-is-on');
    let activeItem = activeSection?.querySelector('.v2-nav-item.v2-is-active') ||
                     activeChild?.closest('.v2-nav-item') ||
                     null;
    let isFallbackItem = false;
    if (!activeItem && activeSection) {
      activeItem = activeSection.querySelector('.v2-nav-item');
      isFallbackItem = true;
    }
    return {
      sectionLabel,
      sectionHref,
      itemLabel: activeItem?.querySelector('.v2-nav-label')?.textContent?.trim() || '',
      itemHref: itemHref(activeItem),
      childLabel: activeChild?.querySelector('.v2-nav-child-label')?.textContent?.trim() || '',
      childHref: activeChild?.getAttribute('href') || '',
      isFallbackItem,
    };
  }

  function renderBreadcrumb(parts) {
    const host = $anyById('v2-header-crumbs');
    if (!host) return;
    host.innerHTML = parts.join(' ');
  }

  function updateBreadcrumbs() {
    const host = $anyById('v2-header-crumbs');
    if (!host) return;

    const onMenu = isOnMenuPage();
    const trail = buildMenuBreadcrumb();

    if (onMenu) {
      const parts = [];
      if (trail.sectionLabel) parts.push(crumbAnchor('v2-primary-crumb', trail.sectionHref, trail.sectionLabel));
      if (trail.itemLabel) {
        parts.push(SEP);
        const isCurrent = !trail.childLabel && !trail.isFallbackItem;
        parts.push(crumbAnchor(isCurrent ? 'v2-crumb-current' : '', trail.itemHref, trail.itemLabel));
      }
      if (trail.childLabel) {
        parts.push(SEP, `<span class="v2-crumb-current"><span class="v2-crumb-text">${escapeHtml(trail.childLabel)}</span></span>`);
      }
      renderBreadcrumb(parts);
      if (trail.sectionLabel && trail.itemLabel && !trail.isFallbackItem) writeTrail(trail);
      return;
    }

    const saved = readTrail() || trail;
    const pageLabel = getCurrentPageLabel();
    const parts = [];
    if (saved.sectionLabel) parts.push(crumbAnchor('v2-primary-crumb', saved.sectionHref, saved.sectionLabel));
    if (saved.itemLabel) {
      parts.push(SEP, crumbAnchor('', saved.itemHref, saved.itemLabel));
    }
    if (saved.childLabel) {
      parts.push(SEP, crumbAnchor('', saved.childHref, saved.childLabel));
    }
    if (pageLabel) {
      parts.push(SEP, `<span class="v2-crumb-current"><span class="v2-crumb-text">${escapeHtml(pageLabel)}</span></span>`);
    }
    renderBreadcrumb(parts);
  }

  document.addEventListener('click', (e) => {
    const insideV1 = e.target.closest('.app-v2, .v2-rail-flyout, .v2-palette-scrim');
    if (!insideV1) return;

    const pinBtn = e.target.closest('.v2-pin-btn[data-pin]');
    if (pinBtn) {
      e.preventDefault(); e.stopPropagation();
      const pinId = pinBtn.dataset.pin;
      const inPinSection = !!pinBtn.closest('.v2-ctx-group.v2-is-pinned');
      const isParentPin = !pinId.startsWith('child:');
      if (inPinSection && isParentPin && !state.pins.includes(pinId)) {
        const kc = ROOT.querySelector(`.v2-nav-children[data-children-for="${CSS.escape(pinId)}"]:not([data-v2-pinned])`);
        if (kc) {
          const childIds = new Set();
          kc.querySelectorAll('.v2-nav-child[href]').forEach(c => {
            childIds.add('child:' + c.getAttribute('href'));
          });
          state.pins = state.pins.filter(p => !childIds.has(p));
          persist();
          applyPins();
          return;
        }
      }
      togglePin(pinId);
      return;
    }

    const posLink = e.target.closest('.v2-nav-item[data-item="pos"], .v2-nav-item[data-item="v-pos"]');
    if (posLink) {
      const href = posLink.getAttribute('href');
      e.preventDefault();
      if (href && href !== '#') window.open(href, '_blank', 'noopener');
      if (!state.collapsed) {
        state.collapsed = true;
        persist();
        applyCollapsed();
        hideFlyout(0);
      }
      return;
    }

    if (e.target.closest('#v2-sidebar-toggle')) {
      toggleSidebar();
      return;
    }

    if (e.target.closest('#v2-theme-toggle')) {
      toggleTheme();
      return;
    }

    if (e.target.closest('#v2-fullscreen-toggle')) {
      toggleFullscreen();
      return;
    }

    if (e.target.closest('#v2-header-search')) {
      return;
    }

    const pRow = e.target.closest('.v2-palette-row[data-href]');
    if (pRow) {
      const href = pRow.dataset.href;
      closePalette();
      if (href) window.location.href = href;
      return;
    }
    if (e.target.id === 'v2-palette-scrim') { closePalette(); return; }

    const chevClick = e.target.closest('.v2-nav-chev');
    if (chevClick) {
      const item = chevClick.closest('.v2-nav-item.v2-has-children');
      if (item) {
        e.preventDefault(); e.stopPropagation();
        toggleExpandedScoped(item);
        return;
      }
    }

    const parentWithKids = e.target.closest('.v2-nav-item.v2-has-children');
    if (parentWithKids && !e.target.closest('.v2-pin-btn')) {
      e.preventDefault();
      toggleExpandedScoped(parentWithKids);
      return;
    }

    const railBtn = e.target.closest('.v2-rail-btn[data-section]');
    if (railBtn) {
      activateSection(railBtn.dataset.section);
      autoExpandActive();
      hideFlyout(0);
      tooltip.hide();
      if (window.innerWidth >= 768 && window.innerWidth < 992) {
        ROOT.classList.add('v2-is-open-mobile');
      }
      if (window.innerWidth >= 992 && state.collapsed) {
        state.collapsed = false;
        persist();
        applyCollapsed();
      }
      return;
    }
  });

  document.addEventListener('input', (e) => {
    if (e.target.id === 'v2-palette-input') {
      state.paletteQuery = e.target.value;
      state.paletteIdx = 0;
      renderPalette();
    }
  });

  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      return;
    }
    if (!state.paletteOpen) return;
    if (e.key === 'Escape') { closePalette(); return; }
    const list = state.paletteFiltered || [];
    if (!list.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      state.paletteIdx = Math.min(list.length - 1, state.paletteIdx + 1);
      renderPalette();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      state.paletteIdx = Math.max(0, state.paletteIdx - 1);
      renderPalette();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      const pick = list[state.paletteIdx];
      if (pick && pick.href) { closePalette(); window.location.href = pick.href; }
    }
  });

  document.addEventListener('mouseover', (e) => {
    const railBtn = e.target.closest('.v2-rail-btn[data-section]');
    if (railBtn && state.collapsed) {
      if (flyoutSection !== railBtn.dataset.section) showFlyout(railBtn.dataset.section);
      else clearTimeout(flyoutTimer);
      return;
    }
    if (e.target.closest('.v2-rail-flyout')) { clearTimeout(flyoutTimer); return; }
  });

  document.addEventListener('mouseout', (e) => {
    if (!state.collapsed) return;
    const leftRail   = e.target.closest('.v2-rail');
    const leftFlyout = e.target.closest('.v2-rail-flyout');
    if (!leftRail && !leftFlyout) return;
    const to = e.relatedTarget;
    const stillInHoverZone = to && (
      (to.closest && (to.closest('.v2-rail-btn[data-section]') || to.closest('.v2-rail-flyout')))
    );
    if (!stillInHoverZone) hideFlyout(140);
  });
  document.addEventListener('mouseleave', () => hideFlyout(140));

  const setupPop = {
    el: null,
    backdrop: null,
    trigger: null,
    ensure() {
      this.el = this.el || document.getElementById('v2-setup-popover');
      this.backdrop = this.backdrop || document.getElementById('v2-setup-backdrop');
      return this.el;
    },
    position(trigger) {
      const pop = this.el;
      if (!pop) return;
      pop.removeAttribute('hidden');
      pop.style.visibility = 'hidden';
      pop.classList.add('v2-is-open');
      const triggerRect = trigger.getBoundingClientRect();
      const popRect = pop.getBoundingClientRect();
      const margin = 12;
      const gap = 18;
      let top = triggerRect.top - popRect.height - gap;
      if (top < margin) top = margin;
      const isRTL = document.documentElement.getAttribute('dir') === 'rtl';
      const contentOffset = 8;
      let left;
      if (isRTL) {
        left = triggerRect.right - popRect.width + contentOffset;
      } else {
        left = triggerRect.left - contentOffset;
      }
      if (left + popRect.width > window.innerWidth - margin) {
        left = window.innerWidth - popRect.width - margin;
      }
      if (left < margin) left = margin;
      pop.style.top = top + 'px';
      pop.style.left = left + 'px';
      pop.style.visibility = '';
    },
    open(trigger) {
      if (!this.ensure()) return;
      this.trigger = trigger;
      trigger.setAttribute('data-v2-setup-open', '1');
      if (this.backdrop) { this.backdrop.removeAttribute('hidden'); this.backdrop.classList.add('v2-is-open'); }
      this.position(trigger);
      if (isMobileViewport()) closeMobileSidebar();
    },
    close() {
      if (!this.ensure()) return;
      this.el.classList.remove('v2-is-open');
      if (this.backdrop) this.backdrop.classList.remove('v2-is-open');
      if (this.trigger) { this.trigger.removeAttribute('data-v2-setup-open'); this.trigger = null; }
      setTimeout(() => {
        if (!this.el.classList.contains('v2-is-open')) {
          this.el.setAttribute('hidden', '');
          if (this.backdrop) this.backdrop.setAttribute('hidden', '');
        }
      }, 200);
    },
    toggle(trigger) {
      if (this.trigger === trigger && this.el && this.el.classList.contains('v2-is-open')) {
        this.close();
      } else {
        this.open(trigger);
      }
    }
  };

  function bindSetupPopover() {
    if (!setupPop.ensure()) return;
    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-v2-setup-trigger]');
      if (trigger) {
        e.preventDefault();
        e.stopPropagation();
        setupPop.toggle(trigger);
        return;
      }
      if (e.target.closest('[data-v2-setup-close]')) {
        setupPop.close();
        return;
      }
      if (setupPop.trigger && !e.target.closest('#v2-setup-popover')) {
        setupPop.close();
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && setupPop.trigger) setupPop.close();
    });
    window.addEventListener('resize', () => {
      if (setupPop.trigger) setupPop.position(setupPop.trigger);
    });
    const main = ROOT.querySelector('.v2-main');
    if (main) main.addEventListener('scroll', () => {
      if (setupPop.trigger) setupPop.position(setupPop.trigger);
    }, { passive: true });
  }

  function disableParentHrefs() {
    ROOT.querySelectorAll('.v2-nav-item.v2-has-children > .v2-nav-btn[href]').forEach(btn => {
      if (btn.hasAttribute('href') && !btn.hasAttribute('data-href')) {
        btn.setAttribute('data-href', btn.getAttribute('href'));
        btn.removeAttribute('href');
      }
      btn.removeAttribute('title');
    });
  }

  function scrollActiveIntoView() {
    const activeSection = ROOT.querySelector('.v2-ctx-section.v2-is-on');
    if (!activeSection) return;
    const scroller = activeSection.closest('.v2-ctx-scroll');
    if (!scroller) return;
    const active = activeSection.querySelector('.v2-nav-child.v2-is-on') || activeSection.querySelector('.v2-nav-item.v2-is-active');
    if (!active) return;
    const scRect = scroller.getBoundingClientRect();
    const aRect  = active.getBoundingClientRect();
    const target = aRect.top - scRect.top + scroller.scrollTop - 8;
    scroller.scrollTop = Math.max(0, target);
  }

  function boot() {
    applyTheme();
    applyCollapsed();
    bindDropdowns();
    bindTooltips();
    bindSetupPopover();
    disableParentHrefs();
    hydrateActive();
    autoExpandActive();
    injectChildPinButtons();
    applyPins();
    buildPaletteIndex();
    updateBreadcrumbs();
    scrollActiveIntoView();
    ROOT.classList.add('v2-nav-booted');

    V2API.ready = true;
    V2API._readyCallbacks.splice(0).forEach(cb => {
      try { cb(); } catch (e) { console.error('v2:ready callback failed', e); }
    });
    document.dispatchEvent(new CustomEvent('v2:ready', {
      detail: { root: ROOT, rehydrated: false }
    }));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
      hydrateActive();
      autoExpandActive();
      updateBreadcrumbs();
      scrollActiveIntoView();
      document.dispatchEvent(new CustomEvent('v2:rehydrated', {
        detail: { root: ROOT, source: 'pageshow' }
      }));
    }
  });

  window.addEventListener('popstate', () => {
    hydrateActive();
    autoExpandActive();
    updateBreadcrumbs();
    scrollActiveIntoView();
    document.dispatchEvent(new CustomEvent('v2:rehydrated', {
      detail: { root: ROOT, source: 'popstate' }
    }));
  });

  document.addEventListener('v2:page-loaded', () => {
    hydrateActive();
    autoExpandActive();
    applyPins();
    updateBreadcrumbs();
    scrollActiveIntoView();
    document.dispatchEvent(new CustomEvent('v2:rehydrated', {
      detail: { root: ROOT, source: 'pjax' }
    }));
  });

})();
