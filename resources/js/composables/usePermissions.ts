import { usePage } from '@inertiajs/vue3';
import type { Permission } from '@/types';

/**
 * Permissões do usuário logado, para esconder o que ele não pode usar.
 * Não é proteção: as rotas checam a permissão de novo no backend.
 */
export function usePermissions() {
    const page = usePage();

    function can(permission: Permission): boolean {
        return page.props.auth.permissions?.includes(permission) ?? false;
    }

    return { can };
}
