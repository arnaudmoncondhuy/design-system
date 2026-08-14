import { Controller } from '@hotwired/stimulus';

/*
 * Ouvre et ferme un <dialog>.
 *
 * `showModal()` apporte la pile d'affichage, le fond assombri, le piège au clavier et la
 * fermeture par Échap : il n'y a rien à réécrire ici.
 */
export default class extends Controller {
    static targets = ['dialog'];

    open() {
        this.dialogTarget.showModal();
    }

    close() {
        this.dialogTarget.close();
    }
}
