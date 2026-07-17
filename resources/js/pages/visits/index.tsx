import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatMoney } from '@/lib/format';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Visits', href: '/visits' }];

interface VisitRow {
    id: number;
    visit_number: string;
    visited_at: string;
    net_amount: string;
    paid_amount: string;
    payment_status: string;
    samples_count: number;
    verified_samples_count: number;
    patient: { id: number; name: string; mrn: string | null; phone: string | null };
    doctor: { id: number; name: string } | null;
}

const paymentVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    paid: 'default',
    partial: 'secondary',
    unpaid: 'destructive',
};

export default function VisitsIndex({ visits, filters }: { visits: Paginated<VisitRow>; filters: { q: string | null } }) {
    const [query, setQuery] = useState(filters.q ?? '');

    const search = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/visits', query ? { q: query } : {}, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Visits" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <form onSubmit={search} className="relative w-full max-w-sm">
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            placeholder="Search by visit no, patient, phone, MRN…"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            className="pl-8"
                        />
                    </form>
                    <Button asChild>
                        <Link href="/visits/create">New Visit</Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">Visit #</th>
                                <th className="px-3 py-2 font-medium">Patient</th>
                                <th className="px-3 py-2 font-medium">Referred by</th>
                                <th className="px-3 py-2 font-medium">Date</th>
                                <th className="px-3 py-2 text-right font-medium">Net</th>
                                <th className="px-3 py-2 font-medium">Payment</th>
                                <th className="px-3 py-2 font-medium">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            {visits.data.map((visit) => (
                                <tr key={visit.id} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2">
                                        <Link href={`/visits/${visit.id}`} className="font-medium underline-offset-2 hover:underline">
                                            {visit.visit_number}
                                        </Link>
                                    </td>
                                    <td className="px-3 py-2">
                                        {visit.patient.name}
                                        <span className="text-muted-foreground ml-1 text-xs">{visit.patient.mrn}</span>
                                    </td>
                                    <td className="px-3 py-2">{visit.doctor?.name ?? '—'}</td>
                                    <td className="px-3 py-2">{formatDateTime(visit.visited_at)}</td>
                                    <td className="px-3 py-2 text-right">{formatMoney(visit.net_amount)}</td>
                                    <td className="px-3 py-2">
                                        <Badge variant={paymentVariant[visit.payment_status] ?? 'secondary'}>{visit.payment_status}</Badge>
                                    </td>
                                    <td className="px-3 py-2">
                                        {visit.verified_samples_count}/{visit.samples_count} verified
                                    </td>
                                </tr>
                            ))}
                            {visits.data.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="text-muted-foreground px-3 py-8 text-center">
                                        No visits found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {visits.last_page > 1 && (
                    <div className="flex flex-wrap gap-1">
                        {visits.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveState
                                    className={`rounded-md border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="text-muted-foreground rounded-md border px-3 py-1 text-sm"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
