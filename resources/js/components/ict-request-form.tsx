import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import { 
    User, 
    FileText, 
    Users, 
    Save,
    Loader2
} from 'lucide-react';
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
} from "@/components/ui/select";
import { Card, CardContent } from '@/components/ui/card';
import { type IctServiceRequest } from '@/types';
import { cn } from '@/lib/utils';

interface ICTRequestFormProps {
    request?: IctServiceRequest;
    onClose?: () => void;
    isEdit?: boolean;
    extractedData?: Partial<IctServiceRequest> | null;
}

export default function ICTRequestForm({ request, onClose, isEdit: propIsEdit, extractedData }: ICTRequestFormProps) {
    const isEdit = propIsEdit ?? !!request;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        control_no: request?.control_no || '',
        name: request?.name || '',
        position: request?.position || '',
        office_unit: request?.office_unit || '',
        contact_no: request?.contact_no || '',
        request_type: request?.request_type || '',
        date_of_request: request?.date_of_request || new Date().toISOString().split('T')[0],
        requested_completion_date: request?.requested_completion_date || '',
        location_venue: request?.location_venue || '',
        request_description: request?.request_description || '',
        status: request?.status || 'Open',
        conducted_by: request?.conducted_by || '',
        noted_by: request?.noted_by || '',
        action_taken: request?.action_taken || '',
        recommendation_conclusion: request?.recommendation_conclusion || '',
        client_feedback_no: request?.client_feedback_no || '',
        received_by: request?.received_by || '',
        receive_date_time: request?.receive_date_time || '',
        date_time_started: request?.date_time_started || '',
        date_time_completed: request?.date_time_completed || '',
    });

    React.useEffect(() => {
        if (!extractedData) return;

        // Inertia useForm's setData should be called once with an object to prevent race conditions
        const newData: Record<string, any> = {};
        const formKeys = new Set(Object.keys(data) as Array<keyof typeof data>);

        for (const [rawKey, rawValue] of Object.entries(extractedData)) {
            const key = rawKey as keyof typeof data;
            if (!formKeys.has(key)) continue;

            newData[key] = typeof rawValue === 'string' ? rawValue : rawValue == null ? '' : String(rawValue);
        }
        
        if (Object.keys(newData).length > 0) {
            setData(currentData => ({ ...currentData, ...newData }));
        }
    }, [extractedData]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit && request) {
            put(route('ict.update', request.id), {
                onSuccess: () => onClose?.(),
            });
        } else {
            post(route('ict.store'), {
                onSuccess: () => {
                    reset();
                    onClose?.();
                },
            });
        }
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

    const inputClasses = "bg-card border-border focus:ring-primary/20 text-sm font-medium h-9 rounded-md shadow-none transition-colors";
    const textareaClasses = "bg-card border-border focus:ring-primary/20 text-sm font-medium rounded-md shadow-none transition-colors resize-none";

    return (
        <div className={cn("w-full mx-auto", onClose && "max-h-[80vh] overflow-y-auto px-1 pr-3")}>
            <form onSubmit={handleSubmit} className="space-y-6">
                
                {/* Section: Requester Information */}
                <Card className="bg-card border-border rounded-lg shadow-sm">
                    <CardContent className="p-6">
                        {sectionHeader(<User />, "Requester Identification")}
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div className="md:col-span-4 lg:col-span-3">
                                {inputLabel("Control Number")}
                                <Input 
                                    value={data.control_no}
                                    onChange={e => setData('control_no', e.target.value)}
                                    placeholder="Ref #" 
                                    className={cn(inputClasses, "font-bold text-primary")}
                                />
                                {errors.control_no && <p className="text-[10px] font-bold text-rose-500 uppercase mt-1">{errors.control_no}</p>}
                            </div>
                            <div className="md:col-span-8 lg:col-span-9">
                                {inputLabel("Name", true)}
                                <Input 
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                    placeholder="Enter name..." 
                                    required
                                    className={inputClasses}
                                />
                                {errors.name && <p className="text-[10px] font-bold text-rose-500 uppercase mt-1">{errors.name}</p>}
                            </div>
                            <div className="md:col-span-6 lg:col-span-4">
                                {inputLabel("Position")}
                                <Input 
                                    value={data.position}
                                    onChange={e => setData('position', e.target.value)}
                                    placeholder="e.g. Technical Staff" 
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-6 lg:col-span-4">
                                {inputLabel("Office/Unit", true)}
                                <Input 
                                    value={data.office_unit}
                                    onChange={e => setData('office_unit', e.target.value)}
                                    placeholder="e.g. Office of the Registrar" 
                                    required
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-12 lg:col-span-4">
                                {inputLabel("Contact No.")}
                                <Input 
                                    value={data.contact_no}
                                    onChange={e => setData('contact_no', e.target.value)}
                                    placeholder="Mobile / Email" 
                                    className={inputClasses}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Section: Service Details */}
                <Card className="bg-card border-border rounded-lg shadow-sm">
                    <CardContent className="p-6">
                        {sectionHeader(<FileText />, "Service Specifications")}
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div className="md:col-span-6 lg:col-span-4">
                                {inputLabel("Type of Request", true)}
                                <Select value={data.request_type} onValueChange={(val) => setData('request_type', val)}>
                                    <SelectTrigger className={inputClasses}>
                                        <SelectValue placeholder="Identify Type..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ICT Technical Support">ICT Technical Support (Hardware/Software/Network Issue)</SelectItem>
                                        <SelectItem value="System Development/Enhancement">System Development/Enhancement</SelectItem>
                                        <SelectItem value="Network/Internet Connection">Network/Internet Connection</SelectItem>
                                        <SelectItem value="Others">Others, please specify</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.request_type && <p className="text-[10px] font-bold text-rose-500 uppercase mt-1">{errors.request_type}</p>}
                            </div>
                            <div className="md:col-span-3 lg:col-span-4">
                                {inputLabel("Date of Request", true)}
                                <Input 
                                    type="date"
                                    value={data.date_of_request}
                                    onChange={e => setData('date_of_request', e.target.value)}
                                    required
                                    className={cn(inputClasses, "relative block w-full dark:[&::-webkit-calendar-picker-indicator]:invert dark:[&::-webkit-calendar-picker-indicator]:opacity-60 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-3 cursor-pointer")}
                                />
                            </div>
                            <div className="md:col-span-3 lg:col-span-4">
                                {inputLabel("Requested Date of Completion")}
                                <Input 
                                    type="date"
                                    value={data.requested_completion_date}
                                    onChange={e => setData('requested_completion_date', e.target.value)}
                                    className={cn(inputClasses, "relative block w-full dark:[&::-webkit-calendar-picker-indicator]:invert dark:[&::-webkit-calendar-picker-indicator]:opacity-60 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-3 cursor-pointer")}
                                />
                            </div>
                            <div className="md:col-span-12">
                                {inputLabel("Location / Venue", true)}
                                <Input 
                                    value={data.location_venue}
                                    onChange={e => setData('location_venue', e.target.value)}
                                    placeholder="Specific room or venue..." 
                                    required
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-12">
                                {inputLabel("Brief Description of Request", true)}
                                <Textarea 
                                    value={data.request_description}
                                    onChange={e => setData('request_description', e.target.value)}
                                    rows={5}
                                    placeholder="Describe the service required..." 
                                    required
                                    className={cn(textareaClasses, "p-4")}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Section: Support Details */}
                <Card className="bg-card border-border rounded-lg shadow-sm">
                    <CardContent className="p-6">
                        {sectionHeader(<Users />, "Support Logs")}
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div className="md:col-span-4">
                                {inputLabel("Status", true)}
                                <Select value={data.status} onValueChange={(val) => setData('status', val as any)}>
                                    <SelectTrigger className={inputClasses}>
                                        <SelectValue placeholder="Status..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Open">Open</SelectItem>
                                        <SelectItem value="In Progress">In Progress</SelectItem>
                                        <SelectItem value="Resolved">Resolved</SelectItem>
                                        <SelectItem value="Escalated">Escalated</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="md:col-span-4">
                                {inputLabel("Conducted By")}
                                <Input 
                                    value={data.conducted_by}
                                    onChange={e => setData('conducted_by', e.target.value)}
                                    placeholder="Staff name..."
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-4">
                                {inputLabel("Noted By")}
                                <Input 
                                    value={data.noted_by}
                                    onChange={e => setData('noted_by', e.target.value)}
                                    placeholder="Supervisor..."
                                    className={inputClasses}
                                />
                            </div>
                            <div className="md:col-span-12">
                                {inputLabel("Action Taken")}
                                <Textarea 
                                    value={data.action_taken}
                                    onChange={e => setData('action_taken', e.target.value)}
                                    rows={4}
                                    placeholder="Steps taken to resolve..."
                                    className={cn(textareaClasses, "p-4")}
                                />
                            </div>
                            <div className="md:col-span-12">
                                {inputLabel("Recommendation/Conclusion")}
                                <Textarea 
                                    value={data.recommendation_conclusion}
                                    onChange={e => setData('recommendation_conclusion', e.target.value)}
                                    rows={4}
                                    placeholder="Future recommendations..."
                                    className={cn(textareaClasses, "p-4")}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Footer Actions */}
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
                            <Link href="/dashboard">Back</Link>
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
                                {isEdit ? "Update Record" : "Save Record"}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
}

