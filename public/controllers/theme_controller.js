import { Controller } from '@hotwired/stimulus';

/*
 * Applique le thème et le mode choisis, et retient chacun des deux.
 *
 * `<html>` porte tout l'état : `data-mode`, et pour chaque côté le thème qui s'y affiche —
 * `data-theme-light`, `data-theme-dark`. Choisir un thème réécrit les deux côtés depuis l'option
 * choisie ; choisir un mode réécrit `data-mode`. `data-theme` s'en déduit, et tokens.css fait le
 * reste : rien ici ne touche à une propriété de style.
 *
 * Le menu du thème et celui du mode peuvent vivre dans deux instances distinctes : chacune ne
 * lit que son propre menu, et l'état commun reste sur `<html>`.
 *
 * Le thème se retient sous le cookie `cookie`, le mode sous le même nom suivi de `_mode`. Le
 * serveur les relit au chargement suivant, ce qui évite que la page s'affiche dans les mauvaises
 * couleurs avant que ce contrôleur ne démarre. Le nom doit rester celui que le serveur relit, ce
 * que `design-system:doctor` vérifie.
 */
export default class extends Controller {
    static values = {
        cookie: { type: String, default: 'theme' },
        days: { type: Number, default: 365 },
    };

    connect() {
        this.system = window.matchMedia('(prefers-color-scheme: dark)');
        this.follow = () => this.render();
        this.system.addEventListener('change', this.follow);
    }

    disconnect() {
        this.system.removeEventListener('change', this.follow);
    }

    chooseTheme(event) {
        const option = event.target.selectedOptions[0];
        const root = document.documentElement;

        root.dataset.themeLight = option.dataset.light;
        root.dataset.themeDark = option.dataset.dark;
        this.remember(this.cookieValue, option.value);
        this.render();
    }

    chooseMode(event) {
        document.documentElement.dataset.mode = event.target.value;
        this.remember(`${this.cookieValue}_mode`, event.target.value);
        this.render();
    }

    /* Le thème du côté que le mode désigne ; en automatique, du côté que le système réclame. */
    render() {
        const root = document.documentElement;
        const dark = root.dataset.mode === 'dark' || (root.dataset.mode === 'auto' && this.system.matches);

        root.dataset.theme = dark ? root.dataset.themeDark : root.dataset.themeLight;
    }

    remember(name, value) {
        document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=${this.daysValue * 86400}; samesite=lax`;
    }
}
