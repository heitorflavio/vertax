import {
    Boxes,
    Braces,
    Database,
    Gauge,
    History,
    KeyRound,
    Network,
    Search,
    ShieldAlert,
    TrendingUp,
    UserCheck,
    Wallet,
    type LucideIcon,
} from '@lucide/vue';
import demo from '@/routes/demo';
import type { LandingPlan } from '@/types/landing';

export interface NavLink {
    label: string;
    description: string;
    href: string;
    icon: LucideIcon;
}

export interface NavGroup {
    label: string;
    items: NavLink[];
    footer?: { label: string; href: string };
}

export function navigation(plans: LandingPlan[]): NavGroup[] {
    return [
        {
            label: 'Produto',
            items: [
                {
                    label: 'Consulta multi-fonte',
                    description:
                        'Um registro único a partir de todas as fontes',
                    href: '#produto',
                    icon: Search,
                },
                {
                    label: 'Score de confiança',
                    description:
                        'Nota de 0 a 100 explicada em quatro dimensões',
                    href: '#produto',
                    icon: Gauge,
                },
                {
                    label: 'Módulos',
                    description: 'Vínculos, crédito, alerta de fraude e mais',
                    href: demo.modules.url(),
                    icon: Boxes,
                },
            ],
        },
        {
            label: 'Soluções',
            items: [
                {
                    label: 'Onboarding',
                    description: 'Valide um cadastro em uma consulta',
                    href: '#casos-de-uso',
                    icon: UserCheck,
                },
                {
                    label: 'Risco e fraude',
                    description: 'Divergências entre fontes na hora',
                    href: '#casos-de-uso',
                    icon: ShieldAlert,
                },
                {
                    label: 'Crédito e cobrança',
                    description: 'Score, renda e o contato certo',
                    href: '#casos-de-uso',
                    icon: TrendingUp,
                },
                {
                    label: 'Due diligence',
                    description: 'Vínculos societários de um CPF',
                    href: '#casos-de-uso',
                    icon: Network,
                },
            ],
        },
        {
            label: 'Plataforma',
            items: [
                {
                    label: 'Fontes integradas',
                    description: 'Peso de confiabilidade por fonte',
                    href: '#fontes',
                    icon: Database,
                },
                {
                    label: 'Resultado estruturado',
                    description: 'Registro mesclado, score e origem',
                    href: '#resultado',
                    icon: Braces,
                },
                {
                    label: 'Acesso e auditoria',
                    description: 'Papéis, passkeys, 2FA e histórico',
                    href: '#por-que',
                    icon: KeyRound,
                },
                {
                    label: 'Perguntas frequentes',
                    description: 'Como a busca e o score funcionam',
                    href: '#perguntas',
                    icon: History,
                },
            ],
        },
        {
            label: 'Planos',
            items: plans.map((plan) => ({
                label: plan.name,
                description: `${plan.price_label} · sessão de ${plan.duration_minutes} min`,
                href: demo.plans.url(),
                icon: Wallet,
            })),
            footer: { label: 'Comparar planos', href: demo.plans.url() },
        },
    ];
}

export function isAnchor(href: string): boolean {
    return href.startsWith('#');
}
