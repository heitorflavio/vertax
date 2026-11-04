import { status, sync } from '@/routes/api/demo';
import { activate } from '@/routes/api/demo/plan';
import { rate } from '@/routes/api/demo/show';
import type { DemoPlanState } from '@/types/demo';

export interface DemoApiEnvelope<T> {
    demo: boolean;
    disclaimer?: string;
    message?: string;
    data: T;
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function request<T>(
    url: string,
    options: Pick<RequestInit, 'method' | 'body'> = {},
): Promise<DemoApiEnvelope<T>> {
    const response = await fetch(url, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
    });

    const payload = (await response.json().catch(() => ({
        message: 'Resposta inválida do servidor demo.',
    }))) as DemoApiEnvelope<T>;

    if (!response.ok) {
        throw new Error(
            payload.message ?? `Falha na API demo (${response.status}).`,
        );
    }

    return payload;
}

export const demoApi = {
    status: () => request<DemoPlanState>(status.url()),

    activatePlan: (planSlug: string) =>
        request<DemoPlanState>(activate.url(), {
            method: 'POST',
            body: JSON.stringify({ plan_slug: planSlug }),
        }),

    rate: (publicId: string, helpful: boolean) =>
        request<{ consultation_id: number; feedback: string }>(
            rate.url(publicId),
            {
                method: 'POST',
                body: JSON.stringify({ helpful }),
            },
        ),

    sync: () =>
        request<{
            status: string;
            sources_synced: number;
            payloads_ingested: number;
            records_merged: number;
            duration_ms: number | null;
        }>(sync.url(), { method: 'POST' }),
};
