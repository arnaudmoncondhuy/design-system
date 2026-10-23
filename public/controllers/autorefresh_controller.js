import { Controller } from '@hotwired/stimulus';

/*
 * Un cadre Turbo qui se recharge seul.
 *
 * Posé sur un `<turbo-frame>`. Un cadre sans `src` se recharge depuis l'adresse de la page :
 * Turbo n'en garde que le cadre de même identifiant.
 *
 * Le rechargement attend quand il ferait perdre quelque chose :
 * - l'onglet est caché — personne ne regarde, et le cadre se recharge dès qu'il redevient
 *   visible plutôt qu'au tour suivant ;
 * - une fenêtre est ouverte — un message qu'on écoute ne doit pas être interrompu ;
 * - un champ du cadre a le focus — ce qu'on y tape serait effacé ;
 * - le cadre est déjà en train de charger.
 */
export default class extends Controller {
    static values = { interval: { type: Number, default: 15000 } };

    connect() {
        this.timer = setInterval(() => this.refresh(), this.intervalValue);

        this.onVisible = () => {
            if (!document.hidden) {
                this.refresh();
            }
        };

        document.addEventListener('visibilitychange', this.onVisible);
    }

    disconnect() {
        clearInterval(this.timer);
        document.removeEventListener('visibilitychange', this.onVisible);
    }

    refresh() {
        if (document.hidden || document.querySelector('dialog[open]') || this.editing() || this.element.hasAttribute('busy')) {
            return;
        }

        if (this.element.src) {
            this.element.reload();
        } else {
            this.element.src = window.location.href;
        }
    }

    editing() {
        const focused = document.activeElement;

        return this.element.contains(focused) && focused.matches('input, select, textarea, [contenteditable]');
    }
}
