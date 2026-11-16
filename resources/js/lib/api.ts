export type ValidationErrors = Record<string, string[]>;

type ErrorPayload = {
    message?: string;
    errors?: ValidationErrors;
};

type HttpMethod = 'GET' | 'PATCH' | 'PUT' | 'DELETE';

type CsrfHeader = {
    name: 'X-CSRF-TOKEN' | 'X-XSRF-TOKEN';
    value: string;
};

const CSRF_TOKEN_URL = '/api/v1/csrf-token';

// Token hasil penyegaran disimpan di memori agar tidak perlu membaca DOM terus.
let cachedCsrf: CsrfHeader | null = null;

export class ApiError extends Error {
    public readonly status: number;

    public readonly errors: ValidationErrors;

    public constructor(
        message: string,
        status: number,
        errors: ValidationErrors = {},
    ) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }

    public get fieldErrors(): Record<string, string> {
        return Object.fromEntries(
            Object.entries(this.errors).map(([field, messages]) => [
                field,
                messages[0] ?? '',
            ]),
        );
    }
}

// Cookie XSRF-TOKEN selalu diperbarui server (mis. setelah login), jadi dipakai lebih dulu.
function csrfFromCookie(): CsrfHeader | null {
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith('XSRF-TOKEN='));

    if (!cookie) {
        return null;
    }

    const rawValue = cookie.split('=').slice(1).join('=');

    return {
        name: 'X-XSRF-TOKEN',
        value: decodeURIComponent(rawValue).replaceAll('"', ''),
    };
}

function csrfFromMeta(): CsrfHeader | null {
    const meta = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    );

    return meta?.content ? { name: 'X-CSRF-TOKEN', value: meta.content } : null;
}

function currentCsrf(): CsrfHeader | null {
    return cachedCsrf ?? csrfFromCookie() ?? csrfFromMeta();
}

// Ambil token baru dari server tanpa memuat ulang halaman.
async function refreshCsrf(): Promise<CsrfHeader> {
    const response = await fetch(CSRF_TOKEN_URL, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const payload = (await response.json().catch(() => null)) as {
        token?: string;
    } | null;

    if (!response.ok || !payload?.token) {
        throw new ApiError(
            'Gagal memperbarui token keamanan. Muat ulang halaman lalu coba lagi.',
            response.status,
        );
    }

    cachedCsrf = { name: 'X-CSRF-TOKEN', value: payload.token };

    return cachedCsrf;
}

async function request<T>(
    method: HttpMethod,
    url: string,
    body?: unknown,
    sudahMencobaUlang = false,
): Promise<T> {
    const csrf = currentCsrf();

    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrf) {
        headers[csrf.name] = csrf.value;
    }

    const options: RequestInit = {
        method,
        credentials: 'same-origin',
        headers,
    };

    if (body !== undefined) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);

    // Token kedaluwarsa (mis. login di tab lain): ambil token baru lalu coba sekali lagi.
    if (response.status === 419 && !sudahMencobaUlang) {
        await refreshCsrf();

        return request<T>(method, url, body, true);
    }

    if (response.status === 419) {
        throw new ApiError(
            'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.',
            419,
        );
    }

    // Sesi habis: arahkan ke halaman login.
    if (response.status === 401) {
        window.location.assign('/login');

        throw new ApiError(
            'Sesi Anda telah berakhir. Silakan login kembali.',
            401,
        );
    }

    if (response.status === 204) {
        return undefined as T;
    }

    const payload: unknown = await response.json().catch(() => null);

    if (!response.ok) {
        const data = payload as ErrorPayload | null;

        throw new ApiError(
            data?.message ?? 'Terjadi kesalahan pada server.',
            response.status,
            data?.errors ?? {},
        );
    }

    return payload as T;
}

export const api = {
    get: <T>(url: string): Promise<T> => request<T>('GET', url),

    patch: <T>(url: string, body: unknown): Promise<T> =>
        request<T>('PATCH', url, body),

    put: <T>(url: string, body: unknown): Promise<T> =>
        request<T>('PUT', url, body),

    delete: <T = null>(url: string, body?: unknown): Promise<T> =>
        request<T>('DELETE', url, body),
};
