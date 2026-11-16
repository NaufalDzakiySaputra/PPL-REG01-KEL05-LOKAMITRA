export type User = {
    id: number;
    name: string;
    email: string;
    nomor_telepon?: string | null;
    role: string;
    role_label?: string;
    status_akun?: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
