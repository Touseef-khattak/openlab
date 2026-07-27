import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatMoney } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Banknote, ClipboardCheck, FlaskConical, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    stats: {
        visits_today: number;
        revenue_today: number;
        unpaid_balance: number;
        patients_total: number;
        pending_collection: number;
        on_bench: number;
        awaiting_verification: number;
        reported_today: number;
    };
    recentVisits: {
        id: number;
        visit_number: string;
        visited_at: string;
        net_amount: string;
        payment_status: string;
        patient: { id: number; name: string; mrn: string | null };
    }[];
}

const paymentVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    paid: 'default',
    partial: 'secondary',
    unpaid: 'destructive',
};

function Stat({ label, value, hint, href }: { label: string; value: string | number; hint?: string; href?: string }) {
    const body = (
        <Card className={href ? 'hover:bg-accent/50 transition-colors' : ''}>
            <CardContent className="flex flex-col gap-1 py-4">
                <span className="text-muted-foreground text-sm">{label}</span>
                <span className="text-2xl font-semibold tabular-nums">{value}</span>
                {hint && <span className="text-muted-foreground text-xs">{hint}</span>}
            </CardContent>
        </Card>
    );

    return href ? <Link href={href}>{body}</Link> : body;
}

export default function Dashboard({ stats, recentVisits }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-4 p-4">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Stat label="Visits today" value={stats.visits_today} hint={`${stats.patients_total} patients on file`} />
                    <Stat label="Revenue today" value={formatMoney(stats.revenue_today)} />
                    <Stat label="Unpaid balance" value={formatMoney(stats.unpaid_balance)} hint="across all visits" />
                    <Stat label="Reported today" value={stats.reported_today} />
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Stat label="Awaiting collection" value={stats.pending_collection} href="/worklist?status=registered" />
                    <Stat label="On the bench" value={stats.on_bench} href="/worklist?status=collected" />
                    <Stat label="Awaiting verification" value={stats.awaiting_verification} href="/verification" />
                </div>

                <Card>
                    <CardContent className="py-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="font-semibold">Recent visits</h2>
                            <Link href="/visits" className="text-muted-foreground text-sm underline-offset-2 hover:underline">
                                View all
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <tbody>
                                    {recentVisits.map((visit) => (
                                        <tr key={visit.id} className="border-b last:border-0">
                                            <td className="py-2 pr-3">
                                                <Link href={`/visits/${visit.id}`} className="font-medium underline-offset-2 hover:underline">
                                                    {visit.visit_number}
                                                </Link>
                                            </td>
                                            <td className="py-2 pr-3">
                                                {visit.patient.name}
                                                <span className="text-muted-foreground ml-1 text-xs">{visit.patient.mrn}</span>
                                            </td>
                                            <td className="text-muted-foreground py-2 pr-3">{formatDateTime(visit.visited_at)}</td>
                                            <td className="py-2 pr-3 text-right tabular-nums">{formatMoney(visit.net_amount)}</td>
                                            <td className="py-2 text-right">
                                                <Badge variant={paymentVariant[visit.payment_status] ?? 'secondary'}>
                                                    {visit.payment_status}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                    {recentVisits.length === 0 && (
                                        <tr>
                                            <td className="text-muted-foreground py-6 text-center">
                                                No visits yet — register the first one from “New Visit”.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <div className="text-muted-foreground flex flex-wrap gap-4 text-xs">
                    <span className="flex items-center gap-1">
                        <Users className="size-3.5" /> Front desk: register visits, patients, doctors
                    </span>
                    <span className="flex items-center gap-1">
                        <FlaskConical className="size-3.5" /> Bench: collect samples, enter results
                    </span>
                    <span className="flex items-center gap-1">
                        <ClipboardCheck className="size-3.5" /> Pathologist: verify and sign off
                    </span>
                    <span className="flex items-center gap-1">
                        <Banknote className="size-3.5" /> Reports unlock after verification
                    </span>
                </div>
            </div>
        </AppLayout>
    );
}
