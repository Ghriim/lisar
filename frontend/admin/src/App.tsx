import { Flex, Layout, Spin } from 'antd'
import { Navigate, Route, Routes } from 'react-router-dom'
import { useAuth } from './auth/useAuth'
import { AdminLayout } from './layout/AdminLayout'
import { CategoriesPage } from './pages/CategoriesPage'
import { DashboardPage } from './pages/DashboardPage'
import { LoginPage } from './pages/LoginPage'
import { PrioritiesPage } from './pages/PrioritiesPage'
import { UsersPage } from './pages/UsersPage'

export function App() {
    const { status } = useAuth()

    // The first paint has no access token: it is being fetched back from the refresh cookie.
    // Wrapped in a Layout like everything else — it is what paints Ant's background colour, and
    // without it these screens sit on the browser's white canvas.
    if (status === 'restoring') {
        return (
            <Layout style={{ minHeight: '100vh' }}>
                <Flex align="center" justify="center" style={{ minHeight: '100vh' }}>
                    <Spin />
                </Flex>
            </Layout>
        )
    }

    if (status === 'anonymous') {
        return (
            <Layout style={{ minHeight: '100vh' }}>
                <Routes>
                    <Route path="/connexion" element={<LoginPage />} />
                    <Route path="*" element={<Navigate to="/connexion" replace />} />
                </Routes>
            </Layout>
        )
    }

    return (
        <Routes>
            <Route element={<AdminLayout />}>
                <Route path="/" element={<DashboardPage />} />
                <Route path="/comptes" element={<UsersPage />} />
                <Route path="/priorites" element={<PrioritiesPage />} />
                <Route path="/categories" element={<CategoriesPage />} />
                {/* Signing in lands here, and so does anything that does not match. */}
                <Route path="*" element={<Navigate to="/" replace />} />
            </Route>
        </Routes>
    )
}
