export interface LandingSource {
    slug: string;
    name: string;
    category: string;
    description: string | null;
    reliability: number;
    search_types: string[];
}

export interface LandingModule {
    slug: string;
    name: string;
    description: string;
    category: string;
    icon: string | null;
}

export interface LandingPlan {
    slug: string;
    name: string;
    description: string;
    price_label: string;
    price_cents: number;
    duration_minutes: number;
}

export interface LandingPageProps {
    canRegister: boolean;
    searchTypes: string[];
    mergedFields: string[];
    trustWeights: Record<string, number>;
    sources: LandingSource[];
    modules: LandingModule[];
    plans: LandingPlan[];
}
