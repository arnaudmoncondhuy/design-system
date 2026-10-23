import { Controller } from '@hotwired/stimulus';

/*
 * Une question avant une action qui ne se rattrape pas.
 *
 * Posé sur un formulaire (`submit->confirm#ask`) ou sur un lien (`click->confirm#ask`). La
 * question vient de l'écran, et nomme ce qu'elle concerne : « Supprimer ceci ? » ne dit pas ce
 * qu'on est en train de supprimer.
 *
 * Elle évite le clic de trop, et rien de plus : ce qui protège vraiment se tient côté serveur.
 */
export default class extends Controller {
    static values = { question: String };

    ask(event) {
        if (!window.confirm(this.questionValue)) {
            event.preventDefault();
        }
    }
}
