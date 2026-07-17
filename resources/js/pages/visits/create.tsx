import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { formatMoney, patientAge } from '@/lib/format';
import { type BreadcrumbItem, type CatalogTest, type Doctor, type PatientSummary } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Search, UserCheck, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Visits', href: '/visits' },
    { title: 'New Visit', href: '/visits/create' },
];

interface VisitForm {
    patient: {
        id: number | null;
        name: string;
        gender: string;
        phone: string;
        cnic: string;
        dob: string;
        age_years: string;
    };
    doctor_id: string;
    test_ids: number[];
    discount: string;
    paid_amount: string;
    [key: string]: unknown;
}

const emptyPatient = { id: null, name: '', gender: '', phone: '', cnic: '', dob: '', age_years: '' };

export default function CreateVisit({ doctors, tests }: { doctors: Doctor[]; tests: CatalogTest[] }) {
    const { data, setData, post, processing, errors, transform } = useForm<VisitForm>({
        patient: { ...emptyPatient },
        doctor_id: 'none',
        test_ids: [],
        discount: '',
        paid_amount: '',
    });

    // --- patient search ---
    const [patientQuery, setPatientQuery] = useState('');
    const [matches, setMatches] = useState<PatientSummary[]>([]);
    const [searching, setSearching] = useState(false);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        abortRef.current?.abort();
        if (patientQuery.trim().length < 3 || data.patient.id !== null) {
            setMatches([]);
            return;
        }
        const controller = new AbortController();
        abortRef.current = controller;
        const timer = setTimeout(() => {
            setSearching(true);
            fetch(`/patients/search?q=${encodeURIComponent(patientQuery.trim())}`, {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((results: PatientSummary[]) => setMatches(results))
                .catch(() => undefined)
                .finally(() => setSearching(false));
        }, 300);
        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [patientQuery, data.patient.id]);

    const selectPatient = (patient: PatientSummary) => {
        setData('patient', {
            id: patient.id,
            name: patient.name,
            gender: patient.gender,
            phone: patient.phone ?? '',
            cnic: patient.cnic ?? '',
            dob: patient.dob ?? '',
            age_years: patient.age_years?.toString() ?? '',
        });
        setMatches([]);
        setPatientQuery('');
    };

    const clearPatient = () => setData('patient', { ...emptyPatient });

    // --- test picker ---
    const [testQuery, setTestQuery] = useState('');
    const filteredTests = useMemo(() => {
        const q = testQuery.trim().toLowerCase();
        if (!q) return tests;
        return tests.filter(
            (test) => test.name.toLowerCase().includes(q) || test.code.toLowerCase().includes(q),
        );
    }, [tests, testQuery]);

    const grouped = useMemo(() => {
        const groups = new Map<string, CatalogTest[]>();
        for (const test of filteredTests) {
            const key = test.category?.name ?? 'Other';
            groups.set(key, [...(groups.get(key) ?? []), test]);
        }
        return [...groups.entries()];
    }, [filteredTests]);

    const toggleTest = (id: number) =>
        setData(
            'test_ids',
            data.test_ids.includes(id) ? data.test_ids.filter((t) => t !== id) : [...data.test_ids, id],
        );

    const selectedTests = useMemo(
        () => data.test_ids.map((id) => tests.find((t) => t.id === id)).filter(Boolean) as CatalogTest[],
        [data.test_ids, tests],
    );

    // --- billing ---
    const total = selectedTests.reduce((sum, test) => sum + Number(test.price), 0);
    const discount = Math.min(Number(data.discount) || 0, total);
    const net = total - discount;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        transform((form) => ({
            ...form,
            doctor_id: form.doctor_id === 'none' ? null : Number(form.doctor_id),
            discount: form.discount === '' ? 0 : Number(form.discount),
            paid_amount: form.paid_amount === '' ? 0 : Number(form.paid_amount),
        }));
        post('/visits');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Visit" />
            <form onSubmit={submit} className="grid gap-4 p-4 lg:grid-cols-2">
                {/* ------- left: patient + referral ------- */}
                <div className="flex flex-col gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                Patient
                                {data.patient.id !== null && (
                                    <Badge variant="secondary" className="gap-1">
                                        <UserCheck className="size-3" /> Existing patient
                                        <button type="button" onClick={clearPatient} className="ml-1 cursor-pointer">
                                            <X className="size-3" />
                                        </button>
                                    </Badge>
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            {data.patient.id === null && (
                                <div className="relative">
                                    <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                                    <Input
                                        placeholder="Search existing patient by phone, CNIC, MRN or name…"
                                        value={patientQuery}
                                        onChange={(event) => setPatientQuery(event.target.value)}
                                        className="pl-8"
                                    />
                                    {searching && <LoaderCircle className="absolute top-2.5 right-2.5 size-4 animate-spin" />}
                                    {matches.length > 0 && (
                                        <div className="bg-popover absolute z-10 mt-1 w-full rounded-md border shadow-md">
                                            {matches.map((match) => (
                                                <button
                                                    key={match.id}
                                                    type="button"
                                                    onClick={() => selectPatient(match)}
                                                    className="hover:bg-accent flex w-full flex-col px-3 py-2 text-left text-sm"
                                                >
                                                    <span className="font-medium">
                                                        {match.name}
                                                        <span className="text-muted-foreground ml-2 text-xs">
                                                            {match.mrn} · {patientAge(match.dob, match.age_years)} · {match.gender}
                                                        </span>
                                                    </span>
                                                    <span className="text-muted-foreground text-xs">
                                                        {match.phone ?? 'no phone'} · {match.visits_count} previous visit(s)
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Label htmlFor="patient-name">Full name *</Label>
                                    <Input
                                        id="patient-name"
                                        value={data.patient.name}
                                        disabled={data.patient.id !== null}
                                        onChange={(event) => setData('patient', { ...data.patient, name: event.target.value })}
                                    />
                                    <InputError message={errors['patient.name']} />
                                </div>
                                <div>
                                    <Label>Gender *</Label>
                                    <Select
                                        value={data.patient.gender}
                                        disabled={data.patient.id !== null}
                                        onValueChange={(value) => setData('patient', { ...data.patient, gender: value })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors['patient.gender']} />
                                </div>
                                <div>
                                    <Label htmlFor="patient-phone">Phone</Label>
                                    <Input
                                        id="patient-phone"
                                        value={data.patient.phone}
                                        disabled={data.patient.id !== null}
                                        placeholder="03XXXXXXXXX"
                                        onChange={(event) => setData('patient', { ...data.patient, phone: event.target.value })}
                                    />
                                    <InputError message={errors['patient.phone']} />
                                </div>
                                <div>
                                    <Label htmlFor="patient-dob">Date of birth</Label>
                                    <Input
                                        id="patient-dob"
                                        type="date"
                                        value={data.patient.dob}
                                        disabled={data.patient.id !== null}
                                        onChange={(event) => setData('patient', { ...data.patient, dob: event.target.value })}
                                    />
                                    <InputError message={errors['patient.dob']} />
                                </div>
                                <div>
                                    <Label htmlFor="patient-age">Age (years, if DOB unknown)</Label>
                                    <Input
                                        id="patient-age"
                                        type="number"
                                        min={0}
                                        value={data.patient.age_years}
                                        disabled={data.patient.id !== null || data.patient.dob !== ''}
                                        onChange={(event) => setData('patient', { ...data.patient, age_years: event.target.value })}
                                    />
                                    <InputError message={errors['patient.age_years']} />
                                </div>
                                <div>
                                    <Label htmlFor="patient-cnic">CNIC</Label>
                                    <Input
                                        id="patient-cnic"
                                        value={data.patient.cnic}
                                        disabled={data.patient.id !== null}
                                        placeholder="XXXXX-XXXXXXX-X"
                                        onChange={(event) => setData('patient', { ...data.patient, cnic: event.target.value })}
                                    />
                                    <InputError message={errors['patient.cnic']} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Referred by</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Select value={data.doctor_id} onValueChange={(value) => setData('doctor_id', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Self / walk-in" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Self / walk-in</SelectItem>
                                    {doctors.map((doctor) => (
                                        <SelectItem key={doctor.id} value={doctor.id.toString()}>
                                            {doctor.name}
                                            {doctor.specialty ? ` — ${doctor.specialty}` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.doctor_id} />
                        </CardContent>
                    </Card>
                </div>

                {/* ------- right: tests + bill ------- */}
                <div className="flex flex-col gap-4">
                    <Card className="flex-1">
                        <CardHeader>
                            <CardTitle>Tests</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            <Input
                                placeholder="Search test by name or code…"
                                value={testQuery}
                                onChange={(event) => setTestQuery(event.target.value)}
                            />
                            <InputError message={errors.test_ids} />
                            <div className="max-h-72 overflow-y-auto rounded-md border">
                                {grouped.map(([category, categoryTests]) => (
                                    <div key={category}>
                                        <div className="bg-muted text-muted-foreground sticky top-0 px-3 py-1 text-xs font-semibold uppercase">
                                            {category}
                                        </div>
                                        {categoryTests.map((test) => (
                                            <button
                                                key={test.id}
                                                type="button"
                                                onClick={() => toggleTest(test.id)}
                                                className={`hover:bg-accent flex w-full items-center justify-between px-3 py-2 text-left text-sm ${
                                                    data.test_ids.includes(test.id) ? 'bg-accent font-medium' : ''
                                                }`}
                                            >
                                                <span>
                                                    <span className="text-muted-foreground mr-2 text-xs">{test.code}</span>
                                                    {test.name}
                                                </span>
                                                <span>{formatMoney(test.price)}</span>
                                            </button>
                                        ))}
                                    </div>
                                ))}
                                {grouped.length === 0 && (
                                    <p className="text-muted-foreground p-3 text-sm">No tests match "{testQuery}".</p>
                                )}
                            </div>

                            {selectedTests.length > 0 && (
                                <div className="flex flex-wrap gap-1.5">
                                    {selectedTests.map((test) => (
                                        <Badge key={test.id} variant="secondary" className="gap-1">
                                            {test.code}
                                            <button type="button" onClick={() => toggleTest(test.id)} className="cursor-pointer">
                                                <X className="size-3" />
                                            </button>
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Bill</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            <div className="flex justify-between text-sm">
                                <span>Total ({selectedTests.length} tests)</span>
                                <span className="font-medium">{formatMoney(total)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <Label htmlFor="discount" className="shrink-0">
                                    Discount
                                </Label>
                                <Input
                                    id="discount"
                                    type="number"
                                    min={0}
                                    max={total}
                                    className="w-32 text-right"
                                    value={data.discount}
                                    onChange={(event) => setData('discount', event.target.value)}
                                />
                            </div>
                            <Separator />
                            <div className="flex justify-between text-base font-semibold">
                                <span>Net payable</span>
                                <span>{formatMoney(net)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <Label htmlFor="paid" className="shrink-0">
                                    Paid now
                                </Label>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setData('paid_amount', net.toString())}
                                    >
                                        Full
                                    </Button>
                                    <Input
                                        id="paid"
                                        type="number"
                                        min={0}
                                        max={net}
                                        className="w-32 text-right"
                                        value={data.paid_amount}
                                        onChange={(event) => setData('paid_amount', event.target.value)}
                                    />
                                </div>
                            </div>
                            <Button type="submit" className="mt-2 w-full" disabled={processing || data.test_ids.length === 0}>
                                {processing && <LoaderCircle className="size-4 animate-spin" />}
                                Register visit
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </AppLayout>
    );
}
