import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import { FileText, Save, Loader2, Users, ClipboardList } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { type MisoAccomplishment } from '@/types';
import { cn } from '@/lib/utils';

interface MisoRequestFormProps {
    request?: MisoAccomplishment;
    onClose?: () => void;
    isEdit?: boolean;
    extractedData?: Partial<MisoAccomplishment> | null;
    category: MisoAccomplishment['category'];
    tab: string;
}

const CATEGORY_LABELS: Record<MisoAccomplishment['category'], string> = {
    data_management: 'MISO Accomplishments Data',
    network: 'Network / Cybersec / Tech Support',
    systems_development: 'Systems Development / QA',
};

export default function MisoRequestForm({ request, onClose, isEdit: propIsEdit, extractedData, category, tab }: MisoRequestFormProps) {
    const isEdit = propIsEdit ?? !!request;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        category: request?.category || category,
        record_no: request?.record_no || '',
        project_title: request?.project_title || '',
        project_lead: request?.project_lead || '',
        project_members: request?.project_members || '',
        budget_cost: request?.budget_cost || '',
        implementing_unit: request?.implementing_unit || '',
        target_activities: request?.target_activities || '',
        intended_duration: request?.intended_duration || '',
        start_date: request?.start_date || '',
        target_end_date: request?.target_end_date || '',
        reporting_period: request?.reporting_period || '',
        completion_percentage: request?.completion_percentage || '',
        overall_status: request?.overall_status || 'Pending',
        remarks: request?.remarks || '',
    });

    React.useEffect(() => {
        setData('category', category);
    }, [category]);

    React.useEffect(() => {
        if (!extractedData) return;

        const newData: Record<string, string> = {};
        const formKeys = new Set(Object.keys(data) as Array<keyof typeof data>);

        for (const [rawKey, rawValue] of Object.entries(extractedData)) {
            const key = rawKey as keyof typeof data;
            if (!formKeys.has(key)) continue;

            newData[key] = typeof rawValue === 'string' ? rawValue : rawValue == null ? '' : String(rawValue);
        }

        if (Object.keys(newData).length > 0) {
            setData((currentData) => ({ ...currentData, ...newData, category }));
        }
    }, [extractedData, category]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit && request) {
            put(route('miso.update', request.id), {
                onSuccess: () => onClose?.(),
            });

            return;
        }

        post(route('miso.store'), {
            onSuccess: () => {
                reset();
                onClose?.();
            },
        });
    };

    const sectionHeader = (icon: React.ReactNode, title: string) => (
        <div className="flex items-center gap-2 mb-6 pb-2 border-b border-border">
            <div className="text-primary [&>svg]:size-4">
                {icon}
            </div>
            <h3 className="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">{title}</h3>
        </div>
    );

    const inputLabel = (label: string, required?: boolean) => (
        <Label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mb-1.5 block">
            {label} {required && <span className="text-rose-500">*</span>}
        </Label>
    );

    const inputClasses = 'bg-card border-border focus:ring-primary/20 text-sm font-medium h-9 rounded-md shadow-none transition-colors';
    const textareaClasses = 'bg-card border-border focus:ring-primary/20 text-sm font-medium rounded-md shadow-none transition-colors resize-none';

    return (
        <div className={cn('w-full mx-auto', onClose && 'max-h-[80vh] overflow-y-auto px-1 pr-3')}>
            <form onSubmit={handleSubmit} className="space-y-6">
                <input type="hidden" name="category" value={data.category} />

                <Card className="bg-card border-border rounded-lg shadow-sm">
                    <CardContent className="p-6">
                        {sectionHeader(<ClipboardList />, 'MISO Category')}
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div className="md:col-span-12">
                                {inputLabel('Category')}
                                <Input value={CATEGORY_LABELS[data.category as MisoAccomplishment['category']] || data.category} disabled className={inputClasses} />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-card border-border rounded-lg shadow-sm">
                    <CardContent className="p-6">
                        {sectionHeader(<FileText />, 'Project Information')}
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div className="md:col-span-3">
                                {inputLabel('Record No.')}
                                <Input
                                    value={data.record_no}
                                    onChange={(e) => setData('record_no', e.target.value)}
                                    placeholder="No."
                                    className={cn(inputClasses, 'font-bold text-primary')}
                                />
                            </div>
                            <div className="md:col-span-9">
                                {inputLabel('Project Title', true)}
                                <Input
                                    value={data.project_title}
                                    onChange={(e) => setData('project_title', e.target.value)}
                                    placeholder="Enter project title..."
                                    required
                                    className={inputClasses}
                                />
                                {errors.project_title && <p className="text-[10px] font-bold text-rose-500 uppercase mt-1">{errors.project_title}</p>}
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Project Lead')}
                                <Input
                                    value={data.project_lead}
                                    onChange={(e) => setData('project_lead', e.target.value)}
                                    placeholder="Lead person"
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Implementing Unit')}
                                <Input
                                    value={data.implementing_unit}
                                    onChange={(e) => setData('implementing_unit', e.target.value)}
                                    placeholder="Office / Unit"
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Reporting Period')}
                                <Input
                                    value={data.reporting_period}
                                    onChange={(e) => setData('reporting_period', e.target.value)}
                                    placeholder="e.g. Q2 2026"
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Completion Percentage')}
                                <Input
                                    value={data.completion_percentage}
                                    onChange={(e) => setData('completion_percentage', e.target.value)}
                                    placeholder="e.g. 75%"
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Budget / Cost')}
                                <Input
                                    value={data.budget_cost}
                                    onChange={(e) => setData('budget_cost', e.target.value)}
                                    placeholder="Budget details"
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Intended Duration')}
                                <Input
                                    value={data.intended_duration}
                                    onChange={(e) => setData('intended_duration', e.target.value)}
                                    placeholder="e.g. 6 months"
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Start Date')}
                                <Input
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    placeholder="Start date"
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6">
                                {inputLabel('Target End Date')}
                                <Input
                                    value={data.target_end_date}
                                    onChange={(e) => setData('target_end_date', e.target.value)}
                                    placeholder="Target end date"
                                    className={inputClasses}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-card border-border rounded-lg shadow-sm">
                    <CardContent className="p-6">
                        {sectionHeader(<Users />, 'Accomplishment Logs')}
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div className="md:col-span-6">
                                {inputLabel('Overall Status')}
                                <Select value={data.overall_status || 'Pending'} onValueChange={(val) => setData('overall_status', val)}>
                                    <SelectTrigger className={inputClasses}>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Pending">Pending</SelectItem>
                                        <SelectItem value="On Track">On Track</SelectItem>
                                        <SelectItem value="In Progress">In Progress</SelectItem>
                                        <SelectItem value="Completed">Completed</SelectItem>
                                        <SelectItem value="Delayed">Delayed</SelectItem>
                                        <SelectItem value="Cancelled">Cancelled</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="md:col-span-12">
                                {inputLabel('Project Members')}
                                <Textarea
                                    value={data.project_members}
                                    onChange={(e) => setData('project_members', e.target.value)}
                                    rows={3}
                                    placeholder="Team members involved"
                                    className={cn(textareaClasses, 'p-4')}
                                />
                            </div>
                            <div className="md:col-span-12">
                                {inputLabel('Target Activities')}
                                <Textarea
                                    value={data.target_activities}
                                    onChange={(e) => setData('target_activities', e.target.value)}
                                    rows={4}
                                    placeholder="Key activities and milestones"
                                    className={cn(textareaClasses, 'p-4')}
                                />
                            </div>
                            <div className="md:col-span-12">
                                {inputLabel('Remarks / Actual Accomplishments')}
                                <Textarea
                                    value={data.remarks}
                                    onChange={(e) => setData('remarks', e.target.value)}
                                    rows={4}
                                    placeholder="Actual accomplishments and remarks"
                                    className={cn(textareaClasses, 'p-4')}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex flex-col sm:flex-row justify-end items-center gap-4 pt-6 sticky bottom-0 bg-background/95 backdrop-blur-md py-6 border-t border-border mt-10 z-20">
                    {onClose ? (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={onClose}
                            className="text-xs font-bold text-muted-foreground uppercase tracking-widest h-9 px-6 rounded-md"
                        >
                            Cancel
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            variant="ghost"
                            asChild
                            className="text-xs font-bold text-muted-foreground uppercase tracking-widest h-9 px-6 rounded-md"
                        >
                            <Link href={route('dashboard', { tab })}>Back</Link>
                        </Button>
                    )}
                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full sm:w-auto bg-primary text-primary-foreground hover:bg-primary/90 text-xs font-bold uppercase tracking-widest h-9 px-8 rounded-md shadow-sm"
                    >
                        {processing ? (
                            <>
                                <Loader2 className="mr-2 size-4 animate-spin" /> saving...
                            </>
                        ) : (
                            <>
                                <Save className="mr-2 size-4" />
                                {isEdit ? 'Update Record' : 'Save Record'}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
}
