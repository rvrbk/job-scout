export type Option = {
    value: string;
    label: string;
};

export type LedgerFact = {
    id: number;
    statement: string;
    evidence_url: string | null;
    confirmed: boolean;
};

export type LedgerEntry = {
    id: number;
    type: string;
    title: string;
    organization: string | null;
    location: string | null;
    started_on: string | null;
    ended_on: string | null;
    period: string | null;
    skills: string[];
    evidence_url: string | null;
    confirmed: boolean;
    facts: LedgerFact[];
};

export type JobPostingSummary = {
    id: number;
    title: string;
    company: string | null;
    location: string | null;
    remote: boolean | null;
    source_url: string | null;
    found: boolean;
    match_score: number | null;
    match_reason: string | null;
    documents_count: number;
    documents: Pick<DocumentSummary, 'type' | 'type_label' | 'status'>[];
    created_at: string | null;
};

export type JobSearchPreferences = {
    locations: string[];
    remote_preference: 'any' | 'hybrid' | 'remote_only';
    keywords: string[];
    exclusions: string[];
    feeds: string[];
    min_score: number;
    salary_min: number | null;
    salary_max: number | null;
    last_searched_at: string | null;
};

export type JobPosting = {
    id: number;
    title: string;
    company: string | null;
    source_url: string | null;
    description: string;
};

export type DocumentStatus =
    | 'generating'
    | 'verifying'
    | 'needs_review'
    | 'verified'
    | 'approved'
    | 'failed';

export type DocumentSummary = {
    id: number;
    type: string;
    type_label: string;
    status: DocumentStatus;
    created_at: string | null;
};

export type DocumentClaim = {
    id: number;
    section: 'summary' | 'experience' | 'skills' | 'letter';
    group: number;
    kind: 'claim' | 'framing' | 'skill';
    text: string;
    status: 'pending' | 'supported' | 'unsupported';
    verifier_note: string | null;
    entry_heading: string | null;
    facts: { id: number; statement: string }[];
};

export type GeneratedDocument = {
    id: number;
    type: 'cv' | 'cover_letter';
    type_label: string;
    status: DocumentStatus;
    is_processing: boolean;
    can_be_approved: boolean;
    approved_at: string | null;
    error: string | null;
    gaps: { requirement: string; note: string }[];
    job: { id: number; title: string; company: string | null };
    claims: DocumentClaim[];
    markdown: string | null;
};
