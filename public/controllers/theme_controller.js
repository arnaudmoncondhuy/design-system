import { Controller } from '@hotwired/stimulus';

/*
 * Applique la palette choisie et retient le choix.
 *
 * L'attribut `data-theme` posé sur <html> suffit à repeindre la page : tokens.css y accroche
 * le `color-scheme` et les alias, et rien ici ne touche à une propriété de style.
 *
 * Une valeur vide retire l'attribut et efface le cookie : la préférence du système reprend
 * alors la main. Le cookie est relu côté serveur au chargement suivant, ce qui évite que la
 * page s'affiche dans la mauvaise palette avant que ce contrôleur ne démarre. Son nom doit
 * rester celui que le serveur relit, ce que `design-system:doctor` vérifie.
 */
export default class extends Controller {
    static values = {
        cookie: { type: String, default: 'theme' },
        days: { type: Number, default: 365 },
    };

    change(event) {
        const chosen = event.target.value;

        if (chosen) {
            document.documentElement.dataset.theme = chosen;
        } else {
            delete document.documentElement.dataset.theme;
        }

        const age = chosen ? this.daysValue * 86400 : 0;
        document.cookie = `${this.cookieValue}=${chosen}; path=/; max-age=${age}; samesite=lax`;
    }
}
