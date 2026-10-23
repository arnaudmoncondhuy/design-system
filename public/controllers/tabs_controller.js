import { Controller } from '@hotwired/stimulus';

/*
 * Des onglets, là où la page empilerait ses panneaux.
 *
 * Les onglets sont des liens d'ancre vers les panneaux. Sans script, tous les panneaux restent
 * affichés et le lien saute au bon endroit : la page reste entière et utilisable. Le
 * comportement ne fait qu'en cacher tous sauf un, et marquer son lien par `aria-current`, que
 * la feuille dessine et que le lecteur d'écran annonce.
 *
 * L'onglet ouvert s'écrit dans l'adresse, sans ajouter d'entrée à l'historique : un lien vers un
 * onglet s'envoie tel quel, et le bouton Précédent rend la page précédente plutôt que l'onglet
 * précédent. Une adresse qui vise un élément à l'intérieur d'un panneau ouvre ce panneau.
 */
export default class extends Controller {
    static targets = ['link', 'panel'];

    connect() {
        this.onHashChange = () => this.show(this.requested());
        window.addEventListener('hashchange', this.onHashChange);

        this.show(this.requested() ?? this.panelTargets[0]);
    }

    disconnect() {
        window.removeEventListener('hashchange', this.onHashChange);
    }

    select(event) {
        event.preventDefault();

        const panel = this.panelTargets.find((candidate) => `#${candidate.id}` === event.currentTarget.hash);

        if (panel) {
            this.show(panel);
            window.history.replaceState(window.history.state, '', `#${panel.id}`);
        }
    }

    /** Le panneau que l'adresse désigne, ou qui contient ce qu'elle désigne. */
    requested() {
        const id = decodeURIComponent(window.location.hash.slice(1));
        const target = id ? document.getElementById(id) : null;

        return target ? this.panelTargets.find((panel) => panel.contains(target)) : undefined;
    }

    show(panel) {
        if (!panel) {
            return;
        }

        for (const candidate of this.panelTargets) {
            candidate.hidden = candidate !== panel;
        }

        for (const link of this.linkTargets) {
            if (link.hash === `#${panel.id}`) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        }
    }
}
