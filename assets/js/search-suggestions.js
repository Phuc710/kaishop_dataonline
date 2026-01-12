/**
 * Search Suggestions JavaScript
 * Handles autocomplete functionality for search input
 */

class SearchSuggestions {
    constructor(inputSelector, containerSelector, options = {}) {
        this.input = document.querySelector(inputSelector);
        this.container = document.querySelector(containerSelector);
        this.options = {
            minLength: 2,
            delay: 300,
            maxResults: 10,
            apiUrl: '/api/search-suggestions.php',
            ...options
        };
        
        this.currentQuery = '';
        this.debounceTimer = null;
        this.selectedIndex = -1;
        this.suggestions = [];
        
        this.init();
    }
    
    init() {
        if (!this.input || !this.container) {
            console.error('Search input or container not found');
            return;
        }
        
        this.setupEventListeners();
        this.createSuggestionsContainer();
    }
    
    setupEventListeners() {
        // Input events
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.input.addEventListener('focus', (e) => this.handleFocus(e));
        this.input.addEventListener('blur', (e) => this.handleBlur(e));
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target) && e.target !== this.input) {
                this.hideSuggestions();
            }
        });
    }
    
    createSuggestionsContainer() {
        this.suggestionsEl = document.createElement('div');
        this.suggestionsEl.className = 'search-suggestions';
        this.suggestionsEl.style.display = 'none';
        this.container.appendChild(this.suggestionsEl);
    }
    
    handleInput(e) {
        const query = e.target.value.trim();
        
        if (query.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }
        
        if (query === this.currentQuery) {
            return;
        }
        
        this.currentQuery = query;
        
        // Debounce API calls
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(query);
        }, this.options.delay);
    }
    
    handleKeydown(e) {
        if (!this.suggestionsEl || this.suggestionsEl.style.display === 'none') {
            return;
        }
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.navigateDown();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.navigateUp();
                break;
            case 'Enter':
                e.preventDefault();
                this.selectCurrent();
                break;
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    handleFocus(e) {
        if (this.suggestions.length > 0 && this.currentQuery.length >= this.options.minLength) {
            this.showSuggestions();
        }
    }
    
    handleBlur(e) {
        // Delay hiding to allow clicking on suggestions
        setTimeout(() => {
            this.hideSuggestions();
        }, 150);
    }
    
    async fetchSuggestions(query) {
        try {
            const response = await fetch(`${this.options.apiUrl}?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.success) {
                this.suggestions = data.suggestions;
                this.renderSuggestions();
            } else {
                console.error('Search API error:', data.message);
            }
        } catch (error) {
            console.error('Search request failed:', error);
        }
    }
    
    renderSuggestions() {
        if (this.suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = this.suggestions.map((suggestion, index) => {
            return this.renderSuggestionItem(suggestion, index);
        }).join('');
        
        this.suggestionsEl.innerHTML = html;
        this.showSuggestions();
        this.selectedIndex = -1;
    }
    
    renderSuggestionItem(suggestion, index) {
        const currency = getCookie('currency') || 'VND';
        let priceHtml = '';
        
        if (suggestion.type === 'product' && suggestion.price_vnd) {
            if (currency === 'USD' && suggestion.price_usd) {
                priceHtml = `<span class="suggestion-price">$${parseFloat(suggestion.price_usd).toFixed(2)}</span>`;
            } else {
                priceHtml = `<span class="suggestion-price">${parseInt(suggestion.price_vnd).toLocaleString()}đ</span>`;
            }
        }
        
        const imageHtml = suggestion.image 
            ? `<img src="${suggestion.image}" alt="${suggestion.title}" class="suggestion-image">`
            : `<div class="suggestion-icon"><i class="fas fa-${this.getTypeIcon(suggestion.type)}"></i></div>`;
        
        return `
            <div class="suggestion-item" data-index="${index}" data-url="${suggestion.url || '#'}">
                ${imageHtml}
                <div class="suggestion-content">
                    <div class="suggestion-title">${this.highlightQuery(suggestion.title)}</div>
                    <div class="suggestion-subtitle">${suggestion.subtitle}</div>
                </div>
                ${priceHtml}
            </div>
        `;
    }
    
    getTypeIcon(type) {
        const icons = {
            'product': 'box',
            'category': 'folder',
            'popular': 'fire'
        };
        return icons[type] || 'search';
    }
    
    highlightQuery(text) {
        if (!this.currentQuery) return text;
        
        const regex = new RegExp(`(${this.escapeRegex(this.currentQuery)})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    showSuggestions() {
        this.suggestionsEl.style.display = 'block';
        this.setupSuggestionClickHandlers();
    }
    
    hideSuggestions() {
        this.suggestionsEl.style.display = 'none';
        this.selectedIndex = -1;
    }
    
    setupSuggestionClickHandlers() {
        const items = this.suggestionsEl.querySelectorAll('.suggestion-item');
        items.forEach((item, index) => {
            item.addEventListener('click', () => {
                this.selectSuggestion(index);
            });
        });
    }
    
    navigateDown() {
        const items = this.suggestionsEl.querySelectorAll('.suggestion-item');
        if (items.length === 0) return;
        
        this.selectedIndex = (this.selectedIndex + 1) % items.length;
        this.updateSelection();
    }
    
    navigateUp() {
        const items = this.suggestionsEl.querySelectorAll('.suggestion-item');
        if (items.length === 0) return;
        
        this.selectedIndex = this.selectedIndex <= 0 ? items.length - 1 : this.selectedIndex - 1;
        this.updateSelection();
    }
    
    updateSelection() {
        const items = this.suggestionsEl.querySelectorAll('.suggestion-item');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });
    }
    
    selectCurrent() {
        if (this.selectedIndex >= 0) {
            this.selectSuggestion(this.selectedIndex);
        }
    }
    
    selectSuggestion(index) {
        const suggestion = this.suggestions[index];
        if (!suggestion) return;
        
        this.input.value = suggestion.title;
        this.hideSuggestions();
        
        // Navigate to URL if available
        if (suggestion.url) {
            window.location.href = suggestion.url;
        }
    }
}

// Utility function to get cookie value
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search suggestions if search input exists
    const searchInput = document.querySelector('#search-input, .search-input, input[name="search"]');
    if (searchInput) {
        const container = searchInput.closest('.search-container') || searchInput.parentElement;
        new SearchSuggestions('#' + searchInput.id || '.search-input', container);
    }
});
