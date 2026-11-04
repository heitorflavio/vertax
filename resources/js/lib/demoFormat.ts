import type { BadgeVariants } from '@/components/ui/badge';

export function trustVariant(score: number): BadgeVariants['variant'] {
    if (score >= 85) {
        return 'default';
    }

    if (score >= 65) {
        return 'secondary';
    }

    return 'destructive';
}

export function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const relativeFormatter = new Intl.RelativeTimeFormat('pt-BR', {
    numeric: 'auto',
});

export function formatRelative(value: string | null): string {
    if (!value) {
        return '—';
    }

    const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000);

    if (Math.abs(seconds) < 60) {
        return 'agora';
    }

    if (Math.abs(seconds) < 3600) {
        return relativeFormatter.format(Math.round(seconds / 60), 'minute');
    }

    if (Math.abs(seconds) < 86400) {
        return relativeFormatter.format(Math.round(seconds / 3600), 'hour');
    }

    return relativeFormatter.format(Math.round(seconds / 86400), 'day');
}
