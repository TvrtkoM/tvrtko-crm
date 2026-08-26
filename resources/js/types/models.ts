export type Company = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    website: string | null;
    industry: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    status: string;
    notes: string | null;
    contacts_count?: number;
    deals_count?: number;
    contacts?: Contact[];
    deals?: Deal[];
    created_at: string;
    updated_at: string;
};

export type Contact = {
    id: number;
    company_id: number | null;
    first_name: string;
    last_name: string | null;
    email: string | null;
    phone: string | null;
    job_title: string | null;
    status: string;
    notes: string | null;
    company?: Company | null;
    deals?: Deal[];
    created_at: string;
    updated_at: string;
};

export type Deal = {
    id: number;
    company_id: number;
    contact_id: number | null;
    title: string;
    value: string | null;
    expected_close_date: string | null;
    status: string;
    notes: string | null;
    company?: Company;
    contact?: Contact | null;
    offers?: Offer[];
    created_at: string;
    updated_at: string;
};

export type OfferItem = {
    id: number;
    offer_id: number;
    description: string;
    quantity: string;
    unit_price: string;
    position: number;
    line_total: number;
};

export type Offer = {
    id: number;
    deal_id: number;
    offer_number: string;
    title: string | null;
    status: string;
    issue_date: string | null;
    valid_until: string | null;
    tax_rate: string;
    notes: string | null;
    subtotal: number;
    tax_amount: number;
    total: number;
    deal?: Deal;
    items?: OfferItem[];
    created_at: string;
    updated_at: string;
};
