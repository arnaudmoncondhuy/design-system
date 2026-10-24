/*
 * Choisit, avant la première peinture, le thème que le mode automatique réclame.
 *
 * Le gabarit ne pose ce script que lorsque le serveur ne peut pas trancher : en mode
 * automatique, le thème choisi n'a qu'un mode, et c'est la préférence du système qui dit s'il
 * s'affiche ou s'il cède la place au thème par défaut. `<html>` porte les deux réponses —
 * `data-theme-light`, `data-theme-dark` — ; ce script retient celle du système, et la suit
 * quand elle change.
 *
 * Script classique et bloquant, sans module : un module ne s'exécute qu'après la peinture.
 */
(() => {
    const root = document.documentElement;
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    const render = () => {
        if (root.dataset.mode === 'auto') {
            root.dataset.theme = system.matches ? root.dataset.themeDark : root.dataset.themeLight;
        }
    };

    render();
    system.addEventListener('change', render);
})();
