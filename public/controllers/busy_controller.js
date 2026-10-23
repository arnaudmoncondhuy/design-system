import { Controller } from '@hotwired/stimulus';

/*
 * Le bouton qui a envoyé un formulaire tourne jusqu'à la réponse.
 *
 * Posé sur un formulaire, ou sur ce qui en contient plusieurs : les événements de Turbo montent
 * jusqu'à lui. `aria-busy` est ce que la feuille dessine et ce que le lecteur d'écran annonce ;
 * rien d'autre n'est écrit ici. Le bouton ne reçoit pas `disabled` : un bouton désactivé n'est
 * plus transmis avec le formulaire, et le serveur perdrait la valeur qu'il portait.
 *
 * Seuls les envois que Turbo conduit sont suivis — ce sont les seuls dont on sait quand ils
 * finissent. Un formulaire qui télécharge un fichier, sous `data-turbo="false"`, laisserait sinon
 * son bouton tourner pour toujours. Un envoi annulé avant de partir, par une confirmation
 * refusée ou un champ invalide, ne déclenche rien.
 */
export default class extends Controller {
    connect() {
        this.start = (event) => {
            event.detail.formSubmission?.submitter?.setAttribute('aria-busy', 'true');
        };

        this.end = (event) => {
            event.detail.formSubmission?.submitter?.removeAttribute('aria-busy');
        };

        this.element.addEventListener('turbo:submit-start', this.start);
        this.element.addEventListener('turbo:submit-end', this.end);
    }

    disconnect() {
        this.element.removeEventListener('turbo:submit-start', this.start);
        this.element.removeEventListener('turbo:submit-end', this.end);
    }
}
