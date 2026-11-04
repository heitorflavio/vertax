import {
    Boxes,
    Gauge,
    History,
    MapPin,
    Network,
    Phone,
    ShieldAlert,
    type LucideIcon,
} from '@lucide/vue';

const icons: Record<string, LucideIcon> = {
    'map-pin': MapPin,
    phone: Phone,
    gauge: Gauge,
    network: Network,
    history: History,
    'shield-alert': ShieldAlert,
};

export function demoIcon(name: string | null | undefined): LucideIcon {
    return (name && icons[name]) || Boxes;
}
