import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type IctServiceRequest } from '@/types';
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
import { Pencil, Trash2, ShieldAlert } from 'lucide-react';
import React, { useState, useEffect } from 'react';
import { DiagramPreview } from '../components/DiagramPreview';

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

interface DashboardProps {
    requests: {
        data: IctServiceRequest[];
        links: PaginationLink[];
    };
    filters: {
        search?: string;
        status?: string;
        archived?: string | boolean | null;
        type?: string;
        requester?: string;
    };
}

export default function Dashboard({ requests, filters }: DashboardProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // Keep searchTerm in sync with filters.search (e.g., after pagination)
    useEffect(() => {
        setSearchTerm(filters.search || '');
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
            router.delete(route('ict.destroy', confirmAction.id), { preserveState: true, preserveScroll: true });
        } else if (confirmAction.type === 'restore') {
            router.post(route('ict.restore', confirmAction.id), {}, { preserveState: true, preserveScroll: true });
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
        const params: Record<string, any> = { search: searchTerm };
        if (selectedIds.length > 0) {
            params.ids = selectedIds.join(',');
        }
        return format === 'csv' 
            ? route('ict.export-csv', params) 
            : route('ict.export-xlsx', params);
    };
    
    // Auto-search logic (simple debounce)
    // Only trigger search when user types, not after pagination/filter changes
    const [isUserTyping, setIsUserTyping] = useState(false);
    useEffect(() => {
        if (!isUserTyping) return;
        const timer = setTimeout(() => {
            if (searchTerm !== filters.search) {
                router.get('/dashboard', { search: searchTerm, status: filters.status }, { preserveState: true, replace: true });
            }
            setIsUserTyping(false);
        }, 500);
        return () => clearTimeout(timer);
    }, [searchTerm, filters.search, filters.status, isUserTyping]);

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ICT Dashboard" />
            
            <div className="flex flex-col gap-6 p-6 bg-background min-h-full">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <div className="relative w-64">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-500" />
                            <Input 
                                placeholder="Search records..." 
                                className="pl-9 h-10 bg-card border-border text-foreground focus:ring-accent"
                                value={searchTerm}
                                onChange={(e) => { setSearchTerm(e.target.value); setIsUserTyping(true); }}
                            />
                        </div>
                        
                        <Select defaultValue="all">
                            <SelectTrigger className="w-36 h-10 bg-card border-border text-muted-foreground">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border text-popover-foreground">
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem value="technical">Technical Support</SelectItem>
                                <SelectItem value="account">User Account</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select defaultValue="all">
                            <SelectTrigger className="w-36 h-10 bg-card border-border text-muted-foreground">
                                <SelectValue placeholder="All Status" />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border text-popover-foreground">
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select defaultValue="all">
                            <SelectTrigger className="w-40 h-10 bg-card border-border text-muted-foreground">
                                <SelectValue placeholder="All Requesters" />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border text-popover-foreground">
                                <SelectItem value="all">All Requesters</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button 
                            variant="outline" 
                            className={cn(
                                "h-10 transition-colors uppercase tracking-widest text-[10px] font-bold px-4",
                                filters.archived
                                    ? "bg-primary/10 border-primary/30 text-primary hover:bg-primary/20"
                                    : "bg-card border-border text-muted-foreground hover:bg-muted"
                            )}
                            onClick={() => {
                                const newFilters = { ...filters, archived: filters.archived ? null : true };
                                router.get('/dashboard', newFilters, { preserveState: true });
                            }}
                        >
                            {filters.archived ? <FileArchive className="mr-2 size-4" /> : <Archive className="mr-2 size-4" />}
                            {filters.archived ? 'Active Records' : 'Archive'}
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

                        
                        <Button 
                            className="h-10 bg-primary text-primary-foreground hover:bg-primary/90 font-bold px-6"
                            asChild
                        >
                            <Link href={route('ict.create')}>
                                <Plus className="mr-2 size-4" /> Add Record
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Table Section */}
                <div className="rounded-lg border border-border bg-card overflow-hidden">
                    <div className="overflow-x-auto no-scrollbar">
                        <table className="w-full text-sm text-left border-collapse">
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
                            <tbody className="divide-y divide-border/30">
                                {requests.data.length > 0 ? requests.data.map((req) => {
                                    const statusStyle = getStatusStyles(req.status);
                                    return (
                                        <tr key={req.id} className={cn("hover:bg-muted/50 transition-colors group", selectedIds.includes(req.id) && "bg-muted/30")}>
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
                                                    {req.request_type}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <div className={cn("size-2 rounded-full", statusStyle.dot)} />
                                                    <span className={cn("font-bold tracking-tight", statusStyle.color)}>
                                                        {req.status}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="font-bold text-foreground">{req.name || 'William Harris'}</div>
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground font-medium">
                                                {req.office_unit || 'IT Services'}
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground font-mono text-[11px]">
                                                {req.date_of_request
                                                    ? new Date(req.date_of_request).toLocaleDateString('en-CA') // YYYY-MM-DD
                                                    : '2026-01-31'}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon" className="size-8 text-muted-foreground hover:text-foreground hover:bg-muted">
                                                            <MoreHorizontal className="size-4 rotate-90" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="bg-popover border-border text-popover-foreground w-40">
                                                        {!filters.archived ? (
                                                            <>
                                                                <DropdownMenuItem 
                                                                    asChild
                                                                    className="hover:bg-muted cursor-pointer flex items-center gap-2"
                                                                >
                                                                    <Link href={route('ict.edit', req.id)}>
                                                                        <Pencil className="size-3 text-primary" /> Edit Record
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem 
                                                                    asChild
                                                                    className="hover:bg-muted cursor-pointer flex items-center gap-2 text-foreground focus:text-primary"
                                                                >
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
                                }) : (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-20 text-center">
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
                            if (filters.search) params.append('search', filters.search);
                            if (filters.status) params.append('status', filters.status);
                            params.append('type', filters.type || '');
                            params.append('requester', filters.requester || '');
                            if (filters.archived) params.append('archived', '1');
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
                            {confirmAction.type === 'delete' && 'Permanently Delete Record'}
                            {confirmAction.type === 'archive' && 'Archive Record'}
                            {confirmAction.type === 'restore' && 'Restore Record'}
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
