export type ApiResponse<T> = {
    data: T;
    message?: string;
};

export type RoleProfileField = {
    key: string;
    label: string;
    type: 'text' | 'url' | 'number' | 'textarea';
    required: boolean;
    value: string | number | null;
};

export type RoleProfile = {
    role: string;
    role_label: string;
    fields: RoleProfileField[];
};
