import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useCan } from '@/hooks/use-can';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { ClipboardList, FilePlus2, FlaskConical, LayoutGrid, ShieldCheck, Stethoscope, TestTubes, Users } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: (NavItem & { permission?: string })[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'New Visit',
        url: '/visits/create',
        icon: FilePlus2,
        permission: 'visits.manage',
    },
    {
        title: 'Visits',
        url: '/visits',
        icon: ClipboardList,
        permission: 'visits.view',
    },
    {
        title: 'Worklist',
        url: '/worklist',
        icon: FlaskConical,
        permission: 'results.enter',
    },
    {
        title: 'Verification',
        url: '/verification',
        icon: ShieldCheck,
        permission: 'results.verify',
    },
    {
        title: 'Patients',
        url: '/patients',
        icon: Users,
        permission: 'patients.view',
    },
    {
        title: 'Doctors',
        url: '/doctors',
        icon: Stethoscope,
        permission: 'doctors.view',
    },
    {
        title: 'Tests',
        url: '/tests',
        icon: TestTubes,
        permission: 'catalog.view',
    },
];

export function AppSidebar() {
    const can = useCan();
    const items = mainNavItems.filter((item) => !item.permission || can(item.permission));

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
