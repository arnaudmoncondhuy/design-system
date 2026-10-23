import { Controller } from '@hotwired/stimulus';

/*
 * Un formulaire qui s'envoie dès qu'une valeur change.
 *
 * Le comportement se pose sur le formulaire, et c'est le champ qui dit quand partir :
 * `data-action="change->autosubmit#submit"`. Un formulaire peut ainsi mêler une liste qui
 * s'applique d'elle-même et un champ texte qu'on valide d'un bouton.
 *
 * `requestSubmit()` passe par la validation du navigateur et par l'événement `submit`, donc par
 * Turbo et par tout comportement qui l'écoute — ce que `submit()` sauterait.
 */
export default class extends Controller {
    submit() {
        this.element.requestSubmit();
    }
}
