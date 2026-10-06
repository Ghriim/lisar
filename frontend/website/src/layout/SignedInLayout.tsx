import { ChartColumn, Dumbbell, House, ListChecks, Mail, Settings, Users } from 'lucide-react'
import { useState, useSyncExternalStore } from 'react'
import { Outlet } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { PageShell, SideNav, type NavItem } from '../components'

const PRIMARY: NavItem[] = [
    { to: '/', label: 'Dashboard', icon: House },
    { to: '/todo', label: 'Todo', icon: ListChecks },
    { to: '/workouts', label: 'Workouts', icon: Dumbbell },
    { to: '/statistiques', label: 'Statistiques', icon: ChartColumn },
]

const SECONDARY: NavItem[] = [
    { to: '/messages', label: 'Messages', icon: Mail },
    { to: '/amis', label: 'Amis', icon: Users },
    { to: '/reglages', label: 'Réglages', icon: Settings },
]

/** Below this, the menu is icons only whatever was chosen: the screen needs the width more. */
const NARROW = '(max-width: 720px)'

const STORAGE_KEY = 'lisar.sideNav.collapsed'

/** Every signed-in route renders inside this: the menu, then the page the URL names. */
export function SignedInLayout() {
    const { signOut } = useAuth()
    const narrow = useMediaQuery(NARROW)
    const [collapsed, setCollapsed] = useState(readCollapsed)

    const toggle = () => {
        setCollapsed(!collapsed)
        writeCollapsed(!collapsed)
    }

    return (
        <PageShell
            nav={
                <SideNav
                    primary={PRIMARY}
                    secondary={SECONDARY}
                    collapsed={narrow || collapsed}
                    onToggle={narrow ? undefined : toggle}
                    onSignOut={() => void signOut()}
                />
            }
        >
            <Outlet />
        </PageShell>
    )
}

function useMediaQuery(query: string): boolean {
    return useSyncExternalStore(
        (onChange) => {
            const list = window.matchMedia(query)
            list.addEventListener('change', onChange)
            return () => list.removeEventListener('change', onChange)
        },
        () => window.matchMedia(query).matches,
    )
}

// A preference of this browser, nothing more: storage can be missing or refuse, and the menu
// then simply opens unfolded.
function readCollapsed(): boolean {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === '1'
    } catch {
        return false
    }
}

function writeCollapsed(collapsed: boolean): void {
    try {
        window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0')
    } catch {
        // Not remembered, and that is all.
    }
}
