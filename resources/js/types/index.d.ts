export interface Member {
    id: number;
    title: string;
    slug: string;
}

export interface AuthGroup {
    id: number;
    title: string;
    slug: string;
    members: Member[];
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    isAdmin: boolean;
}

export interface PageProps {
    auth: {
        user: AuthUser | null;
        group: AuthGroup | null;
    };
    currentMonth: {
        number: number;
        name: string;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}
