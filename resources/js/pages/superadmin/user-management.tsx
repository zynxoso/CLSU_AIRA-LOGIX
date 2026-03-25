import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { AlertTriangle, Pencil, Trash2, UserPlus, Shield, XCircle, CheckCircle2, Sparkles } from 'lucide-react';
import { useMemo, useState } from 'react';

interface AdminUser {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'super_admin';
    permissions: string[];
    created_at: string | null;
}

interface Props {
    users: AdminUser[];
    availablePermissions: string[];
}

interface PageProps extends Record<string, unknown> {
    flash?: {
        success?: string;
        error?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Superadmin Dashboard', href: '/superadmin/dashboard' },
    { title: 'User Management', href: '/superadmin/users' },
];

export default function UserManagement({ users, availablePermissions }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [editingUser, setEditingUser] = useState<AdminUser | null>(null);
    const [deletingUser, setDeletingUser] = useState<AdminUser | null>(null);

    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        permissions: ['dashboard', 'smart_scan', 'documentation'] as string[],
    });

    const editForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        permissions: [] as string[],
    });

    const deleteForm = useForm({
        confirmation: '',
    });

    const permissionLabels = useMemo(() => {
        return {
            dashboard: 'Dashboard',
            smart_scan: 'SMART Scan',
            documentation: 'Documentation',
            ai_consumption: 'AI Consumption',
        } as Record<string, string>;
    }, []);

    const togglePermission = (
        currentPermissions: string[],
        permission: string,
        setter: (permissions: string[]) => void,
    ) => {
        if (currentPermissions.includes(permission)) {
            setter(currentPermissions.filter((item) => item !== permission));
            return;
        }

        setter([...currentPermissions, permission]);
    };

    const startEdit = (user: AdminUser) => {
        setEditingUser(user);
        editForm.setData({
            name: user.name,
            email: user.email,
            password: '',
            password_confirmation: '',
            permissions: user.permissions,
        });
        editForm.clearErrors();
    };

    const cancelEdit = () => {
        setEditingUser(null);
        editForm.reset();
        editForm.clearErrors();
    };

    const closeDeleteDialog = () => {
        setDeletingUser(null);
        deleteForm.reset();
        deleteForm.clearErrors();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin User Management" />

            <div className="space-y-6 bg-gradient-to-b from-background to-muted/20 p-6">
                <div className="rounded-2xl border border-primary/20 bg-card p-6 shadow-sm">
                    <p className="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary">
                        <Shield className="size-3.5" /> Superadmin Only Area
                    </p>
                    <h1 className="mt-3 text-3xl font-bold tracking-tight">Admin User Management</h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Create admin accounts, update permissions, and safely remove admin access when needed.
                    </p>
                    <div className="mt-4 flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className="bg-background">{users.length} Admin Accounts</Badge>
                        <Badge variant="outline" className="bg-background">Permission-Based Access Control</Badge>
                    </div>
                </div>

                {flash?.success ? (
                    <div className="flex items-start gap-3 rounded-xl border border-emerald-300/40 bg-emerald-50 p-4 text-emerald-800">
                        <CheckCircle2 className="mt-0.5 size-5 shrink-0" />
                        <p className="text-sm font-medium">{flash.success}</p>
                    </div>
                ) : null}

                {flash?.error ? (
                    <div className="flex items-start gap-3 rounded-xl border border-rose-300/40 bg-rose-50 p-4 text-rose-800">
                        <XCircle className="mt-0.5 size-5 shrink-0" />
                        <p className="text-sm font-medium">{flash.error}</p>
                    </div>
                ) : null}

                <div className="rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <UserPlus className="size-5 text-primary" /> Create Admin
                    </h2>
                    <form
                        className="grid gap-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            createForm.post(route('superadmin.users.store'), {
                                preserveScroll: true,
                                onSuccess: () => createForm.reset('name', 'email', 'password', 'password_confirmation'),
                            });
                        }}
                    >
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Name</label>
                            <Input
                                value={createForm.data.name}
                                onChange={(event) => createForm.setData('name', event.target.value)}
                                placeholder="Enter full name"
                            />
                            {createForm.errors.name ? <p className="text-sm text-red-500">{createForm.errors.name}</p> : null}
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Email</label>
                            <Input
                                value={createForm.data.email}
                                onChange={(event) => createForm.setData('email', event.target.value)}
                                type="email"
                                placeholder="admin@company.com"
                            />
                            {createForm.errors.email ? <p className="text-sm text-red-500">{createForm.errors.email}</p> : null}
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Password</label>
                            <Input
                                value={createForm.data.password}
                                onChange={(event) => createForm.setData('password', event.target.value)}
                                type="password"
                                placeholder="Strong password"
                            />
                            {createForm.errors.password ? <p className="text-sm text-red-500">{createForm.errors.password}</p> : null}
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Confirm Password</label>
                            <Input
                                value={createForm.data.password_confirmation}
                                onChange={(event) => createForm.setData('password_confirmation', event.target.value)}
                                type="password"
                                placeholder="Retype password"
                            />
                        </div>

                        <div className="space-y-2 md:col-span-2">
                            <p className="text-sm font-medium">Permissions</p>
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                {availablePermissions.map((permission) => (
                                    <label key={permission} className="flex items-center gap-2 rounded-lg border bg-background px-3 py-2 text-sm">
                                        <Checkbox
                                            checked={createForm.data.permissions.includes(permission)}
                                            onCheckedChange={() =>
                                                togglePermission(createForm.data.permissions, permission, (permissions) =>
                                                    createForm.setData('permissions', permissions),
                                                )
                                            }
                                        />
                                        {permissionLabels[permission] ?? permission}
                                    </label>
                                ))}
                            </div>
                            {createForm.errors.permissions ? <p className="text-sm text-red-500">{createForm.errors.permissions}</p> : null}
                        </div>

                        <div className="flex items-center gap-3 md:col-span-2">
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? 'Creating...' : 'Create Admin'}
                            </Button>
                            <p className="text-xs text-muted-foreground">At least one permission is required.</p>
                        </div>
                    </form>
                </div>

                <div className="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div className="border-b px-6 py-4">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Sparkles className="size-5 text-primary" /> Admin Accounts
                        </h2>
                    </div>

                    <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-muted-foreground">
                            <tr>
                                <th className="px-6 py-3 text-left">Name</th>
                                <th className="px-6 py-3 text-left">Email</th>
                                <th className="px-6 py-3 text-left">Created</th>
                                <th className="px-6 py-3 text-left">Permissions</th>
                                <th className="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.length > 0 ? (
                                users.map((user) => (
                                    <tr key={user.id} className="border-t">
                                        <td className="px-6 py-3 font-medium">{user.name}</td>
                                        <td className="px-6 py-3">{user.email}</td>
                                        <td className="px-6 py-3 text-xs text-muted-foreground">
                                            {user.created_at ? new Date(user.created_at).toLocaleDateString() : '-'}
                                        </td>
                                        <td className="px-6 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                {user.permissions.map((permission) => (
                                                    <span key={`${user.id}-${permission}`} className="rounded-full border bg-background px-2 py-1 text-xs">
                                                        {permissionLabels[permission] ?? permission}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-6 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="outline" size="sm" onClick={() => startEdit(user)}>
                                                    <Pencil className="mr-1 size-4" /> Edit
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => setDeletingUser(user)}
                                                >
                                                    <Trash2 className="mr-1 size-4" /> Delete
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td className="px-6 py-10 text-center text-muted-foreground" colSpan={5}>
                                        No admin accounts found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                    </div>
                </div>

                <Dialog open={Boolean(editingUser)} onOpenChange={(open) => (!open ? cancelEdit() : null)}>
                    <DialogContent className="max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Edit Admin Account</DialogTitle>
                            <DialogDescription>
                                Update profile fields and permission access for {editingUser?.name}.
                            </DialogDescription>
                        </DialogHeader>

                        <form
                            className="grid gap-4 md:grid-cols-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                if (!editingUser) {
                                    return;
                                }

                                editForm.put(route('superadmin.users.update', editingUser.id), {
                                    preserveScroll: true,
                                    onSuccess: () => cancelEdit(),
                                });
                            }}
                        >
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Name</label>
                                <Input value={editForm.data.name} onChange={(event) => editForm.setData('name', event.target.value)} />
                                {editForm.errors.name ? <p className="text-sm text-red-500">{editForm.errors.name}</p> : null}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Email</label>
                                <Input
                                    value={editForm.data.email}
                                    onChange={(event) => editForm.setData('email', event.target.value)}
                                    type="email"
                                />
                                {editForm.errors.email ? <p className="text-sm text-red-500">{editForm.errors.email}</p> : null}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">New Password (optional)</label>
                                <Input
                                    value={editForm.data.password}
                                    onChange={(event) => editForm.setData('password', event.target.value)}
                                    type="password"
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Confirm New Password</label>
                                <Input
                                    value={editForm.data.password_confirmation}
                                    onChange={(event) => editForm.setData('password_confirmation', event.target.value)}
                                    type="password"
                                />
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <p className="text-sm font-medium">Permissions</p>
                                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                    {availablePermissions.map((permission) => (
                                        <label key={`edit-${permission}`} className="flex items-center gap-2 rounded-lg border bg-background px-3 py-2 text-sm">
                                            <Checkbox
                                                checked={editForm.data.permissions.includes(permission)}
                                                onCheckedChange={() =>
                                                    togglePermission(editForm.data.permissions, permission, (permissions) =>
                                                        editForm.setData('permissions', permissions),
                                                    )
                                                }
                                            />
                                            {permissionLabels[permission] ?? permission}
                                        </label>
                                    ))}
                                </div>
                                {editForm.errors.permissions ? <p className="text-sm text-red-500">{editForm.errors.permissions}</p> : null}
                            </div>

                            <DialogFooter className="md:col-span-2">
                                <Button type="button" variant="outline" onClick={cancelEdit}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={editForm.processing}>
                                    {editForm.processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog open={Boolean(deletingUser)} onOpenChange={(open) => (!open ? closeDeleteDialog() : null)}>
                    <DialogContent className="max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <AlertTriangle className="size-5 text-rose-500" /> Confirm Deletion
                            </DialogTitle>
                            <DialogDescription>
                                This action cannot be undone. This will permanently delete admin access for{' '}
                                <span className="font-semibold text-foreground">{deletingUser?.name}</span>.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="rounded-lg border border-amber-300/40 bg-amber-50/70 p-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                            <p className="flex items-start gap-2 text-sm font-medium text-amber-800 dark:text-amber-200">
                                <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                The user will no longer be able to log in as admin.
                            </p>
                        </div>

                        <div className="space-y-2">
                            <label htmlFor="delete-confirmation" className="text-sm font-medium">
                                Type <span className="font-semibold text-foreground">delete</span> to confirm
                            </label>
                            <Input
                                id="delete-confirmation"
                                value={deleteForm.data.confirmation}
                                onChange={(event) => deleteForm.setData('confirmation', event.target.value)}
                                placeholder="delete"
                                autoComplete="off"
                            />
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={closeDeleteDialog}>
                                Cancel
                            </Button>
                            <Button
                                variant="destructive"
                                disabled={
                                    deleteForm.processing ||
                                    !deletingUser ||
                                    deleteForm.data.confirmation.trim().toLowerCase() !== 'delete'
                                }
                                onClick={() => {
                                    if (!deletingUser) {
                                        return;
                                    }

                                    deleteForm.delete(route('superadmin.users.destroy', deletingUser.id), {
                                        preserveScroll: true,
                                        onSuccess: () => closeDeleteDialog(),
                                    });
                                }}
                            >
                                {deleteForm.processing ? 'Deleting...' : 'Delete Admin'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
