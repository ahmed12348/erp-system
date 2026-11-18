export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

/** أدوار النظام — نفس قيم `App\Enums\ErpRole` */
export type ErpRole = 'admin' | 'accountant' | 'sales';

/** صلاحيات المستخدم الحالي — بتتبعت مع كل صفحة من `HandleInertiaRequests` */
export type ErpAbilities = {
    manageMasterData: boolean;
    quickAddProduct: boolean;
    manageUsers: boolean;
};

export type Auth = {
    user: User;
    role: ErpRole | null;
    can: ErpAbilities;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
