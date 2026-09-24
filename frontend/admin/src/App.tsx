import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { useAuth } from './auth/useAuth'
import { FullPageLoader, PlainShell } from './components'
import { AdminLayout } from './layout/AdminLayout'
import { CategoriesPage } from './pages/CategoriesPage'
import { DashboardPage } from './pages/DashboardPage'
import { HabitsPage } from './pages/HabitsPage'
import { HydrationPresetsPage } from './pages/HydrationPresetsPage'
import { LoginPage } from './pages/LoginPage'
import { PrioritiesPage } from './pages/PrioritiesPage'
import { UsersPage } from './pages/UsersPage'

// Loaded on demand: it pulls in every lucide icon, which no other screen needs to download.
const IconsPage = lazy(() => import('./pages/IconsPage').then((module) => ({ default: module.IconsPage })))

export function App() {
    const { status } = useAuth()

    // The first paint has no access token: it is being fetched back from the refresh cookie.
    if (status === 'restoring') {
        return (
            <PlainShell>
                <FullPageLoader />
            </PlainShell>
        )
    }

    if (status === 'anonymous') {
        return (
            <PlainShell>
                <Routes>
                    <Route path="/connexion" element={<LoginPage />} />
                    <Route path="*" element={<Navigate to="/connexion" replace />} />
                </Routes>
            </PlainShell>
        )
    }

    return (
        <Routes>
            <Route element={<AdminLayout />}>
                <Route path="/" element={<DashboardPage />} />
                <Route path="/comptes" element={<UsersPage />} />
                <Route path="/priorites" element={<PrioritiesPage />} />
                <Route path="/categories" element={<CategoriesPage />} />
                <Route path="/hydratation/raccourcis" element={<HydrationPresetsPage />} />
                <Route path="/habitudes/catalogue" element={<HabitsPage />} />
                <Route
                    path="/developpeurs/icones"
                    element={
                        <Suspense fallback={<FullPageLoader />}>
                            <IconsPage />
                        </Suspense>
                    }
                />
                {/* Signing in lands here, and so does anything that does not match. */}
                <Route path="*" element={<Navigate to="/" replace />} />
            </Route>
        </Routes>
    )
}
