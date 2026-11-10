import { ref } from 'vue';

export type LandingTheme = 'dark' | 'light';

const STORAGE_KEY = 'vertax:landing-theme';
const theme = ref<LandingTheme>('dark');

/** Tema da landing: escuro por padrão, independente da aparência do app. */
export function useLandingTheme() {
    function restore(): void {
        try {
            const saved = window.localStorage.getItem(STORAGE_KEY);

            if (saved === 'dark' || saved === 'light') {
                theme.value = saved;
            }
        } catch {
            // Sem storage (aba privada, cookies bloqueados): fica o padrão.
        }
    }

    function toggle(): void {
        theme.value = theme.value === 'dark' ? 'light' : 'dark';

        try {
            window.localStorage.setItem(STORAGE_KEY, theme.value);
        } catch {
            // Idem: a troca vale só para esta visita.
        }
    }

    return { theme, restore, toggle };
}
