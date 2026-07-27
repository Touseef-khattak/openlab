import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { useCan } from '@/hooks/use-can';
import { formatDateTime, formatMoney, patientAge } from '@/lib/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { FileText, Printer } from 'lucide-react';

interface VisitDetail {
    id: number;
    visit_number: string;
    visited_at: string;
    total_amount: string;
    discount: string;
    net_amount: string;
    paid_amount: string;
    payment_status: string;
    patient: {
        id: number;
        mrn: string | null;
        name: string;
        gender: string;
        phone: string | null;
        cnic: string | null;
        dob: string | null;
        age_years: number | null;
    };
    doctor: { id: number; name: string; specialty: string | null } | null;
    creator: { id: number; name: string } | null;
    samples: { id: number; sample_number: string | null; specimen_type: string; status: string }[];
    visit_tests: {
        id: number;
        price: string;
        test: { id: number; code: string; name: string; specimen_type: string | null };
        sample: { id: number; sample_number: string | null; specimen_type: string; status: string } | null;
    }[];
}

export default function VisitShow({ visit }: { visit: VisitDetail }) {
    const { flash, name: appName } = usePage<SharedData>().props;
    const can = useCan();
    const hasVerifiedResults = visit.samples.some((sample) => ['verified', 'reported'].includes(sample.status));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Visits', href: '/visits' },
        { title: visit.visit_number, href: `/visits/${visit.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={visit.visit_number} />
            <style>{`@media print { body * { visibility: hidden; } #receipt, #receipt * { visibility: visible; } #receipt { position: absolute; inset: 0; padding: 24px; } }`}</style>
            <div className="flex flex-col gap-4 p-4">
                {flash.success && (
                    <div className="rounded-md border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Visit {visit.visit_number}</h1>
                    <div className="flex gap-2">
                        {hasVerifiedResults && can('reports.view') && (
                            <Button asChild>
                                <a href={`/visits/${visit.id}/report`} target="_blank" rel="noopener">
                                    <FileText className="size-4" /> Lab report (PDF)
                                </a>
                            </Button>
                        )}
                        <Button variant="outline" onClick={() => window.print()}>
                            <Printer className="size-4" /> Print receipt
                        </Button>
                    </div>
                </div>

                <div id="receipt" className="flex flex-col gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-center text-lg">{appName}</CardTitle>
                            <p className="text-muted-foreground text-center text-xs">
                                Receipt · {visit.visit_number} · {formatDateTime(visit.visited_at)}
                            </p>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <div className="grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <span className="text-muted-foreground">Patient: </span>
                                    <span className="font-medium">{visit.patient.name}</span>
                                    <span className="text-muted-foreground ml-1 text-xs">({visit.patient.mrn})</span>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">Age / Gender: </span>
                                    {patientAge(visit.patient.dob, visit.patient.age_years)} / {visit.patient.gender}
                                </div>
                                <div>
                                    <span className="text-muted-foreground">Phone: </span>
                                    {visit.patient.phone ?? '—'}
                                </div>
                                <div>
                                    <span className="text-muted-foreground">Referred by: </span>
                                    {visit.doctor ? `${visit.doctor.name}${visit.doctor.specialty ? ` (${visit.doctor.specialty})` : ''}` : 'Self'}
                                </div>
                            </div>

                            <Separator />

                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="py-1.5 font-medium">Code</th>
                                        <th className="py-1.5 font-medium">Test</th>
                                        <th className="py-1.5 font-medium">Sample</th>
                                        <th className="py-1.5 text-right font-medium">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visit.visit_tests.map((visitTest) => (
                                        <tr key={visitTest.id} className="border-b last:border-0">
                                            <td className="py-1.5">{visitTest.test.code}</td>
                                            <td className="py-1.5">{visitTest.test.name}</td>
                                            <td className="py-1.5">{visitTest.sample?.sample_number ?? '—'}</td>
                                            <td className="py-1.5 text-right">{formatMoney(visitTest.price)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="ml-auto flex w-full max-w-xs flex-col gap-1 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Total</span>
                                    <span>{formatMoney(visit.total_amount)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Discount</span>
                                    <span>- {formatMoney(visit.discount)}</span>
                                </div>
                                <div className="flex justify-between font-semibold">
                                    <span>Net</span>
                                    <span>{formatMoney(visit.net_amount)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Paid</span>
                                    <span>{formatMoney(visit.paid_amount)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Balance</span>
                                    <span>{formatMoney(Number(visit.net_amount) - Number(visit.paid_amount))}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="print:hidden">
                        <CardHeader>
                            <CardTitle>Samples</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-3">
                                {visit.samples.map((sample) => (
                                    <div key={sample.id} className="rounded-md border px-3 py-2 text-sm">
                                        <div className="font-medium">{sample.sample_number}</div>
                                        <div className="text-muted-foreground text-xs">{sample.specimen_type}</div>
                                        <Badge variant="secondary" className="mt-1">
                                            {sample.status}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
