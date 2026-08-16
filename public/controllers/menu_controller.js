import { Controller } from '@hotwired/stimulus';

/*
 * Ce qu'un <details> employé en menu ne fait pas de lui-même.
 *
 * L'élément porte l'ouverture, la fermeture et l'annonce de son état : rien de tout cela n'est
 * réécrit ici. Restent les deux gestes qu'un menu déroulant doit à qui l'ouvre — la touche
 * Échap, qui rend le focus au bouton, et le clic ailleurs sur la page. Sans ce comportement, le
 * menu s'ouvre et se ferme quand même, par son bouton.
 */
export default class extends Controller {
    connect() {
        this.onKeydown = (event) => {
            if (event.key === 'Escape' && this.element.open) {
                this.element.open = false;
                this.element.querySelector('summary').focus();
            }
        };

        this.onPointerdown = (event) => {
            if (this.element.open && !this.element.contains(event.target)) {
                this.element.open = false;
            }
        };

        document.addEventListener('keydown', this.onKeydown);
        document.addEventListener('pointerdown', this.onPointerdown);
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKeydown);
        document.removeEventListener('pointerdown', this.onPointerdown);
    }
}
