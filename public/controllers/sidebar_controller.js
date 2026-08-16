import { Controller } from '@hotwired/stimulus';

/*
 * Fait du menu latéral un tiroir là où il n'y a pas de côté.
 *
 * Le panneau est un `<dialog>` : `showModal()` apporte la couche supérieure, le voile, le piège
 * au clavier et la fermeture par Échap. Rien de tout cela n'est réécrit ici — ce comportement
 * ne fait que dire quand le menu est un tiroir, et l'ouvrir.
 *
 * Hors tiroir, le dialogue reste fermé et c'est la feuille de style qui l'affiche : une colonne
 * ordinaire, que rien n'annonce comme une fenêtre. Sans script, aucun `data-drawer` n'est posé
 * et le panneau s'affiche partout — visible et utilisable, ce qui est le pire cas acceptable.
 *
 * Le seuil est celui où la charpente rend sa seconde colonne.
 */
export default class extends Controller {
    static targets = ['toggle', 'panel'];

    static values = {
        wide: { type: String, default: '(min-width: 60em)' },
    };

    connect() {
        this.wide = window.matchMedia(this.wideValue);
        this.follow = () => this.adapt(this.wide.matches);
        this.announce = () => this.setExpanded(this.panelTarget.open);

        /*
         * Un dialogue modal se ferme sur Échap, jamais sur un clic au-dehors : ce geste-là est à
         * écrire. Le voile étant peint par le dialogue lui-même, un clic dessus lui parvient —
         * ce sont donc les coordonnées, et non la cible, qui disent s'il est tombé dedans.
         */
        this.dismiss = (event) => {
            const boite = this.panelTarget.getBoundingClientRect();
            const dedans = event.clientX >= boite.left && event.clientX <= boite.right
                && event.clientY >= boite.top && event.clientY <= boite.bottom;

            if (!dedans) {
                this.panelTarget.close();
            }
        };

        this.wide.addEventListener('change', this.follow);
        this.panelTarget.addEventListener('close', this.announce);
        this.panelTarget.addEventListener('click', this.dismiss);
        this.follow();
    }

    disconnect() {
        this.wide.removeEventListener('change', this.follow);
        this.panelTarget.removeEventListener('close', this.announce);
        this.panelTarget.removeEventListener('click', this.dismiss);
    }

    toggle() {
        if (this.panelTarget.open) {
            this.panelTarget.close();
        } else {
            this.panelTarget.showModal();
        }

        this.announce();
    }

    /** Tiroir sur un écran étroit, colonne ordinaire dès qu'il y a la place. */
    adapt(wide) {
        if (this.panelTarget.open) {
            this.panelTarget.close();
        }

        this.element.toggleAttribute('data-drawer', !wide);
        this.setExpanded(wide);
    }

    /** L'attribut porte l'annonce ; l'état, lui, appartient au dialogue. */
    setExpanded(expanded) {
        if (this.hasToggleTarget) {
            this.toggleTarget.setAttribute('aria-expanded', String(expanded));
        }
    }
}
