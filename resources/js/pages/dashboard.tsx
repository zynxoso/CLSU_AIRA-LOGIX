import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type IctServiceRequest, type MisoAccomplishment } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import {
    Plus,
    Search,
    MoreHorizontal,
    Archive,
    FileSpreadsheet,
    FileArchive,
    RefreshCcw,
    LayoutGrid,
    Zap,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Pencil, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

type DashboardRow = IctServiceRequest | MisoAccomplishment;

interface DashboardTab {
    key: string;
    label: string;
}

interface FilterMeta {
    searchPlaceholder: string;
    typeLabel: string;
    requesterLabel: string;
    statusLabel: string;
}

interface DashboardProps {
    requests: {
        data: DashboardRow[];
        links: PaginationLink[];
    };
    filters: {
        tab?: string;
        search?: string;
        status?: string;
        archived?: string | boolean | null;
        type?: string;
        requester?: string;
    };
    availableStatuses: string[];
    availableTypes: string[];
    requesters: string[];
    tabs: DashboardTab[];
    activeTab: string;
    filterMeta: FilterMeta;
}

export default function Dashboard({
    requests,
    filters,
    availableStatuses,
    availableTypes,
    requesters,
    tabs,
    activeTab,
    filterMeta,
}: DashboardProps) {
    const isIctTab = activeTab === 'ict';
    const archivedEnabled = filters.archived === true || filters.archived === '1' || filters.archived === 'true';
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // Keep searchTerm in sync with filters.search (e.g., after pagination)
    useEffect(() => {
        setSearchTerm(filters.search || '');
        setSelectedIds([]);
    }, [filters.search]);

    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    const [confirmAction, setConfirmAction] = useState<{ isOpen: boolean; type: 'delete' | 'archive' | 'restore'; id: number | null }>({
        isOpen: false,
        type: 'archive',
        id: null
    });

    const handleDelete = (id: number) => setConfirmAction({ isOpen: true, type: 'delete', id });
    const handleArchive = (id: number) => setConfirmAction({ isOpen: true, type: 'archive', id });
    const handleRestore = (id: number) => setConfirmAction({ isOpen: true, type: 'restore', id });

    const executeConfirmAction = () => {
        if (!confirmAction.id) return;

        if (confirmAction.type === 'delete' || confirmAction.type === 'archive') {
            router.delete(
                isIctTab ? route('ict.destroy', confirmAction.id) : route('miso.destroy', confirmAction.id),
                { preserveState: true, preserveScroll: true }
            );
        } else if (confirmAction.type === 'restore') {
            router.post(
                isIctTab ? route('ict.restore', confirmAction.id) : route('miso.restore', confirmAction.id),
                {},
                { preserveState: true, preserveScroll: true }
            );
        }

        setConfirmAction({ ...confirmAction, isOpen: false });
    };

    const toggleAll = () => {
        if (selectedIds.length === requests.data.length && requests.data.length > 0) {
            setSelectedIds([]);
        } else {
            setSelectedIds(requests.data.map(r => r.id));
        }
    };

    const toggleSelect = (id: number) => {
        setSelectedIds(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);
    };

    const getExportUrl = (format: 'csv' | 'xlsx') => {
        const params: Record<string, string> = { tab: activeTab };

        if (searchTerm) {
            params.search = searchTerm;
        }

        if (filters.status) {
            params.status = filters.status;
        }

        if (filters.type) {
            params.type = filters.type;
        }

        if (filters.requester) {
            params.requester = filters.requester;
        }

        if (archivedEnabled) {
            params.archived = '1';
        }

        if (selectedIds.length > 0) {
            params.ids = selectedIds.join(',');
        }

        if (isIctTab) {
            return format === 'csv'
                ? route('ict.export-csv', params)
                : route('ict.export-xlsx', params);
        }

        return format === 'csv'
            ? route('miso.export-csv', params)
            : route('miso.export-xlsx', params);
    };

    const handleTabChange = (tabKey: string) => {
        if (tabKey === activeTab) return;

        setSelectedIds([]);
        router.get('/dashboard', { tab: tabKey }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const handleFilterChange = (key: string, value: string | null) => {
        const newFilters = { ...filters, tab: activeTab, [key]: value === 'all' ? null : value };
        // If we change filters, we reset page
        router.get('/dashboard', newFilters, { preserveState: true });
    };

    // Auto-search logic (simple debounce)
    const [isUserTyping, setIsUserTyping] = useState(false);
    useEffect(() => {
        if (!isUserTyping) return;
        const timer = setTimeout(() => {
            if (searchTerm !== (filters.search || '')) {
                router.get('/dashboard', { ...filters, tab: activeTab, search: searchTerm }, { preserveState: true, replace: true });
            }
            setIsUserTyping(false);
        }, 500);
        return () => clearTimeout(timer);
    }, [searchTerm, filters, isUserTyping, activeTab]);

    const getStatusStyles = (status: string) => {
        switch (status) {
            case 'Completed':
            case 'Resolved':
                return { color: 'text-primary font-bold', dot: 'bg-primary' };
            case 'Pending':
            case 'Open':
                return { color: 'text-primary/60 font-bold', dot: 'bg-primary/40' };
            case 'In Progress':
                return { color: 'text-zinc-400', dot: 'border-2 border-zinc-500 border-t-transparent animate-spin' };
            case 'Cancelled':
                return { color: 'text-rose-500', dot: 'bg-rose-500' };
            default:
                return { color: 'text-zinc-500', dot: 'bg-zinc-500' };
        }
    };

    const colSpan = isIctTab ? 8 : 9;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ICT Dashboard" />

            <div className="flex flex-col gap-6 p-6 bg-background min-h-full">
                <div className="flex flex-wrap items-center gap-2">
                    {tabs.map((tab) => (
                        <Button
                            key={tab.key}
                            variant="outline"
                            className={cn(
                                'h-9 px-4 text-[11px] uppercase tracking-widest font-bold',
                                tab.key === activeTab
                                    ? 'bg-primary text-primary-foreground border-primary hover:bg-primary/90'
                                    : 'bg-card border-border text-muted-foreground hover:bg-muted hover:text-foreground'
                            )}
                            onClick={() => handleTabChange(tab.key)}
                        >
                            {tab.key === activeTab && <LayoutGrid className="mr-2 size-3.5" />}
                            {tab.label}
                        </Button>
                    ))}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <div className="relative w-64">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-500" />
                            <Input
                                placeholder={filterMeta.searchPlaceholder}
                                className="pl-9 h-10 bg-card border-border text-foreground focus:ring-accent"
                                value={searchTerm}
                                onChange={(e) => { setSearchTerm(e.target.value); setIsUserTyping(true); }}
                            />
                        </div>

                        <Select
                            value={filters.type || 'all'}
                            onValueChange={(v) => handleFilterChange('type', v)}
                        >
                            <SelectTrigger className="w-44 h-10 bg-card border-border text-muted-foreground">
                                <SelectValue placeholder={`All ${filterMeta.typeLabel}`} />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border text-popover-foreground">
                                <SelectItem value="all">All {filterMeta.typeLabel}</SelectItem>
                                {availableTypes.map(type => (
                                    <SelectItem key={type} value={type}>{type}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.status || 'all'}
                            onValueChange={(v) => handleFilterChange('status', v)}
                        >
                            <SelectTrigger className="w-40 h-10 bg-card border-border text-muted-foreground">
                                <SelectValue placeholder={`All ${filterMeta.statusLabel}`} />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border text-popover-foreground">
                                <SelectItem value="all">All {filterMeta.statusLabel}</SelectItem>
                                {availableStatuses.map(status => (
                                    <SelectItem key={status} value={status}>{status}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.requester || 'all'}
                            onValueChange={(v) => handleFilterChange('requester', v)}
                        >
                            <SelectTrigger className="w-52 h-10 bg-card border-border text-muted-foreground">
                                <SelectValue placeholder={`All ${filterMeta.requesterLabel}`} />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border text-popover-foreground">
                                <SelectItem value="all">All {filterMeta.requesterLabel}</SelectItem>
                                {requesters.map(req => (
                                    <SelectItem key={req} value={req}>{req}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Button
                            variant="outline"
                            className={cn(
                                "h-10 transition-colors uppercase tracking-widest text-[10px] font-bold px-4",
                                archivedEnabled
                                    ? "bg-primary/10 border-primary/30 text-primary hover:bg-primary/20"
                                    : "bg-card border-border text-muted-foreground hover:bg-muted"
                            )}
                            onClick={() => {
                                const newFilters = { ...filters, tab: activeTab, archived: archivedEnabled ? null : true };
                                router.get('/dashboard', newFilters, { preserveState: true });
                            }}
                        >
                            {archivedEnabled ? <FileArchive className="mr-2 size-4" /> : <Archive className="mr-2 size-4" />}
                            {archivedEnabled ? 'Active Records' : 'Archive'}
                        </Button>
                    </div>

                    <div className="flex items-center gap-2">
                        <div className="flex items-center gap-1 bg-card border border-border p-1 rounded-md">
                            <Button variant="ghost" size="sm" className="h-8 text-[10px] font-bold text-muted-foreground hover:text-foreground hover:bg-muted uppercase tracking-tighter" asChild>
                                <a href={getExportUrl('csv')}>
                                    <FileArchive className="mr-1 size-3 text-muted-foreground" /> CSV
                                </a>
                            </Button>
                            <Button variant="ghost" size="sm" className="h-8 text-[10px] font-bold text-primary hover:text-primary/80 uppercase tracking-tighter hover:bg-primary/10" asChild>
                                <a href={getExportUrl('xlsx')}>
                                    <FileSpreadsheet className="mr-1 size-3 text-primary" /> XLSX
                                </a>
                            </Button>
                        </div>

                        {isIctTab ? (
                            <Button
                                className="h-10 bg-primary text-primary-foreground hover:bg-primary/90 font-bold px-6"
                                asChild
                            >
                                <Link href={route('ict.create')}>
                                    <Plus className="mr-2 size-4" /> Add Record
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button
                                    variant="outline"
                                    className="h-10 border-border bg-card text-muted-foreground hover:bg-muted font-bold px-4"
                                    asChild
                                >
                                    <Link href={route('miso.smart-scan', { tab: activeTab })}>
                                        <Zap className="mr-2 size-4" /> Import + Extract
                                    </Link>
                                </Button>
                                <Button
                                    className="h-10 bg-primary text-primary-foreground hover:bg-primary/90 font-bold px-6"
                                    asChild
                                >
                                    <Link href={route('miso.create', { tab: activeTab })}>
                                        <Plus className="mr-2 size-4" /> Add Record
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Table Section */}
                <div className="rounded-lg border border-border bg-card overflow-hidden">
                    <div className="overflow-x-auto no-scrollbar">
                        <table className="w-full text-sm text-left border-collapse">
                            {isIctTab ? (
                                <thead>
                                    <tr className="border-b border-border text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/30">
                                        <th className="px-6 py-5 text-center w-12">
                                            <Checkbox
                                                className="border-border"
                                                checked={requests.data.length > 0 && selectedIds.length === requests.data.length}
                                                onCheckedChange={toggleAll}
                                            />
                                        </th>
                                        <th className="px-6 py-5">Control No.</th>
                                        <th className="px-6 py-5">Type of Request</th>
                                        <th className="px-6 py-5">Status</th>
                                        <th className="px-6 py-5">Name</th>
                                        <th className="px-6 py-5">Office/Unit</th>
                                        <th className="px-6 py-5">Date of Request</th>
                                        <th className="px-6 py-5 text-right"></th>
                                    </tr>
                                </thead>
                            ) : (
                                <thead>
                                    <tr className="border-b border-border text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/30">
                                        <th className="px-6 py-5 text-center w-12">
                                            <Checkbox
                                                className="border-border"
                                                checked={requests.data.length > 0 && selectedIds.length === requests.data.length}
                                                onCheckedChange={toggleAll}
                                            />
                                        </th>
                                        <th className="px-6 py-5">No.</th>
                                        <th className="px-6 py-5">Project Title</th>
                                        <th className="px-6 py-5">Project Lead</th>
                                        <th className="px-6 py-5">Implementing Unit</th>
                                        <th className="px-6 py-5">Reporting Period</th>
                                        <th className="px-6 py-5">Completion</th>
                                        <th className="px-6 py-5">Status</th>
                                        <th className="px-6 py-5 text-right"></th>
                                    </tr>
                                </thead>
                            )}

                            <tbody className="divide-y divide-border/30">
                                {requests.data.length > 0 ? requests.data.map((row) => {
                                    if (isIctTab) {
                                        const req = row as IctServiceRequest;
                                        const statusStyle = getStatusStyles(req.status);

                                        return (
                                            <tr key={req.id} className={cn('hover:bg-muted/50 transition-colors group', selectedIds.includes(req.id) && 'bg-muted/30')}>
                                                <td className="px-6 py-4 text-center">
                                                    <Checkbox
                                                        className="border-border bg-transparent"
                                                        checked={selectedIds.includes(req.id)}
                                                        onCheckedChange={() => toggleSelect(req.id)}
                                                    />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <Link href={`/dashboard/requests/${req.id}`} className="font-bold text-primary hover:text-primary/80 transition-colors tracking-tight">
                                                        {req.control_no || `ICT-${new Date().getFullYear()}-${String(req.id).padStart(4, '0')}`}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <Badge variant="outline" className="bg-muted/50 border-border text-muted-foreground font-medium py-1 px-3 rounded-full">
                                                        {req.request_type || 'Unspecified'}
                                                    </Badge>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <div className={cn('size-2 rounded-full', statusStyle.dot)} />
                                                        <span className={cn('font-bold tracking-tight', statusStyle.color)}>
                                                            {req.status}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="font-bold text-foreground">{req.name || '-'}</div>
                                                </td>
                                                <td className="px-6 py-4 text-muted-foreground font-medium">
                                                    {req.office_unit || '-'}
                                                </td>
                                                <td className="px-6 py-4 text-muted-foreground font-mono text-[11px]">
                                                    {req.date_of_request
                                                        ? new Date(req.date_of_request).toLocaleDateString('en-CA')
                                                        : '-'}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" className="size-8 text-muted-foreground hover:text-foreground hover:bg-muted">
                                                                <MoreHorizontal className="size-4 rotate-90" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end" className="bg-popover border-border text-popover-foreground w-44">
                                                            {!archivedEnabled ? (
                                                                <>
                                                                    <DropdownMenuItem asChild className="hover:bg-muted cursor-pointer flex items-center gap-2">
                                                                        <Link href={route('ict.edit', req.id)}>
                                                                            <Pencil className="size-3 text-primary" /> Edit Record
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem asChild className="hover:bg-muted cursor-pointer flex items-center gap-2 text-foreground focus:text-primary">
                                                                        <a href={route('ict.download', req.id)}>
                                                                            <Archive className="size-3" /> Download DOCX
                                                                        </a>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        className="hover:bg-muted cursor-pointer flex items-center gap-2 text-amber-500 focus:text-amber-600"
                                                                        onClick={() => handleArchive(req.id)}
                                                                    >
                                                                        <FileArchive className="size-3" /> Archive
                                                                    </DropdownMenuItem>
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <DropdownMenuItem
                                                                        className="hover:bg-muted cursor-pointer flex items-center gap-2 text-emerald-500 focus:text-emerald-600"
                                                                        onClick={() => handleRestore(req.id)}
                                                                    >
                                                                        <RefreshCcw className="size-3" /> Restore Record
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        className="hover:bg-muted cursor-pointer flex items-center gap-2 text-rose-500 focus:text-rose-600"
                                                                        onClick={() => handleDelete(req.id)}
                                                                    >
                                                                        <Trash2 className="size-3" /> Permanently Delete
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </td>
                                            </tr>
                                        );
                                    }

                                    const req = row as MisoAccomplishment;
                                    const status = req.overall_status || 'Unspecified';
                                    const statusStyle = getStatusStyles(status);

                                    return (
                                        <tr key={req.id} className={cn('hover:bg-muted/50 transition-colors group', selectedIds.includes(req.id) && 'bg-muted/30')}>
                                            <td className="px-6 py-4 text-center">
                                                <Checkbox
                                                    className="border-border bg-transparent"
                                                    checked={selectedIds.includes(req.id)}
                                                    onCheckedChange={() => toggleSelect(req.id)}
                                                />
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground font-mono text-[11px]">
                                                {req.record_no || '-'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="font-bold text-foreground max-w-[28rem] truncate" title={req.project_title || ''}>
                                                    {req.project_title || '-'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground font-medium">
                                                {req.project_lead || '-'}
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground font-medium">
                                                {req.implementing_unit || '-'}
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground font-mono text-[11px]">
                                                {req.reporting_period || '-'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge variant="outline" className="bg-muted/50 border-border text-muted-foreground font-medium py-1 px-3 rounded-full">
                                                    {req.completion_percentage || '-'}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <div className={cn('size-2 rounded-full', statusStyle.dot)} />
                                                    <span className={cn('font-bold tracking-tight', statusStyle.color)}>{status}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon" className="size-8 text-muted-foreground hover:text-foreground hover:bg-muted">
                                                            <MoreHorizontal className="size-4 rotate-90" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="bg-popover border-border text-popover-foreground w-44">
                                                        {!archivedEnabled ? (
                                                            <>
                                                                <DropdownMenuItem asChild className="hover:bg-muted cursor-pointer flex items-center gap-2">
                                                                    <Link href={route('miso.edit', req.id)}>
                                                                        <Pencil className="size-3 text-primary" /> Edit Record
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem asChild className="hover:bg-muted cursor-pointer flex items-center gap-2 text-foreground focus:text-primary">
                                                                    <a href={route('miso.download', req.id)}>
                                                                        <Archive className="size-3" /> Download DOCX
                                                                    </a>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem
                                                                    className="hover:bg-muted cursor-pointer flex items-center gap-2 text-amber-500 focus:text-amber-600"
                                                                    onClick={() => handleArchive(req.id)}
                                                                >
                                                                    <FileArchive className="size-3" /> Archive
                                                                </DropdownMenuItem>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <DropdownMenuItem
                                                                    className="hover:bg-muted cursor-pointer flex items-center gap-2 text-emerald-500 focus:text-emerald-600"
                                                                    onClick={() => handleRestore(req.id)}
                                                                >
                                                                    <RefreshCcw className="size-3" /> Restore Record
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem
                                                                    className="hover:bg-muted cursor-pointer flex items-center gap-2 text-rose-500 focus:text-rose-600"
                                                                    onClick={() => handleDelete(req.id)}
                                                                >
                                                                    <Trash2 className="size-3" /> Permanently Delete
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </td>
                                        </tr>
                                    );
                                }) : (
                                    <tr>
                                        <td colSpan={colSpan} className="px-6 py-20 text-center">
                                            <div className="flex flex-col items-center gap-2">
                                                <Search className="size-10 text-zinc-800" />
                                                <span className="text-zinc-600 font-bold uppercase tracking-widest text-xs">No records found</span>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Pagination */}
                {requests.links && requests.links.length > 3 && (
                    <div className="flex items-center justify-center gap-2 mt-4 mb-2">
                        {requests.links.map((link, i) => {
                            // Build query string with all filters
                            const params = new URLSearchParams();
                            params.append('tab', activeTab);
                            if (filters.search) params.append('search', filters.search);
                            if (filters.status) params.append('status', filters.status);
                            if (filters.type) params.append('type', filters.type);
                            if (filters.requester) params.append('requester', filters.requester);
                            if (archivedEnabled) params.append('archived', '1');
                            // If link.url already has ?, append with &, else with ?
                            let href = link.url;
                            if (href && params.toString()) {
                                href += (href.includes('?') ? '&' : '?') + params.toString();
                            }
                            return (
                                <Link
                                    key={i}
                                    href={href || '#'}
                                    className={cn(
                                        "px-3 py-1.5 text-xs font-bold uppercase tracking-widest rounded-md transition-colors outline-none focus-visible:ring-1 focus-visible:ring-primary",
                                        !link.url && "opacity-50 cursor-not-allowed pointer-events-none text-zinc-500",
                                        link.active ? "bg-primary text-primary-foreground" : "text-muted-foreground bg-muted/30 hover:bg-muted/60 hover:text-foreground"
                                    )}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    preserveState
                                    preserveScroll
                                />
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Confirmation Dialog */}
            <Dialog
                open={confirmAction.isOpen}
                onOpenChange={(open) => !open && setConfirmAction({ ...confirmAction, isOpen: false })}
            >
                <DialogContent className="sm:max-w-md border-border bg-card text-foreground">
                    <DialogHeader>
                        <DialogTitle>
                            {confirmAction.type === 'delete' && `Permanently Delete ${isIctTab ? 'ICT Record' : 'MISO Record'}`}
                            {confirmAction.type === 'archive' && `Archive ${isIctTab ? 'ICT Record' : 'MISO Record'}`}
                            {confirmAction.type === 'restore' && `Restore ${isIctTab ? 'ICT Record' : 'MISO Record'}`}
                        </DialogTitle>
                        <DialogDescription>
                            {confirmAction.type === 'delete' && 'Are you sure you want to permanently delete this record? This action cannot be undone.'}
                            {confirmAction.type === 'archive' && 'Are you sure you want to archive this record? It will be hidden from the active requests list.'}
                            {confirmAction.type === 'restore' && 'Are you sure you want to restore this record? It will appear back in the active requests list.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="sm:justify-end gap-2 mt-4">
                        <Button
                            variant="outline"
                            onClick={() => setConfirmAction({ ...confirmAction, isOpen: false })}
                            className="bg-card hover:bg-muted text-foreground border-border"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant={confirmAction.type === 'delete' ? 'destructive' : 'default'}
                            onClick={executeConfirmAction}
                            className={cn(
                                confirmAction.type === 'archive' && 'bg-amber-600 hover:bg-amber-700 text-white',
                                confirmAction.type === 'restore' && 'bg-emerald-600 hover:bg-emerald-700 text-white'
                            )}
                        >
                            Confirm
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
