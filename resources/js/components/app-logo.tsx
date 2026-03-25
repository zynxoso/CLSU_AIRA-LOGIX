import { useSidebar } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';

export default function AppLogo() {
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    return (
        <div className="flex items-center gap-2 overflow-hidden">
            <div className={cn(
                "flex shrink-0 items-center justify-center rounded-md bg-sidebar-accent/30 p-1 transition-all duration-300",
                isCollapsed ? "size-8 ml-[-1px]" : "size-9"
            )}>
                <img src="/clsu-logo-green.png" alt="CLSU-ICT Logo" className="h-full w-full object-contain" />
            </div>
            <div className={cn(
                "grid flex-1 text-left text-sm transition-all duration-300 ease-in-out",
                isCollapsed ? "w-0 opacity-0 overflow-hidden" : "w-auto opacity-100"
            )}>
                <span className="mb-0.5 truncate leading-none font-semibold">CLSU-ICT</span>
            </div>
        </div>
    );
}
