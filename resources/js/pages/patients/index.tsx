import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { patientAge } from '@/lib/format';
import { type BreadcrumbItem, type Paginated, type PatientSummary } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Patients', href: '/patients' }];

export default function PatientsIndex({
    patients,
    filters,
}: {
    patients: Paginated<PatientSummary>;
    filters: { q: string | null };
}) {
    const [query, setQuery] = useState(filters.q ?? '');

    const search = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/patients', query ? { q: query } : {}, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Patients" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <form onSubmit={search} className="relative w-full max-w-sm">
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            placeholder="Search by name, phone, CNIC, MRN…"
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
                                <th className="px-3 py-2 font-medium">MRN</th>
                                <th className="px-3 py-2 font-medium">Name</th>
                                <th className="px-3 py-2 font-medium">Age / Gender</th>
                                <th className="px-3 py-2 font-medium">Phone</th>
                                <th className="px-3 py-2 font-medium">CNIC</th>
                                <th className="px-3 py-2 text-right font-medium">Visits</th>
                            </tr>
                        </thead>
                        <tbody>
                            {patients.data.map((patient) => (
                                <tr key={patient.id} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2">{patient.mrn}</td>
                                    <td className="px-3 py-2 font-medium">{patient.name}</td>
                                    <td className="px-3 py-2">
                                        {patientAge(patient.dob, patient.age_years)} / {patient.gender}
                                    </td>
                                    <td className="px-3 py-2">{patient.phone ?? '—'}</td>
                                    <td className="px-3 py-2">{patient.cnic ?? '—'}</td>
                                    <td className="px-3 py-2 text-right">{patient.visits_count}</td>
                                </tr>
                            ))}
                            {patients.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground px-3 py-8 text-center">
                                        No patients found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {patients.last_page > 1 && (
                    <div className="flex flex-wrap gap-1">
                        {patients.links.map((link, index) =>
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
