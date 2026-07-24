function toastManager() {
    return {
        toasts: [],
        counter: 0,

        add(data) {
            const id = ++this.counter;
            const toast = {
                id,
                message: data.message,
                type: data.type || 'info',
                visible: false
            };

            this.toasts.push(toast);

            // Mostrar con pequeño delay para animación
            setTimeout(() => {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.toasts[index].visible = true;
                }
            }, 100);

            // Auto-remover después del duration
            const duration = data.duration || 3000;
            if (data.type !== 'loading') {
                setTimeout(() => this.remove(id), duration);
            }
        },

        remove(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                this.toasts[index].visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300);
            }
        }
    };
}

// Helper global para mostrar notificaciones
window.notify = {
    success: (message, duration = 3000) => {
        window.dispatchEvent(new CustomEvent('notify', { 
            detail: { message, type: 'success', duration } 
        }));
    },
    error: (message, duration = 3000) => {
        window.dispatchEvent(new CustomEvent('notify', { 
            detail: { message, type: 'error', duration } 
        }));
    },
    warning: (message, duration = 3000) => {
        window.dispatchEvent(new CustomEvent('notify', { 
            detail: { message, type: 'warning', duration } 
        }));
    },
    info: (message, duration = 3000) => {
        window.dispatchEvent(new CustomEvent('notify', { 
            detail: { message, type: 'info', duration } 
        }));
    },
    loading: (message) => {
        window.dispatchEvent(new CustomEvent('notify', { 
            detail: { message, type: 'loading', duration: 0 } 
        }));
    }
};