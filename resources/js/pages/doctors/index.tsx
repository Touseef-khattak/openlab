import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { useCan } from '@/hooks/use-can';
import { type BreadcrumbItem, type Doctor, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Doctors', href: '/doctors' }];

interface DoctorForm {
    name: string;
    specialty: string;
    phone: string;
    commission_percent: string;
    is_active: boolean;
    [key: string]: unknown;
}

export default function DoctorsIndex({ doctors }: { doctors: Doctor[] }) {
    const can = useCan();
    const { flash } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Doctor | null>(null);

    const form = useForm<DoctorForm>({
        name: '',
        specialty: '',
        phone: '',
        commission_percent: '0',
        is_active: true,
    });

    const openCreate = () => {
        setEditing(null);
        form.setData({ name: '', specialty: '', phone: '', commission_percent: '0', is_active: true });
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (doctor: Doctor) => {
        setEditing(doctor);
        form.setData({
            name: doctor.name,
            specialty: doctor.specialty ?? '',
            phone: doctor.phone ?? '',
            commission_percent: doctor.commission_percent?.toString() ?? '0',
            is_active: doctor.is_active,
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { onSuccess: () => setOpen(false) };
        if (editing) {
            form.put(`/doctors/${editing.id}`, options);
        } else {
            form.post('/doctors', options);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Doctors" />
            <div className="flex flex-col gap-4 p-4">
                {flash.success && (
                    <div className="rounded-md border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Referring doctors</h1>
                    {can('doctors.manage') && (
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Add doctor
                        </Button>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">Name</th>
                                <th className="px-3 py-2 font-medium">Specialty</th>
                                <th className="px-3 py-2 font-medium">Phone</th>
                                <th className="px-3 py-2 text-right font-medium">Commission %</th>
                                <th className="px-3 py-2 text-right font-medium">Referrals</th>
                                <th className="px-3 py-2 font-medium">Status</th>
                                {can('doctors.manage') && <th className="px-3 py-2" />}
                            </tr>
                        </thead>
                        <tbody>
                            {doctors.map((doctor) => (
                                <tr key={doctor.id} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2 font-medium">{doctor.name}</td>
                                    <td className="px-3 py-2">{doctor.specialty ?? '—'}</td>
                                    <td className="px-3 py-2">{doctor.phone ?? '—'}</td>
                                    <td className="px-3 py-2 text-right">{Number(doctor.commission_percent)}%</td>
                                    <td className="px-3 py-2 text-right">{doctor.visits_count}</td>
                                    <td className="px-3 py-2">
                                        <Badge variant={doctor.is_active ? 'default' : 'secondary'}>
                                            {doctor.is_active ? 'active' : 'inactive'}
                                        </Badge>
                                    </td>
                                    {can('doctors.manage') && (
                                        <td className="px-3 py-2 text-right">
                                            <Button variant="ghost" size="sm" onClick={() => openEdit(doctor)}>
                                                <Pencil className="size-4" />
                                            </Button>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {doctors.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="text-muted-foreground px-3 py-8 text-center">
                                        No doctors yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? `Edit ${editing.name}` : 'Add doctor'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <div>
                            <Label htmlFor="doctor-name">Name *</Label>
                            <Input id="doctor-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="doctor-specialty">Specialty</Label>
                            <Input
                                id="doctor-specialty"
                                value={form.data.specialty}
                                onChange={(e) => form.setData('specialty', e.target.value)}
                            />
                            <InputError message={form.errors.specialty} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="doctor-phone">Phone</Label>
                                <Input id="doctor-phone" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                                <InputError message={form.errors.phone} />
                            </div>
                            <div>
                                <Label htmlFor="doctor-commission">Commission %</Label>
                                <Input
                                    id="doctor-commission"
                                    type="number"
                                    min={0}
                                    max={100}
                                    value={form.data.commission_percent}
                                    onChange={(e) => form.setData('commission_percent', e.target.value)}
                                />
                                <InputError message={form.errors.commission_percent} />
                            </div>
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                            />
                            Active (available for referral selection)
                        </label>
                        <DialogFooter>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <LoaderCircle className="size-4 animate-spin" />}
                                {editing ? 'Save changes' : 'Add doctor'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
