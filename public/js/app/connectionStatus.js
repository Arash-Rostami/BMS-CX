class StatusSubject {
    constructor() {
        this.observers = [];
        this.status = '';
    }

    addObserver(observer) {
        this.observers.push(observer);
    }

    setStatus(status) {
        this.status = status;
        this.notify();
    }

    notify() {
        this.observers.forEach(observer => observer.update(this.status));
    }
}

class StatusObserver {
    constructor() {
        this.statusDiv = document.createElement('div');
        this.hideTimeout = null;

        this.config = {
            online: {
                color: 'rgb(21, 128, 61)',
                text: 'back online',
                icon: '<svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>'
            },
            offline: {
                color: 'rgb(220, 38, 38)',
                text: 'gone offline',
                icon: '<svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>'
            }
        };

        this.applyBaseStyles();
        document.body.appendChild(this.statusDiv);
    }

    applyBaseStyles() {
        Object.assign(this.statusDiv.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            zIndex: '500',
            padding: '16px 20px',
            color: '#ffffff',
            fontFamily: 'system-ui, sans-serif',
            fontWeight: '500',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '8px',
            pointerEvents: 'none',
            transform: 'translateY(-100%)',
            opacity: '0',
            transition: 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease'
        });
    }

    showDivStatus(state) {
        clearTimeout(this.hideTimeout);

        this.statusDiv.innerHTML = `${state.icon} <span>${state.text}</span>`;
        this.statusDiv.style.backgroundColor = state.color;

        this.statusDiv.style.transform = 'translateY(0)';
        this.statusDiv.style.opacity = '1';
    }

    hideDivStatus(delay) {
        this.hideTimeout = setTimeout(() => {
            this.statusDiv.style.transform = 'translateY(-100%)';
            this.statusDiv.style.opacity = '0';
        }, delay);
    }

    update(status) {
        const state = this.config[status];
        if (!state) return;

        this.showDivStatus(state);

        if (status === 'online') {
            this.hideDivStatus(4000);
        }
    }
}

const statusSubject = new StatusSubject();
const statusObserver = new StatusObserver();
statusSubject.addObserver(statusObserver);

window.addEventListener('online', () => statusSubject.setStatus('online'));
window.addEventListener('offline', () => statusSubject.setStatus('offline'));
