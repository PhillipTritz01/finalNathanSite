/**
 * Enhanced Gallery Modal for CMS Admin
 * Provides improved performance, accessibility, and user experience
 */

class EnhancedGalleryModal {
  constructor() {
    this.modalEl = document.getElementById('galleryModal');
    if (!this.modalEl) {
      console.warn('Gallery modal element not found');
      return;
    }

    this.modal = new bootstrap.Modal(this.modalEl);
    this.grid = this.modalEl.querySelector('.gallery-grid');
    this.spinner = this.modalEl.querySelector('.loading-spinner');
    this.errorMessage = this.modalEl.querySelector('.error-message');
    this.emptyState = this.modalEl.querySelector('.empty-state');
    this.selectAll = this.modalEl.querySelector('.select-all-modal');
    this.delBtn = this.modalEl.querySelector('.delete-selected');
    this.selectionCount = this.modalEl.querySelector('#selectionCount');
    this.refreshBtn = this.modalEl.querySelector('#refreshModal');
    this.retryBtn = this.modalEl.querySelector('#retryLoad');
    
    // State management
    this.currentItems = [];
    this.currentIds = [];
    this.selected = new Set();
    this.lightbox = null;
    this.currentPortfolioId = null;
    this.isLoading = false;
    
    // Virtual scrolling for performance
    this.virtualScrolling = {
      enabled: false,
      itemHeight: 200,
      containerHeight: 0,
      scrollTop: 0,
      visibleStart: 0,
      visibleEnd: 0,
      buffer: 5
    };
    
    // Performance optimizations
    this.intersectionObserver = null;
    this.resizeObserver = null;
    this.debounceTimers = new Map();
    
    this.init();
  }

  init() {
    this.attachEventListeners();
    this.setupKeyboardNavigation();
    this.setupFocusTrap();
    this.setupIntersectionObserver();
    this.setupResizeObserver();
  }

