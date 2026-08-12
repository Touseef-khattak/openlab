import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Banknote, LineChart, Stethoscope, Timer } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '/reports' }];

const reports = [
    {
        title: 'Daily collection',
        description: 'End-of-day closing: gross, discount, net, collected and outstanding for a chosen day.',
        href: '/reports/daily-collection',
        icon: Banknote,
    },
    {
        title: 'Revenue over a period',
        description: 'Financials across a date range with a daily trend and a breakdown by test category.',
        href: '/reports/revenue',
        icon: LineChart,
    },
    {
        title: 'Test volume & turnaround',
        description: 'Most-ordered tests, average turnaround time per stage, and the current pending backlog.',
        href: '/reports/volume-tat',
        icon: Timer,
    },
    {
        title: 'Doctor commission',
        description: 'Per-referring-doctor visits, referred net and commission payable for a period.',
        href: '/reports/doctor-commission',
        icon: Stethoscope,
    },
];

export default function ReportsIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Reports</h1>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {reports.map((report) => (
                        <Link key={report.href} href={report.href}>
                            <Card className="hover:bg-accent/50 h-full transition-colors">
                                <CardContent className="flex flex-col gap-2 py-5">
                                    <report.icon className="text-muted-foreground size-6" />
                                    <span className="font-medium">{report.title}</span>
                                    <span className="text-muted-foreground text-sm">{report.description}</span>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
                <p className="text-muted-foreground text-xs">
                    Every report can be exported to CSV for Excel. Figures are based on visit registration date.
                </p>
            </div>
        </AppLayout>
    );
}
