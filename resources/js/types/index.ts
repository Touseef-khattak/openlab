import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    roles: string[];
    permissions: string[];
    is_admin: boolean;
}

export interface Flash {
    success: string | null;
    error: string | null;
}

export interface PatientSummary {
    id: number;
    mrn: string | null;
    name: string;
    phone: string | null;
    cnic: string | null;
    gender: string;
    dob: string | null;
    age_years: number | null;
    visits_count?: number;
    last_visit_at?: string | null;
}

export interface Doctor {
    id: number;
    name: string;
    specialty: string | null;
    phone: string | null;
    commission_percent: string;
    is_active: boolean;
    visits_count?: number;
}

export interface CatalogTest {
    id: number;
    code: string;
    name: string;
    price: string;
    specimen_type: string | null;
    category?: { id: number; name: string } | null;
}

export interface LabSettings {
    id: number;
    name: string | null;
    logo: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    report_header: string | null;
    report_footer: string | null;
    receipt_footer: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: Flash;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
