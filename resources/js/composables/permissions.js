import { usePage } from "@inertiajs/vue3";

export function usePermissions() {

    const getUser = () => {
        return usePage().props.auth?.user || null;
    };

    const hasRole = (name) => {
        const user = usePage().props.auth?.user;
        return user?.roles?.includes(name) || false;
    };

    const hasPermission = (name) => {
        const user = usePage().props.auth?.user;
        return user?.permissions?.includes(name) || false;
    };

    return {
        getUser,
        hasRole,
        hasPermission,
    };
}