  setupIntersectionObserver() {
    if ('IntersectionObserver' in window) {
      this.intersectionObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach(entry => {
            const img = entry.target;
            if (entry.isIntersecting && !img.src && img.dataset.src) {
              img.src = img.dataset.src;
              img.removeAttribute('data-src');
            }
          });
        },
        { rootMargin: '50px' }
      );
    }
  }

  setupResizeObserver() {
    if ('ResizeObserver' in window) {
      this.resizeObserver = new ResizeObserver(
        this.debounce(() => {
          this.handleResize();
        }, 250)
      );
    }
  }

  debounce(func, wait) {
    return (...args) => {
      const key = func.name || 'anonymous';
      clearTimeout(this.debounceTimers.get(key));
      this.debounceTimers.set(key, setTimeout(() => func.apply(this, args), wait));
    };
  }

  attachEventListeners() {
    // Modal open triggers
    document.querySelectorAll('[data-bs-target="#galleryModal"]').forEach(btn => {
      btn.addEventListener('click', (e) => this.openModal(e.target));
    });

    // Form submission
    this.modalEl.querySelector('.delete-media-form').addEventListener('submit', (e) => this.handleDelete(e));
    
    // Select all checkbox
    this.selectAll.addEventListener('change', () => this.handleSelectAll());
    
    // Refresh button
    this.refreshBtn.addEventListener('click', () => this.refreshGallery());
    
    // Retry button
    this.retryBtn.addEventListener('click', () => this.retryLoad());
    
    // Modal events
    this.modalEl.addEventListener('hidden.bs.modal', () => this.cleanup());
    this.modalEl.addEventListener('shown.bs.modal', () => this.onModalShown());
    
    // Scroll event for virtual scrolling
    this.modalEl.querySelector('.modal-body').addEventListener('scroll', 
      this.debounce(() => this.handleScroll(), 16)
    );
  }

  setupKeyboardNavigation() {
    this.modalEl.addEventListener('keydown', (e) => {
      if (!this.modal._isShown) return;
      
      switch(e.key) {
        case 'Escape':
          if (!e.target.closest('input, textarea, select')) {
            this.modal.hide();
          }
          break;
        case 'a':
        case 'A':
          if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            this.selectAll.checked = true;
            this.handleSelectAll();
          }
          break;
        case 'Delete':
        case 'Backspace':
          if (this.selected.size > 0 && !e.target.matches('input, textarea')) {
            e.preventDefault();
            this.handleDelete(e);
          }
          break;
        case 'ArrowUp':
        case 'ArrowDown':
        case 'ArrowLeft':
        case 'ArrowRight':
          this.handleArrowNavigation(e);
          break;
      }
    });
  }

  handleArrowNavigation(e) {
    const focusedElement = document.activeElement;
    const mediaWrappers = Array.from(this.grid.querySelectorAll('.media-wrapper'));
    const currentIndex = mediaWrappers.indexOf(focusedElement);
    
    if (currentIndex === -1) return;
    
    let nextIndex = currentIndex;
    const gridColumns = this.getGridColumns();
    
    switch(e.key) {
      case 'ArrowUp':
        nextIndex = Math.max(0, currentIndex - gridColumns);
        break;
      case 'ArrowDown':
        nextIndex = Math.min(mediaWrappers.length - 1, currentIndex + gridColumns);
        break;
      case 'ArrowLeft':
        nextIndex = Math.max(0, currentIndex - 1);
        break;
      case 'ArrowRight':
        nextIndex = Math.min(mediaWrappers.length - 1, currentIndex + 1);
        break;
    }
    
    if (nextIndex !== currentIndex) {
      e.preventDefault();
      mediaWrappers[nextIndex].focus();
      this.scrollIntoViewIfNeeded(mediaWrappers[nextIndex]);
    }
  }

  getGridColumns() {
    const gridStyle = window.getComputedStyle(this.grid);
    const columns = gridStyle.gridTemplateColumns.split(' ').length;
    return columns || 4;
  }

  scrollIntoViewIfNeeded(element) {
    const modalBody = this.modalEl.querySelector('.modal-body');
    const elementRect = element.getBoundingClientRect();
    const containerRect = modalBody.getBoundingClientRect();
    
    if (elementRect.top < containerRect.top || elementRect.bottom > containerRect.bottom) {
      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  setupFocusTrap() {
    const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    
    this.modalEl.addEventListener('keydown', (e) => {
      if (e.key !== 'Tab') return;
      
      const focusable = Array.from(this.modalEl.querySelectorAll(focusableElements))
        .filter(el => !el.disabled && !el.hidden && el.offsetParent !== null);
      
      if (focusable.length === 0) return;
      
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });
  }

  async openModal(trigger) {
    try {
      this.showSpinner();
      
      const items = JSON.parse(trigger.dataset.items || '[]');
      this.currentPortfolioId = trigger.getAttribute('data-portfolio-id');
      this.currentItems = items;
      this.currentIds = items.map(x => x.id);
      this.selected.clear();
      
      // Enable virtual scrolling for large galleries
      this.virtualScrolling.enabled = items.length > 100;
      
      this.modal.show();
      await this.buildGrid(items);
    } catch (error) {
      console.error('Error opening modal:', error);
      this.showError('Failed to load gallery');
    }
  }

  async buildGrid(items) {
    if (this.isLoading) return;
    
    this.isLoading = true;
    this.showSpinner();
    this.hideError();
    this.hideEmptyState();
    
    try {
      // Clear existing content
      this.grid.innerHTML = '';
      this.cleanupLightbox();
      
      if (!items || items.length === 0) {
        this.showEmptyState();
        return;
      }

      // Create PhotoSwipe container
      const pswpDiv = document.createElement('div');
      pswpDiv.className = 'pswp-gallery';
      pswpDiv.id = 'pswp-' + Date.now();
      pswpDiv.style.display = 'none';
      this.grid.before(pswpDiv);

      if (this.virtualScrolling.enabled) {
        await this.buildVirtualGrid(items, pswpDiv);
      } else {
        await this.buildRegularGrid(items, pswpDiv);
      }

      // Initialize PhotoSwipe
      this.initLightbox(pswpDiv.id);
      
      // Setup event handlers
      this.attachCheckboxEvents();
      this.syncUI();
      
    } catch (error) {
      console.error('Error building grid:', error);
      this.showError('Failed to build gallery');
    } finally {
      this.isLoading = false;
      this.hideSpinner();
    }
  }

  async buildRegularGrid(items, pswpContainer) {
    const fragment = document.createDocumentFragment();
    const promises = [];
    
    items.forEach((item, index) => {
      const promise = this.createMediaItem(item, index, pswpContainer, fragment);
      promises.push(promise);
    });
    
    await Promise.allSettled(promises);
    this.grid.appendChild(fragment);
  }

  async buildVirtualGrid(items, pswpContainer) {
    // Implement virtual scrolling for better performance with large galleries
    const modalBody = this.modalEl.querySelector('.modal-body');
    this.virtualScrolling.containerHeight = modalBody.clientHeight;
    
    // Calculate visible range
    this.updateVisibleRange();
    
    // Create only visible items
    const visibleItems = items.slice(
      this.virtualScrolling.visibleStart,
      this.virtualScrolling.visibleEnd
    );
    
    const fragment = document.createDocumentFragment();
    const promises = visibleItems.map((item, index) => 
      this.createMediaItem(item, this.virtualScrolling.visibleStart + index, pswpContainer, fragment)
    );
    
    await Promise.allSettled(promises);
    this.grid.appendChild(fragment);
    
    // Set up scroll listener for virtual scrolling
    if (this.resizeObserver) {
      this.resizeObserver.observe(modalBody);
    }
  }

  updateVisibleRange() {
    const itemsPerRow = this.getGridColumns();
    const rowHeight = this.virtualScrolling.itemHeight;
    const containerHeight = this.virtualScrolling.containerHeight;
    const scrollTop = this.virtualScrolling.scrollTop;
    
    const startRow = Math.floor(scrollTop / rowHeight);
    const endRow = Math.ceil((scrollTop + containerHeight) / rowHeight);
    
    this.virtualScrolling.visibleStart = Math.max(0, (startRow - this.virtualScrolling.buffer) * itemsPerRow);
    this.virtualScrolling.visibleEnd = Math.min(
      this.currentItems.length,
      (endRow + this.virtualScrolling.buffer) * itemsPerRow
    );
  }

  handleScroll() {
    if (!this.virtualScrolling.enabled) return;
    
    const modalBody = this.modalEl.querySelector('.modal-body');
    this.virtualScrolling.scrollTop = modalBody.scrollTop;
    
    // Update visible range and rebuild if necessary
    const oldStart = this.virtualScrolling.visibleStart;
    const oldEnd = this.virtualScrolling.visibleEnd;
    
    this.updateVisibleRange();
    
    if (oldStart !== this.virtualScrolling.visibleStart || oldEnd !== this.virtualScrolling.visibleEnd) {
      this.buildGrid(this.currentItems);
    }
  }

  handleResize() {
    if (this.virtualScrolling.enabled) {
      const modalBody = this.modalEl.querySelector('.modal-body');
      this.virtualScrolling.containerHeight = modalBody.clientHeight;
      this.updateVisibleRange();
      this.buildGrid(this.currentItems);
    }
  }

  async createMediaItem(item, index, pswpContainer, container = null) {
    return new Promise((resolve) => {
      const wrap = document.createElement('div');
      wrap.className = 'media-wrapper';
      wrap.setAttribute('role', 'gridcell');
      wrap.setAttribute('tabindex', '0');
      wrap.setAttribute('aria-label', `Media item ${index + 1} of ${this.currentItems.length}`);
      wrap.dataset.index = index;
      
      const img = document.createElement('img');
      img.loading = 'lazy';
      img.alt = `Media item ${index + 1}`;
      
      // Use intersection observer for lazy loading
      if (this.intersectionObserver) {
        img.dataset.src = item.media_url;
        this.intersectionObserver.observe(img);
      } else {
        img.src = item.media_url;
      }
      
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.className = 'form-check-input delete-check';
      checkbox.setAttribute('data-media-id', item.id);
      checkbox.setAttribute('aria-label', `Select media item ${index + 1}`);
      
      // Handle image load/error with better error handling
      const handleLoad = () => {
        img.removeAttribute('data-error');
        wrap.classList.add('loaded');
        resolve();
      };
      
      const handleError = () => {
        img.setAttribute('data-error', 'true');
        img.alt = 'Failed to load image';
        wrap.classList.add('error');
        console.warn('Failed to load image:', item.media_url);
        
        // Create error placeholder
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-placeholder';
        errorDiv.innerHTML = '<i class="bi bi-image"></i><span>Failed to load</span>';
        wrap.appendChild(errorDiv);
        
        resolve();
      };
      
      img.addEventListener('load', handleLoad, { once: true });
      img.addEventListener('error', handleError, { once: true });
      
      wrap.appendChild(img);
      wrap.appendChild(checkbox);
      
      if (container) {
        container.appendChild(wrap);
      } else {
        this.grid.appendChild(wrap);
      }

      // Setup PhotoSwipe for images
      if (item.media_url.match(/\.(jpe?g|png|gif|webp)$/i)) {
        const a = document.createElement('a');
        a.href = item.media_url;
        a.setAttribute('data-pswp-width', '1600');
        a.setAttribute('data-pswp-height', '1200');
        pswpContainer.appendChild(a);
        
        const openLightbox = () => {
          if (this.lightbox) {
            this.lightbox.loadAndOpen(index);
          }
        };
        
        img.addEventListener('click', openLightbox);
        img.style.cursor = 'zoom-in';
        
        // Enhanced keyboard support
        wrap.addEventListener('keydown', (e) => {
          switch(e.key) {
            case 'Enter':
              e.preventDefault();
              openLightbox();
              break;
            case ' ':
              e.preventDefault();
              checkbox.checked = !checkbox.checked;
              checkbox.dispatchEvent(new Event('change', { bubbles: true }));
              break;
          }
        });
      }
    });
  }

  initLightbox(galleryId) {
    this.cleanupLightbox();
    
    try {
      this.lightbox = new PhotoSwipeLightbox({
        gallery: '#' + galleryId,
        children: 'a',
        pswpModule: PhotoSwipe,
        wheelToZoom: true,
        arrowKeys: true,
        padding: { top: 40, bottom: 40, left: 40, right: 40 },
        bgOpacity: 1,
        showHideAnimationType: 'zoom',
        preload: [1, 2] // Preload next/prev images
      });
      
      // Enhanced lightbox features
      this.lightbox.on('uiRegister', () => {
        // Close on content click
        this.lightbox.pswp.ui.registerElement({
          name: 'close-on-click',
          onInit: (el, pswp) => {
            pswp.on('contentClickAction', () => {
              pswp.close();
              return false;
            });
          }
        });
        
        // Add download button
        this.lightbox.pswp.ui.registerElement({
          name: 'download-button',
          order: 8,
          isButton: true,
          html: '<i class="bi bi-download"></i>',
          onInit: (el, pswp) => {
            el.setAttribute('title', 'Download image');
            el.onclick = () => {
              const link = document.createElement('a');
              link.href = pswp.currSlide.data.src;
              link.download = pswp.currSlide.data.src.split('/').pop();
              link.click();
            };
          }
        });
      });
      
      this.lightbox.init();
    } catch (error) {
      console.error('Failed to initialize lightbox:', error);
    }
  }

  attachCheckboxEvents() {
    this.selected.clear();
    
    // Use event delegation for better performance
    this.grid.addEventListener('change', (e) => {
      if (e.target.classList.contains('delete-check')) {
        const id = +e.target.dataset.mediaId;
        if (e.target.checked) {
          this.selected.add(id);
        } else {
          this.selected.delete(id);
        }
        this.syncUI();
      }
    });
    
    this.syncUI();
  }

  handleSelectAll() {
    const isChecked = this.selectAll.checked;
    this.selected.clear();
    
    this.grid.querySelectorAll('.delete-check').forEach(cb => {
      cb.checked = isChecked;
      if (isChecked) {
        this.selected.add(+cb.dataset.mediaId);
      }
    });
    
    this.syncUI();
    
    // Announce to screen readers
    const message = isChecked ? 
      `Selected all ${this.selected.size} items` : 
      'Deselected all items';
    this.announceToScreenReader(message);
  }

  syncUI() {
    // Update selection state
    this.selected.clear();
    const boxes = this.grid.querySelectorAll('.delete-check');
    boxes.forEach(cb => {
      if (cb.checked) this.selected.add(+cb.dataset.mediaId);
    });
    
    // Update UI elements
    const total = boxes.length;
    const checked = this.selected.size;
    
    this.delBtn.disabled = checked === 0;
    this.delBtn.classList.toggle('active', checked > 0);
    
    this.selectAll.indeterminate = checked > 0 && checked < total;
    this.selectAll.checked = checked === total && total > 0;
    
    // Update selection counter with better formatting
    if (checked === 0) {
      this.selectionCount.textContent = '';
    } else {
      const percentage = Math.round((checked / total) * 100);
      this.selectionCount.textContent = `${checked} of ${total} selected (${percentage}%)`;
    }
    
    // Update delete button text
    if (checked > 0) {
      this.delBtn.innerHTML = `<i class="bi bi-trash"></i> Delete ${checked} item${checked > 1 ? 's' : ''}`;
    } else {
      this.delBtn.innerHTML = '<i class="bi bi-trash"></i> Delete Selected';
    }
  }

  async handleDelete(e) {
    e.preventDefault();
    
    if (this.selected.size === 0) return;
    
    const count = this.selected.size;
    const message = count === 1 ? 
      'Delete this media item?' : 
      `Delete ${count} media items?`;
    
    if (!confirm(message)) return;
    
    this.showSpinner();
    const idsToDelete = Array.from(this.selected);
    
    try {
      const response = await fetch('api/delete_media.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ media_ids: idsToDelete })
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (!result.success) {
        throw new Error(result.message || 'Delete operation failed');
      }
      
      // Animate removal of deleted items
      const removePromises = idsToDelete.map(id => {
        return new Promise(resolve => {
          const cb = this.grid.querySelector(`.delete-check[data-media-id="${id}"]`);
          if (cb) {
            const wrap = cb.closest('.media-wrapper');
            if (wrap) {
              wrap.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
              wrap.style.opacity = '0';
              wrap.style.transform = 'scale(0.8)';
              setTimeout(() => {
                wrap.remove();
                resolve();
              }, 300);
            } else {
              resolve();
            }
          } else {
            resolve();
          }
        });
      });
      
      await Promise.all(removePromises);
      
      // Update state
      this.currentIds = this.currentIds.filter(id => !idsToDelete.includes(id));
      this.currentItems = this.currentItems.filter(item => !idsToDelete.includes(item.id));
      this.selected.clear();
      
      // Update dashboard
      this.updateDashboard(idsToDelete);
      
      // Check if empty
      if (this.grid.querySelectorAll('.media-wrapper').length === 0) {
        this.modal.hide();
        this.showToast('Info', 'All media deleted.');
      } else {
        this.syncUI();
      }
      
      this.showToast('Success', `${count} item${count > 1 ? 's' : ''} deleted successfully`);
      this.announceToScreenReader(`${count} item${count > 1 ? 's' : ''} deleted`);
      
    } catch (error) {
      console.error('Delete failed:', error);
      this.showToast('Error', error.message || 'Delete failed', false);
    } finally {
      this.hideSpinner();
    }
  }

  updateDashboard(deletedIds) {
    if (!this.currentPortfolioId) return;
    
    const dashCard = document.querySelector(`#item-${this.currentPortfolioId}`);
    if (!dashCard) return;
    
    const plusN = dashCard.querySelector('[data-bs-toggle="modal"][data-bs-target="#galleryModal"]');
    if (!plusN) return;
    
    try {
      let items = JSON.parse(plusN.getAttribute('data-items') || '[]');
      items = items.filter(m => !deletedIds.includes(m.id));
      plusN.setAttribute('data-items', JSON.stringify(items));
      
      const n = items.length - 8;
      if (n > 0) {
        plusN.textContent = '+' + n;
        plusN.style.display = '';
      } else {
        plusN.style.display = 'none';
      }
      
      // Update dashboard thumbnails with animation
      const previewContainer = dashCard.querySelector('.media-preview-container');
      if (previewContainer) {
        // Remove old thumbnails with animation
        const oldWrappers = previewContainer.querySelectorAll('.media-wrapper');
        oldWrappers.forEach(el => {
          el.style.transition = 'opacity 0.2s ease';
          el.style.opacity = '0';
          setTimeout(() => el.remove(), 200);
        });
        
        // Add new thumbnails
        setTimeout(() => {
          items.slice(0, 8).forEach((m, index) => {
            const wrap = document.createElement('div');
            wrap.className = 'media-wrapper position-relative';
            wrap.style.opacity = '0';
            wrap.innerHTML = `
              <img src="${m.media_url}" class="media-preview">
              <input type="checkbox" class="form-check-input delete-check" data-media-id="${m.id}">
            `;
            previewContainer.appendChild(wrap);
            
            // Animate in
            setTimeout(() => {
              wrap.style.transition = 'opacity 0.2s ease';
              wrap.style.opacity = '1';
            }, index * 50);
          });
        }, 250);
      }
    } catch (error) {
      console.error('Failed to update dashboard:', error);
    }
  }

  refreshGallery() {
    if (this.currentItems.length > 0) {
      this.buildGrid(this.currentItems);
      this.announceToScreenReader('Gallery refreshed');
    }
  }

  retryLoad() {
    this.refreshGallery();
  }

  showSpinner() {
    this.spinner.classList.remove('d-none');
    this.spinner.setAttribute('aria-hidden', 'false');
  }

  hideSpinner() {
    this.spinner.classList.add('d-none');
    this.spinner.setAttribute('aria-hidden', 'true');
  }

  showError(message) {
    this.errorMessage.querySelector('.error-text').textContent = message;
    this.errorMessage.classList.remove('d-none');
    this.announceToScreenReader(`Error: ${message}`);
  }

  hideError() {
    this.errorMessage.classList.add('d-none');
  }

  showEmptyState() {
    this.emptyState.classList.remove('d-none');
    this.announceToScreenReader('No media items found');
  }

  hideEmptyState() {
    this.emptyState.classList.add('d-none');
  }

  onModalShown() {
    // Focus first focusable element
    const firstFocusable = this.modalEl.querySelector('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) {
      firstFocusable.focus();
    }
    
    this.announceToScreenReader(`Gallery modal opened with ${this.currentItems.length} items`);
  }

  cleanupLightbox() {
    if (this.lightbox) {
      try {
        this.lightbox.destroy();
      } catch (error) {
        console.warn('Error destroying lightbox:', error);
      }
      this.lightbox = null;
    }
    
    // Remove old PhotoSwipe containers
    this.modalEl.querySelectorAll('.pswp-gallery').forEach(el => el.remove());
  }

  cleanup() {
    this.grid.innerHTML = '';
    this.selected.clear();
    this.currentItems = [];
    this.currentIds = [];
    this.currentPortfolioId = null;
    this.isLoading = false;
    this.virtualScrolling.enabled = false;
    
    // Clear debounce timers
    this.debounceTimers.forEach(timer => clearTimeout(timer));
    this.debounceTimers.clear();
    
    // Disconnect observers
    if (this.intersectionObserver) {
      this.intersectionObserver.disconnect();
    }
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
    
    this.hideSpinner();
    this.hideError();
    this.hideEmptyState();
    this.cleanupLightbox();
    this.syncUI();
  }

  // Utility methods
  showToast(title, message, isSuccess = true) {
    if (typeof toast === 'function') {
      toast(title, message, isSuccess);
    } else {
      console.log(`${title}: ${message}`);
    }
  }

  announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    
    setTimeout(() => {
      document.body.removeChild(announcement);
    }, 1000);
  }

  // Public API methods
  open(portfolioId, items) {
    const trigger = {
      dataset: { items: JSON.stringify(items) },
      getAttribute: () => portfolioId
    };
    this.openModal(trigger);
  }

  close() {
    this.modal.hide();
  }

  selectItems(itemIds) {
    itemIds.forEach(id => this.selected.add(id));
    this.grid.querySelectorAll('.delete-check').forEach(cb => {
      cb.checked = this.selected.has(+cb.dataset.mediaId);
    });
    this.syncUI();
  }

  getSelectedItems() {
    return Array.from(this.selected);
  }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = EnhancedGalleryModal;
} else if (typeof window !== 'undefined') {
  window.EnhancedGalleryModal = EnhancedGalleryModal;
} 