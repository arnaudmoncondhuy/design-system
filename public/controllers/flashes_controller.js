import { Controller } from '@hotwired/stimulus';

/*
 * Les messages éphémères, posés par-dessus la page.
 *
 * Sans script, ils restent en tête du contenu, dans le flux : lisibles, et sans rien qui recouvre
 * la page. Le comportement les fait flotter dans un coin, par `data-floating` que la feuille
 * dessine, et découvre le bouton qui les ferme — caché dans le gabarit, puisqu'il ne ferait rien
 * sans lui.
 *
 * Un message qui dit que tout s'est bien passé se retire seul. Un avertissement ou un échec reste
 * jusqu'à ce qu'on le ferme : il demande peut-être d'agir, et doit pouvoir se relire. Le délai
 * s'interrompt tant que le pointeur ou le focus est sur les messages, et repart en entier quand
 * les deux les ont quittés : on ne retire pas une phrase pendant qu'on la lit.
 *
 * Les messages ne partent pas dans la copie que Turbo garde de la page : revenue par le bouton
 * Précédent, elle les afficherait de nouveau, comme si ce qu'ils annoncent venait de se produire.
 */
export default class extends Controller {
    static targets = ['message', 'close'];

    static values = { delay: { type: Number, default: 6000 } };

    static LASTING = ['danger', 'warning'];

    connect() {
        this.element.dataset.floating = '';
        this.holds = new Set();

        this.onPointerEnter = () => this.pause('pointer');
        this.onPointerLeave = () => this.resume('pointer');
        this.onFocusIn = () => this.pause('focus');
        this.onFocusOut = (event) => {
            if (!this.element.contains(event.relatedTarget)) {
                this.resume('focus');
            }
        };

        this.onBeforeCache = () => this.element.remove();

        this.element.addEventListener('pointerenter', this.onPointerEnter);
        this.element.addEventListener('pointerleave', this.onPointerLeave);
        this.element.addEventListener('focusin', this.onFocusIn);
        this.element.addEventListener('focusout', this.onFocusOut);
        document.addEventListener('turbo:before-cache', this.onBeforeCache);

        // Un message qui paraît sous un pointeur immobile ne reçoit pas d'entrée.
        if (this.element.matches(':hover')) {
            this.pause('pointer');
        } else {
            this.schedule();
        }
    }

    disconnect() {
        clearTimeout(this.timer);
        this.element.removeEventListener('pointerenter', this.onPointerEnter);
        this.element.removeEventListener('pointerleave', this.onPointerLeave);
        this.element.removeEventListener('focusin', this.onFocusIn);
        this.element.removeEventListener('focusout', this.onFocusOut);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache);
    }

    closeTargetConnected(button) {
        button.hidden = false;
    }

    close(event) {
        this.messageTargets.find((message) => message.contains(event.currentTarget))?.remove();

        // Le message retiré emporte le focus sans que le navigateur signale toujours son départ.
        if (!this.element.contains(document.activeElement)) {
            this.resume('focus');
        }
    }

    pause(reason) {
        this.holds.add(reason);
        clearTimeout(this.timer);
    }

    resume(reason) {
        this.holds.delete(reason);

        if (this.holds.size === 0) {
            this.schedule();
        }
    }

    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            for (const message of this.messageTargets) {
                if (!this.constructor.LASTING.includes(message.dataset.tone)) {
                    message.remove();
                }
            }
        }, this.delayValue);
    }
}
