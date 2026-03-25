import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type IctServiceRequest } from '@/types';
import { Head } from '@inertiajs/react';
import ICTRequestForm from '@/components/ict-request-form';
import SnapToLogBanner from '@/components/snap-to-log-banner';
import { Sparkles } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'New Service Request', href: '/dashboard/intake' },
];

export default function Intake() {
    const [extractedData, setExtractedData] = useState<Partial<IctServiceRequest> | null>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Service Request" />
            
            <div className="flex-1 space-y-12 p-3 md:p-6 lg:p-8 w-full">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-8 border-b border-border/50 pb-12 w-full">
                    <div className="flex items-center gap-7">
                        <div className="p-4 bg-primary/5 rounded-xl border border-primary/10 shadow-md transition-all hover:scale-105 duration-500 flex items-center justify-center">
                            <Sparkles className="size-10 text-primary animate-pulse" />
                        </div>
                        <div>
                            <h1 className="text-4xl md:text-5xl font-black text-foreground tracking-tighter leading-none">New Service Request</h1>
                            <p className="text-muted-foreground text-sm font-medium mt-3 w-full max-w-xl">Please fill out the form below to document a new ICT interaction.</p>
                        </div>
                    </div>
                    
                    <SnapToLogBanner onExtracted={setExtractedData} className="w-full md:w-auto" />
                </div>

                {/* Form Section */}
                <div className="relative w-full">
                    {/* Decorative Elements */}
                    <div className="absolute top-0 right-0 w-[600px] h-[600px] bg-primary/5 blur-[180px] -z-10 rounded-full animate-pulse"></div>
                    <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-zinc-500/5 dark:bg-zinc-500/2 blur-[180px] -z-10 rounded-full"></div>
                    
                    <ICTRequestForm isEdit={false} extractedData={extractedData} />
                </div>
            </div>
        </AppLayout>
    );
}

