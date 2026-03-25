export interface IctServiceRequest {
    id: number;
    control_no?: string;
    timestamp_str?: string;
    client_feedback_no?: string;
    
    // Client Info
    name: string;
    position?: string;
    office_unit?: string;
    contact_no?: string;
    
    // Request Details
    date_of_request?: string;
    requested_completion_date?: string;
    request_type?: string;
    location_venue?: string;
    request_description?: string;
    
    // Action & Personnel
    received_by?: string;
    receive_date_time?: string;
    action_taken?: string;
    recommendation_conclusion?: string;
    status: 'Open' | 'In Progress' | 'Resolved' | 'Escalated';
    
    // Completion tracking
    date_time_started?: string;
    date_time_completed?: string;
    conducted_by?: string;
    noted_by?: string;
    
    created_at: string;
    updated_at: string;
}

export type IctFormData = Partial<Omit<IctServiceRequest, 'id' | 'created_at' | 'updated_at'>>;
