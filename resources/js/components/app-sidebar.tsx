import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, FileText, Cpu, Users, Shield, BarChart3 } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;
    const isSuperAdmin = user?.role === 'super_admin';
    const permissions = user?.permissions ?? [];

    const hasPermission = (permission: string): boolean => {
        return isSuperAdmin || permissions.includes(permission);
    };

    const mainNavItems: NavItem[] = [];

    if (isSuperAdmin) {
        mainNavItems.push({
            title: 'Superadmin Panel',
            url: '/superadmin/dashboard',
            icon: Shield,
        });
        mainNavItems.push({
            title: 'User Management',
            url: '/superadmin/users',
            icon: Users,
        });
    }

    if (hasPermission('dashboard')) {
        mainNavItems.push({
            title: 'Dashboard',
            url: '/dashboard',
            icon: LayoutGrid,
        });

        mainNavItems.push({
            title: 'Reports & Analytics',
            url: '/dashboard/reports',
            icon: BarChart3,
        });
    }

    if (hasPermission('smart_scan')) {
        mainNavItems.push({
            title: 'SMART Scan',
            url: '/dashboard/smart-scan',
            icon: FileText,
        });
    }

    if (hasPermission('ai_consumption')) {
        mainNavItems.push({
            title: 'AI Consumption',
            url: '/dashboard/ai-consumption',
            icon: Cpu,
        });
    }

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            url: 'https://github.com/zynxoso/CLSU_AIRA-LOGIX',
            icon: Folder,
        },
    ];

    if (hasPermission('documentation')) {
        footerNavItems.push({
            title: 'Documentation',
            url: '/dashboard/documentation',
            icon: BookOpen,
        });
    }

    const homeHref = isSuperAdmin ? '/superadmin/dashboard' : '/dashboard';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
