import { Controller } from '@hotwired/stimulus';

/*
 * Ne garde affichés que les éléments qui contiennent chacun des mots saisis.
 *
 * Les éléments sont déjà rendus : rien n'est demandé au serveur, et vider le champ rend la liste
 * entière. Un élément est lu dans son `data-search` s'il en porte un, dans son texte sinon. La
 * casse et les accents ne comptent pas : « télé » trouve « Téléphone », et « telephone » aussi.
 *
 * La cible `empty`, facultative, dit qu'une recherche ne trouve rien : une liste dont plus rien
 * ne correspond ne se distingue pas, sans elle, d'une liste qui vient de se vider. Elle reste
 * cachée tant que rien n'est saisi — une liste vide a son propre message, que l'écran donne.
 *
 * Le filtre se rejoue sur un élément qui arrive après coup, et au chargement : un navigateur qui
 * rend le champ rempli au retour sur la page doit retrouver la liste filtrée.
 */
export default class extends Controller {
    static targets = ['input', 'item', 'empty'];

    connect() {
        this.apply();
    }

    itemTargetConnected() {
        if (!this.pending) {
            this.pending = true;
            queueMicrotask(() => {
                this.pending = false;
                this.apply();
            });
        }
    }

    apply() {
        if (!this.hasInputTarget) {
            return;
        }

        const words = this.normalize(this.inputTarget.value).split(/\s+/).filter(Boolean);
        let shown = 0;

        for (const item of this.itemTargets) {
            const text = this.normalize(item.dataset.search ?? item.textContent);
            const match = words.every((word) => text.includes(word));

            item.hidden = !match;
            shown += match ? 1 : 0;
        }

        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = shown > 0 || words.length === 0;
        }
    }

    normalize(text) {
        return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase();
    }
}
