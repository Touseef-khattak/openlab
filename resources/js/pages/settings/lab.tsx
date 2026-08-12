import { type BreadcrumbItem, type LabSettings } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Lab branding', href: '/settings/lab' }];

interface BrandingForm {
    name: string;
    logo: string | null;
    remove_logo: boolean;
    address: string;
    phone: string;
    email: string;
    report_header: string;
    report_footer: string;
    receipt_footer: string;
    [key: string]: unknown;
}

export default function LabBranding({ settings }: { settings: LabSettings }) {
    const fileRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(settings.logo);

    const { data, setData, put, errors, processing, recentlySuccessful } = useForm<BrandingForm>({
        name: settings.name ?? '',
        logo: null,
        remove_logo: false,
        address: settings.address ?? '',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        report_header: settings.report_header ?? '',
        report_footer: settings.report_footer ?? '',
        receipt_footer: settings.receipt_footer ?? '',
    });

    const pickLogo = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            const dataUri = reader.result as string;
            setPreview(dataUri);
            setData((current) => ({ ...current, logo: dataUri, remove_logo: false }));
        };
        reader.readAsDataURL(file);
    };

    const clearLogo = () => {
        setPreview(null);
        setData((current) => ({ ...current, logo: null, remove_logo: true }));
        if (fileRef.current) fileRef.current.value = '';
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put('/settings/lab', { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lab branding" />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Lab branding" description="Name, logo and details printed on reports and receipts" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label>Logo</Label>
                            <div className="flex items-center gap-4">
                                <div className="bg-muted flex size-20 items-center justify-center overflow-hidden rounded-md border">
                                    {preview ? (
                                        <img src={preview} alt="Lab logo" className="max-h-full max-w-full object-contain" />
                                    ) : (
                                        <span className="text-muted-foreground text-xs">No logo</span>
                                    )}
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Input ref={fileRef} type="file" accept="image/*" onChange={pickLogo} className="max-w-xs" />
                                    {preview && (
                                        <button type="button" onClick={clearLogo} className="text-muted-foreground text-left text-xs underline">
                                            Remove logo
                                        </button>
                                    )}
                                </div>
                            </div>
                            <InputError message={errors.logo} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Lab name</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="address">Address</Label>
                            <Input id="address" value={data.address} onChange={(e) => setData('address', e.target.value)} />
                            <InputError message={errors.address} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="report_header">Report header line</Label>
                            <Input
                                id="report_header"
                                value={data.report_header}
                                placeholder="e.g. Clinical Laboratory Report"
                                onChange={(e) => setData('report_header', e.target.value)}
                            />
                            <InputError message={errors.report_header} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="report_footer">Report footer note</Label>
                            <Input
                                id="report_footer"
                                value={data.report_footer}
                                onChange={(e) => setData('report_footer', e.target.value)}
                            />
                            <InputError message={errors.report_footer} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="receipt_footer">Receipt footer note</Label>
                            <Input
                                id="receipt_footer"
                                value={data.receipt_footer}
                                onChange={(e) => setData('receipt_footer', e.target.value)}
                            />
                            <InputError message={errors.receipt_footer} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>
                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Saved</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
