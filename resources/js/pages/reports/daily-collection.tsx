import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { DateControl } from './report-controls';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Daily collection', href: '/reports/daily-collection' },
];

interface Row {
    visit_number: string;
    time: string;
    patient: string;
    mrn: string | null;
    gross: number;
    discount: number;
    net: number;
    paid: number;
    balance: number;
    payment_status: string;
}

const paymentVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    paid: 'default',
    partial: 'secondary',
    unpaid: 'destructive',
};

function Stat({ label, value, muted }: { label: string; value: string; muted?: boolean }) {
    return (
        <Card>
            <CardContent className="flex flex-col gap-1 py-4">
                <span className="text-muted-foreground text-sm">{label}</span>
                <span className={`text-xl font-semibold tabular-nums ${muted ? 'text-muted-foreground' : ''}`}>{value}</span>
            </CardContent>
        </Card>
    );
}

export default function DailyCollection({
    date,
    rows,
    summary,
    byStatus,
}: {
    date: string;
    rows: Row[];
    summary: { count: number; gross: number; discount: number; net: number; collected: number; outstanding: number };
    byStatus: Record<string, number>;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily collection" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <h1 className="text-xl font-semibold">Daily collection</h1>
                    <DateControl path="/reports/daily-collection" date={date} />
                </div>

                <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <Stat label={`Visits`} value={String(summary.count)} />
                    <Stat label="Gross" value={formatMoney(summary.gross)} />
                    <Stat label="Discount" value={formatMoney(summary.discount)} muted />
                    <Stat label="Net" value={formatMoney(summary.net)} />
                    <Stat label="Collected" value={formatMoney(summary.collected)} />
                    <Stat label="Outstanding" value={formatMoney(summary.outstanding)} muted />
                </div>

                <div className="text-muted-foreground flex gap-3 text-sm">
                    {Object.entries(byStatus).map(([status, count]) => (
                        <span key={status}>
                            <Badge variant={paymentVariant[status] ?? 'secondary'}>{status}</Badge> {count}
                        </span>
                    ))}
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">Visit</th>
                                <th className="px-3 py-2 font-medium">Time</th>
                                <th className="px-3 py-2 font-medium">Patient</th>
                                <th className="px-3 py-2 text-right font-medium">Gross</th>
                                <th className="px-3 py-2 text-right font-medium">Discount</th>
                                <th className="px-3 py-2 text-right font-medium">Net</th>
                                <th className="px-3 py-2 text-right font-medium">Paid</th>
                                <th className="px-3 py-2 text-right font-medium">Balance</th>
                                <th className="px-3 py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr key={row.visit_number} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2 font-medium">{row.visit_number}</td>
                                    <td className="px-3 py-2">{row.time}</td>
                                    <td className="px-3 py-2">
                                        {row.patient}
                                        <span className="text-muted-foreground ml-1 text-xs">{row.mrn}</span>
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatMoney(row.gross)}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatMoney(row.discount)}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatMoney(row.net)}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatMoney(row.paid)}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatMoney(row.balance)}</td>
                                    <td className="px-3 py-2">
                                        <Badge variant={paymentVariant[row.payment_status] ?? 'secondary'}>{row.payment_status}</Badge>
                                    </td>
                                </tr>
                            ))}
                            {rows.length === 0 && (
                                <tr>
                                    <td colSpan={9} className="text-muted-foreground px-3 py-8 text-center">
                                        No visits on this date.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
