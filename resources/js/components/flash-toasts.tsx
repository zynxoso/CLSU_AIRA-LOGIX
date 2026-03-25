import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

interface FlashToastsProps {
    flash?: {
        success?: string;
        error?: string;
    };
}

export function FlashToasts({ flash }: FlashToastsProps) {
    const lastSuccess = useRef<string | null>(null);
    const lastError = useRef<string | null>(null);

    const successMessage = typeof flash?.success === 'string' ? flash.success : null;
    const errorMessage = typeof flash?.error === 'string' ? flash.error : null;

    useEffect(() => {
        if (successMessage && successMessage !== lastSuccess.current) {
            toast.success(successMessage, {
                duration: 3500,
            });
            lastSuccess.current = successMessage;
        }
    }, [successMessage]);

    useEffect(() => {
        if (errorMessage && errorMessage !== lastError.current) {
            toast.error(errorMessage, {
                duration: 4500,
            });
            lastError.current = errorMessage;
        }
    }, [errorMessage]);

    return null;
}
