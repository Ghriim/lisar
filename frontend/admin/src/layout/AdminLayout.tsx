import { Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { AppShell, type Section } from '../components'

/**
 * One section per domain, and the screens of a domain underneath it. There is one account screen
 * today and it still sits under its own section: the shape of the menu says what lisar is made
 * of, and that does not change because a domain happens to have a single screen so far.
 *
 * The dashboard is the exception — it is a screen, not a domain, so it carries no children and
 * takes the root path: it is where signing in lands.
 */
const SECTIONS: Section[] = [
    { key: '/', label: 'Dashboard' },
    {
        key: 'user',
        label: 'User',
        children: [{ key: '/comptes', label: 'Comptes' }],
    },
    {
        key: 'todo',
        label: 'Todo',
        children: [
            { key: '/priorites', label: 'Priorités' },
            { key: '/categories', label: 'Catégories' },
        ],
    },
    {
        key: 'hydration',
        label: 'Hydratation',
        children: [{ key: '/hydratation/raccourcis', label: 'Raccourcis' }],
    },
]

export function AdminLayout() {
    const { user, signOut } = useAuth()
    const navigate = useNavigate()
    const { pathname } = useLocation()

    return (
        <AppShell
            sections={SECTIONS}
            currentPath={pathname}
            onNavigate={(path) => void navigate(path)}
            username={user?.username}
            onSignOut={() => void signOut()}
        >
            <Outlet />
        </AppShell>
    )
}
