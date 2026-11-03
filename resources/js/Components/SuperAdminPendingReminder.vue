<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
} from 'vue'

import { usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import i18n from '@/i18n'

const t = (...args) => i18n.global.t(...args)

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

/*
| Après combien de minutes refaire un rappel ?
|
| Exemple :
| 30 = toutes les 30 minutes
| 60 = toutes les heures
| 15 = toutes les 15 minutes
*/

const REMINDER_INTERVAL_MINUTES = 2

/*
| Combien de secondes la notification reste affichée ?
|
| Exemple :
| 8  = 8 secondes
| 12 = 12 secondes
| 20 = 20 secondes
*/

const NOTIFICATION_DURATION_SECONDS = 20

/*
| Position :
|
| top-end    = en haut à droite
| bottom-end = en bas à droite
| top-start  = en haut à gauche
| bottom-start = en bas à gauche
*/

const NOTIFICATION_POSITION = 'top-end'

/*
|--------------------------------------------------------------------------
| Calcul automatique
|--------------------------------------------------------------------------
*/

const REMINDER_INTERVAL =
    REMINDER_INTERVAL_MINUTES *
    60 *
    1000

const NOTIFICATION_DURATION =
    NOTIFICATION_DURATION_SECONDS *
    1000

/*
|--------------------------------------------------------------------------
| Internal scheduler
|--------------------------------------------------------------------------
|
| Le composant vérifie chaque minute si les 30 minutes sont passées.
|
| IMPORTANT :
| Cela ne veut pas dire qu'il appelle Laravel chaque minute.
|
| Laravel est appelé uniquement lorsque REMINDER_INTERVAL est dépassé.
|
*/

const SCHEDULER_INTERVAL =
    60 * 1000

const page = usePage()

let intervalId = null
let retryTimeoutId = null

/*
|--------------------------------------------------------------------------
| Current Super Admin
|--------------------------------------------------------------------------
*/

const currentUserId = computed(() => {
    return (
        page.props.auth?.user?.id ||
        'unknown'
    )
})

/*
|--------------------------------------------------------------------------
| LocalStorage Keys
|--------------------------------------------------------------------------
*/

const lastCheckKey = computed(() => {
    return (
        'lms_super_admin_pending_last_check_' +
        currentUserId.value
    )
})

const tabLockKey = computed(() => {
    return (
        'lms_super_admin_pending_lock_' +
        currentUserId.value
    )
})

/*
|--------------------------------------------------------------------------
| Get localStorage timestamp
|--------------------------------------------------------------------------
*/

const getStoredTimestamp = key => {
    const value = Number(
        localStorage.getItem(key) || 0
    )

    return Number.isFinite(value)
        ? value
        : 0
}

/*
|--------------------------------------------------------------------------
| Should check server?
|--------------------------------------------------------------------------
*/

const shouldCheck = () => {
    const lastCheck =
        getStoredTimestamp(
            lastCheckKey.value
        )

    return (
        Date.now() - lastCheck >=
        REMINDER_INTERVAL
    )
}

/*
|--------------------------------------------------------------------------
| Save last check
|--------------------------------------------------------------------------
*/

const saveLastCheck = () => {
    localStorage.setItem(
        lastCheckKey.value,
        String(Date.now())
    )
}

/*
|--------------------------------------------------------------------------
| Multiple tabs protection
|--------------------------------------------------------------------------
*/

const acquireTabLock = () => {
    const now = Date.now()

    const currentLock =
        getStoredTimestamp(
            tabLockKey.value
        )

    /*
    | Si un autre onglet a lancé la vérification
    | pendant les 10 dernières secondes,
    | cet onglet ne fait rien.
    */

    if (
        currentLock &&
        now - currentLock < 10000
    ) {
        return false
    }

    localStorage.setItem(
        tabLockKey.value,
        String(now)
    )

    return true
}

const releaseTabLock = () => {
    localStorage.removeItem(
        tabLockKey.value
    )
}

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchPendingActions = async () => {
    try {
        const response = await fetch(
            route(
                'super-admin.pending-reminders'
            ),
            {
                method: 'GET',

                credentials:
                    'same-origin',

                headers: {
                    Accept:
                        'application/json',

                    'X-Requested-With':
                        'XMLHttpRequest',
                },
            }
        )

        if (
            response.status === 401 ||
            response.status === 403
        ) {
            return null
        }

        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}`
            )
        }

        return await response.json()
    } catch (error) {
        console.error(
            'Super Admin reminder error:',
            error
        )

        return null
    }
}

/*
|--------------------------------------------------------------------------
| Build Notification HTML
|--------------------------------------------------------------------------
*/

const buildNotificationHtml = data => {
    const companyRequests =
        Number(
            data.summary
                ?.company_requests || 0
        )

    const individualRequests =
        Number(
            data.summary
                ?.individual_requests || 0
        )

    const individualCourses =
        Number(
            data.summary
                ?.individual_courses || 0
        )

    let rows = ''

    /*
    |--------------------------------------------------------------------------
    | Company Requests
    |--------------------------------------------------------------------------
    */

    if (companyRequests > 0) {
        rows += `
            <div class="lms-reminder-row">

                <div class="lms-reminder-row-left">

                    <div
                        class="
                            lms-reminder-row-icon
                            company
                        "
                    >
                        <i
                            class="
                                ri-building-line
                            "
                        ></i>
                    </div>

                    <span>
                        ${t('ui-company-requests')}
                    </span>

                </div>

                <span
                    class="
                        lms-reminder-badge
                    "
                >
                    ${companyRequests}
                </span>

            </div>
        `
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Requests
    |--------------------------------------------------------------------------
    */

    if (individualRequests > 0) {
        rows += `
            <div class="lms-reminder-row">

                <div class="lms-reminder-row-left">

                    <div
                        class="
                            lms-reminder-row-icon
                            individual
                        "
                    >
                        <i
                            class="
                                ri-user-line
                            "
                        ></i>
                    </div>

                    <span>
                        ${t('ui-individual-requests')}
                    </span>

                </div>

                <span
                    class="
                        lms-reminder-badge
                    "
                >
                    ${individualRequests}
                </span>

            </div>
        `
    }

    /*
    |--------------------------------------------------------------------------
    | Pending individual courses
    |--------------------------------------------------------------------------
    */

    let coursesInfo = ''

    if (individualCourses > 0) {
        coursesInfo = `
            <div
                class="
                    lms-reminder-course-info
                "
            >
                <i
                    class="
                        ri-book-open-line
                    "
                ></i>

                <span>
                    ${t('ui-pending-individual-courses', { count: individualCourses })}
                </span>
            </div>
        `
    }

    return `
        <div
            class="
                lms-reminder-content
            "
        >

            <div
                class="
                    lms-reminder-header
                "
            >

                <div
                    class="
                        lms-reminder-main-icon
                    "
                >
                    <i
                        class="
                            ri-notification-3-line
                        "
                    ></i>
                </div>

                <div
                    class="
                        lms-reminder-header-text
                    "
                >

                    <strong>
                        ${t('ui-pending-actions')}
                    </strong>

                    <span>
                        ${t('ui-pending-items-attention', { count: data.total })}
                    </span>

                </div>

            </div>

            <div
                class="
                    lms-reminder-list
                "
            >
                ${rows}
            </div>

            ${coursesInfo}

        </div>
    `
}

/*
|--------------------------------------------------------------------------
| Show professional notification
|--------------------------------------------------------------------------
*/

const showReminder = async data => {
    /*
    |--------------------------------------------------------------------------
    | Don't replace another SweetAlert
    |--------------------------------------------------------------------------
    */

    if (Swal.isVisible()) {
        return false
    }

    await Swal.fire({
        /*
        |--------------------------------------------------------------------------
        | Toast mode
        |--------------------------------------------------------------------------
        */

        toast: true,

        /*
        |--------------------------------------------------------------------------
        | Position
        |--------------------------------------------------------------------------
        */

        position:
            NOTIFICATION_POSITION,

        /*
        |--------------------------------------------------------------------------
        | Custom content
        |--------------------------------------------------------------------------
        */

        html:
            buildNotificationHtml(
                data
            ),

        /*
        |--------------------------------------------------------------------------
        | No big confirmation button
        |--------------------------------------------------------------------------
        */

        showConfirmButton: false,

        /*
        |--------------------------------------------------------------------------
        | Small close X
        |--------------------------------------------------------------------------
        */

        showCloseButton: true,

        /*
        |--------------------------------------------------------------------------
        | Automatic close
        |--------------------------------------------------------------------------
        */

        timer:
            NOTIFICATION_DURATION,

        timerProgressBar: true,

        /*
        |--------------------------------------------------------------------------
        | Size
        |--------------------------------------------------------------------------
        */

        width: 390,

        padding: 0,

        /*
        |--------------------------------------------------------------------------
        | No dark backdrop
        |--------------------------------------------------------------------------
        */

        backdrop: false,

        /*
        |--------------------------------------------------------------------------
        | Custom classes
        |--------------------------------------------------------------------------
        */

        customClass: {
            popup:
                'lms-pending-toast',

            htmlContainer:
                'lms-pending-toast-html',

            closeButton:
                'lms-pending-toast-close',

            timerProgressBar:
                'lms-pending-toast-progress',
        },

        /*
        |--------------------------------------------------------------------------
        | Pause timer on hover
        |--------------------------------------------------------------------------
        */

        didOpen: toast => {
            toast.addEventListener(
                'mouseenter',
                () => {
                    Swal.stopTimer()
                }
            )

            toast.addEventListener(
                'mouseleave',
                () => {
                    Swal.resumeTimer()
                }
            )
        },
    })

    return true
}

/*
|--------------------------------------------------------------------------
| Retry if another SweetAlert is already open
|--------------------------------------------------------------------------
*/

const retryLater = () => {
    if (retryTimeoutId) {
        window.clearTimeout(
            retryTimeoutId
        )
    }

    retryTimeoutId =
        window.setTimeout(
            () => {
                checkPendingActions(
                    true
                )
            },
            15000
        )
}

/*
|--------------------------------------------------------------------------
| Check pending actions
|--------------------------------------------------------------------------
*/

const checkPendingActions =
    async (force = false) => {
        /*
        |--------------------------------------------------------------------------
        | Don't call server before reminder interval
        |--------------------------------------------------------------------------
        */

        if (
            !force &&
            !shouldCheck()
        ) {
            return
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate requests between tabs
        |--------------------------------------------------------------------------
        */

        if (!acquireTabLock()) {
            return
        }

        try {
            const data =
                await fetchPendingActions()

            /*
            |--------------------------------------------------------------------------
            | API problem
            |--------------------------------------------------------------------------
            */

            if (!data) {
                return
            }

            /*
            |--------------------------------------------------------------------------
            | Nothing pending
            |--------------------------------------------------------------------------
            */

            if (!data.has_pending) {
                saveLastCheck()

                return
            }

            /*
            |--------------------------------------------------------------------------
            | Another SweetAlert currently opened
            |--------------------------------------------------------------------------
            */

            if (Swal.isVisible()) {
                retryLater()

                return
            }

            /*
            |--------------------------------------------------------------------------
            | Display notification
            |--------------------------------------------------------------------------
            */

            const displayed =
                await showReminder(data)

            if (displayed) {
                saveLastCheck()
            }
        } finally {
            releaseTabLock()
        }
    }

/*
|--------------------------------------------------------------------------
| Browser visibility
|--------------------------------------------------------------------------
|
| Si le Super Admin revient dans l'onglet après plus de 30 minutes,
| on vérifie immédiatement.
|
*/

const handleVisibilityChange = () => {
    if (
        document.visibilityState ===
        'visible'
    ) {
        checkPendingActions()
    }
}

/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(() => {
    /*
    |--------------------------------------------------------------------------
    | First check
    |--------------------------------------------------------------------------
    */

    checkPendingActions()

    /*
    |--------------------------------------------------------------------------
    | Internal scheduler
    |--------------------------------------------------------------------------
    |
    | Il tourne chaque minute mais le serveur n'est appelé
    | que lorsque REMINDER_INTERVAL est atteint.
    |
    */

    intervalId =
        window.setInterval(
            () => {
                checkPendingActions()
            },
            SCHEDULER_INTERVAL
        )

    /*
    |--------------------------------------------------------------------------
    | Visibility
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'visibilitychange',
        handleVisibilityChange
    )
})

/*
|--------------------------------------------------------------------------
| Unmount
|--------------------------------------------------------------------------
*/

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(
            intervalId
        )

        intervalId = null
    }

    if (retryTimeoutId) {
        window.clearTimeout(
            retryTimeoutId
        )

        retryTimeoutId = null
    }

    document.removeEventListener(
        'visibilitychange',
        handleVisibilityChange
    )
})
</script>

<template>
    <span
        class="d-none"
        aria-hidden="true"
    ></span>
</template>

<style>
/*
|--------------------------------------------------------------------------
| SweetAlert professional toast
|--------------------------------------------------------------------------
*/

.lms-pending-toast {
    padding: 0 !important;

    border: 1px solid #e4edf4 !important;

    border-radius: 14px !important;

    background: #ffffff !important;

    box-shadow:
        0 14px 35px
        rgba(7, 17, 38, 0.14) !important;

    overflow: hidden !important;
}

/*
|--------------------------------------------------------------------------
| Remove default SweetAlert spacing
|--------------------------------------------------------------------------
*/

.lms-pending-toast-html {
    width: 100% !important;

    margin: 0 !important;

    padding: 0 !important;
}

/*
|--------------------------------------------------------------------------
| Content
|--------------------------------------------------------------------------
*/

.lms-reminder-content {
    width: 100%;

    padding:
        16px
        17px
        13px;

    text-align: left;
}

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

.lms-reminder-header {
    display: flex;

    align-items: center;

    gap: 11px;

    padding-right: 22px;

    margin-bottom: 13px;
}

.lms-reminder-main-icon {
    width: 40px;

    height: 40px;

    min-width: 40px;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #37cced,
            #0d8fe9
        );

    color: #ffffff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    box-shadow:
        0 5px 14px
        rgba(
            13,
            143,
            233,
            0.20
        );
}

.lms-reminder-header-text {
    min-width: 0;

    display: flex;

    flex-direction: column;
}

.lms-reminder-header-text strong {
    color: #071126;

    font-size: 14px;

    line-height: 1.3;

    font-weight: 700;
}

.lms-reminder-header-text span {
    margin-top: 2px;

    color: #7c8a9a;

    font-size: 10.5px;

    line-height: 1.35;
}

/*
|--------------------------------------------------------------------------
| List
|--------------------------------------------------------------------------
*/

.lms-reminder-list {
    border:
        1px solid
        #e8eff4;

    border-radius: 10px;

    overflow: hidden;
}

.lms-reminder-row {
    min-height: 45px;

    padding:
        7px
        10px;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 10px;

    border-bottom:
        1px solid
        #edf2f6;
}

.lms-reminder-row:last-child {
    border-bottom: 0;
}

/*
|--------------------------------------------------------------------------
| Row left
|--------------------------------------------------------------------------
*/

.lms-reminder-row-left {
    display: flex;

    align-items: center;

    gap: 9px;

    min-width: 0;
}

.lms-reminder-row-left > span {
    color: #334155;

    font-size: 11.5px;

    font-weight: 600;
}

/*
|--------------------------------------------------------------------------
| Small row icons
|--------------------------------------------------------------------------
*/

.lms-reminder-row-icon {
    width: 29px;

    height: 29px;

    min-width: 29px;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 14px;
}

.lms-reminder-row-icon.company {
    background:
        rgba(
            13,
            143,
            233,
            0.10
        );

    color: #0d8fe9;
}

.lms-reminder-row-icon.individual {
    background:
        rgba(
            10,
            179,
            156,
            0.10
        );

    color: #0ab39c;
}

/*
|--------------------------------------------------------------------------
| Count
|--------------------------------------------------------------------------
*/

.lms-reminder-badge {
    min-width: 27px;

    height: 25px;

    padding:
        0
        8px;

    border-radius: 999px;

    background:
        rgba(
            240,
            101,
            72,
            0.11
        );

    color: #f06548;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 11px;

    font-weight: 800;
}

/*
|--------------------------------------------------------------------------
| Courses information
|--------------------------------------------------------------------------
*/

.lms-reminder-course-info {
    margin-top: 9px;

    padding:
        7px
        9px;

    border-radius: 8px;

    background: #f8fafc;

    display: flex;

    align-items: center;

    gap: 6px;

    color: #7c8a9a;

    font-size: 9.5px;
}

.lms-reminder-course-info i {
    color: #f7b84b;

    font-size: 13px;
}

/*
|--------------------------------------------------------------------------
| Close button
|--------------------------------------------------------------------------
*/

.lms-pending-toast-close {
    position: absolute !important;

    top: 7px !important;

    right: 7px !important;

    width: 27px !important;

    height: 27px !important;

    margin: 0 !important;

    padding: 0 !important;

    border-radius: 7px !important;

    color: #94a3b8 !important;

    font-size: 20px !important;

    transition:
        background 0.2s ease,
        color 0.2s ease !important;
}

.lms-pending-toast-close:hover {
    background:
        #f1f5f9 !important;

    color:
        #071126 !important;
}

/*
|--------------------------------------------------------------------------
| Timer bar
|--------------------------------------------------------------------------
*/

.lms-pending-toast-progress {
    height: 3px !important;

    background:
        linear-gradient(
            90deg,
            #37cced,
            #0d8fe9
        ) !important;
}

/*
|--------------------------------------------------------------------------
| Mobile
|--------------------------------------------------------------------------
*/

@media (
    max-width: 575.98px
) {
    .lms-pending-toast {
        width:
            calc(
                100vw - 24px
            ) !important;
    }
}
</style>