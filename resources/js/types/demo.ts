export interface DemoPlanState {
    active: boolean;
    slug: string | null;
    name: string | null;
    price_label: string | null;
    features: string[];
    grants_hidden: boolean;
    activated_at: string | null;
    expires_at: string | null;
    remaining_seconds: number;
}

export interface SharedDemoProps {
    plan: DemoPlanState;
    disclaimer: string;
    shortDisclaimer: string;
    copyNotice: string;
    defaultPlanSlug: string;
}

export interface DemoTrustBreakdown {
    source_quality: number;
    completeness: number;
    consistency: number;
    freshness: number;
    weights: Record<string, number>;
    sources_evaluated: number;
    fields_filled: number;
    fields_total: number;
    agreement_by_field: Record<string, number>;
    missing_fields: string[];
    newest_payload_age_days: number | null;
    explanation: string;
    evaluated_at: string | null;
}

export interface DemoTrust {
    score: number;
    label: string;
    breakdown: DemoTrustBreakdown;
}

export interface DemoField {
    key: string;
    label: string;
    value: string | null;
}

export interface DemoSourceContribution {
    slug: string | null;
    name: string;
    category: string | null;
    reliability: number;
    match_score: number;
    fetched_at: string | null;
    contributed_fields: string[];
}

export interface DemoRecordSummary {
    public_id: string;
    full_name: string;
    cpf_masked: string;
    birth_date: string | null;
    birth_date_formatted: string | null;
    trust_score: number;
    trust_label: string;
    sources_count: number;
    consultations_count: number;
    last_consulted_at: string | null;
    url: string;
}

export interface DemoSearchExample {
    type: string;
    value: string;
}

export interface DemoSearchResult extends DemoRecordSummary {
    matched_by: string;
    matched_by_label: string;
}

export interface DemoRecentSearch {
    public_id: string;
    full_name: string;
    trust_score: number;
    trust_label: string;
    searched_at: string | null;
}

export interface DemoRecordDetail extends DemoRecordSummary {
    cpf: string | null;
    fields: DemoField[];
    basic_fields: DemoField[];
    hidden: DemoField[] | null;
    hidden_locked: boolean;
    hidden_fields_count: number;
    trust: DemoTrust;
    sources: DemoSourceContribution[];
    consultation: {
        id: number;
        evaluated_at: string | null;
        trust_score: number;
        plan_active: boolean;
    } | null;
}

export interface DemoModule {
    slug: string;
    name: string;
    description: string;
    category: string;
    icon: string | null;
    badge: string;
    is_demo: boolean;
}

export interface DemoPlan {
    slug: string;
    name: string;
    description: string;
    price_label: string;
    price_cents?: number;
    duration_minutes: number;
    grants_hidden: boolean;
    features: string[];
    recommended: boolean;
    sort_order?: number;
}

export interface DemoStats {
    records: number;
    sources: number;
    consultations: number;
    avg_trust: number;
    plan_activations?: number;
    helpful?: number;
    not_helpful?: number;
}

export interface DemoIngestionRunSummary {
    status: string;
    sources_synced: number;
    payloads_ingested: number;
    records_merged: number;
    duration_ms: number | null;
    message: string | null;
    started_at: string | null;
    finished_at: string | null;
}

export interface DemoPanelSource {
    slug: string;
    name: string;
    category: string;
    description: string | null;
    reliability: number;
    is_active: boolean;
    payloads: number;
}
