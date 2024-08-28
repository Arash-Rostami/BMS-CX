// defining observer
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
        this.observers.forEach((observer) => {
            observer.update(this.status);
        });
    }
}

//defining logic of what happens when user gets online or offline
class StatusObserver {
    constructor() {
        this.statusDiv = document.createElement('div');
        this.applyBaseStyles();
        document.body.appendChild(this.statusDiv);
    }

    applyBaseStyles() {
        this.statusDiv.style.padding = '20px';
        this.statusDiv.style.textAlign = 'center';
        this.statusDiv.style.position = 'fixed';
        this.statusDiv.style.width = '100%';
        this.statusDiv.style.zIndex = '500';
        this.statusDiv.style.top = '0';
        this.statusDiv.style.left = '0';
        this.statusDiv.style.height = '0px';
        this.statusDiv.style.transition = 'all 1s ease-in-out';
        this.statusDiv.style.opacity = '0'; // Initial opacity
    }

    changeColor(color) {
        const colors = {
            'red': 'rgb(255, 0, 0)',
            'green': 'rgb(0, 128, 0)'
        };
        this.statusDiv.style.backgroundColor = colors[color];
    }

    hideDivStatus(delay) {
        setTimeout(() => {
            this.statusDiv.style.height = '0';
            setTimeout(() => {
                this.statusDiv.style.opacity = '0';
                this.statusDiv.innerHTML = '';
            }, 250);
        }, delay);
    }

    raiseOpacity() {
        for (let opacity = 0; opacity < 1.1; opacity += 0.1) {
            setTimeout(() => {
                this.statusDiv.style.opacity = opacity.toString();
            }, 100 * (10 * opacity)); // Adjust timing to progressively increase opacity
        }
    }

    showDivStatus() {
        this.statusDiv.style.height = '70px';
        this.statusDiv.style.opacity = '1';
    }

    update(status) {
        let text = {
            'online': ['<svg style="width:20px; margin:auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">\n' +
            '  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />\n' +
            '</svg>', 'back online'],
            'offline': ['<svg style="width:20px; margin:auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">\n' +
            '  <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />\n' +
            '</svg>', 'gone offline'],
        }[status];

        this.statusDiv.innerHTML = `${text[0]} ${text[1]}`;
        this.changeColor(status === 'online' ? 'green' : 'red');

        this.showDivStatus();
        this.raiseOpacity();
        if (status === 'online') {
            this.hideDivStatus(5000);
        }
    }
}

const statusSubject = new StatusSubject();
const statusObserver = new StatusObserver();
statusSubject.addObserver(statusObserver);

window.addEventListener('online', () => {
    statusSubject.setStatus('online');
});

window.addEventListener('offline', () => {
    statusSubject.setStatus('offline');
});
