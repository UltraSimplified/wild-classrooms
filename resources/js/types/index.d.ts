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

/** A stored image's pixel size, so its space can be held before it loads. */
export interface ImageSize {
    width: number;
    height: number;
}

export interface MapPoint {
    id: number;
    latitude: number;
    longitude: number;
    picture: string | null;
    notes: string | null;
    spottedAt: string | null;
}

export interface SpeciesLink {
    id: number;
    title: string;
    slug: string;
    image: string | null;
}
