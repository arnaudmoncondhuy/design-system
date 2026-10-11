import { Controller } from '@hotwired/stimulus';

/*
 * Ouvre et ferme un <dialog>.
 *
 * `showModal()` apporte la pile d'affichage, le fond assombri, le piège au clavier et la
 * fermeture par Échap. Ce qu'il n'apporte pas, c'est la fermeture par un clic sur le fond :
 * le voile étant peint par le dialogue lui-même, un clic dessus lui parvient. Ce sont donc les
 * coordonnées du clic, et non sa cible, qui disent s'il est tombé dedans.
 */
export default class extends Controller {
    static targets = ['dialog'];

    initialize() {
        this.dismiss = (event) => {
            const dialog = event.currentTarget;
            const boite = dialog.getBoundingClientRect();
            const dedans = event.clientX >= boite.left && event.clientX <= boite.right
                && event.clientY >= boite.top && event.clientY <= boite.bottom;

            if (!dedans) {
                dialog.close();
            }
        };
    }

    dialogTargetConnected(dialog) {
        dialog.addEventListener('click', this.dismiss);
    }

    dialogTargetDisconnected(dialog) {
        dialog.removeEventListener('click', this.dismiss);
    }

    open() {
        this.dialogTarget.showModal();
    }

    close() {
        this.dialogTarget.close();
    }
}
