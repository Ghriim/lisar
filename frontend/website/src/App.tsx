import { Navigate, Route, Routes } from 'react-router-dom'
import { useAuth } from './auth/useAuth'
import { Loader } from './components'
import { SignedInLayout } from './layout/SignedInLayout'
import { ComingSoonPage } from './pages/ComingSoonPage'
import { DashboardPage } from './pages/DashboardPage'
import { LoginPage } from './pages/LoginPage'
import { RegisterPage } from './pages/RegisterPage'
import { TodoPage } from './pages/TodoPage'
import { WorkoutCompletePage } from './pages/WorkoutCompletePage'
import { WorkoutPage } from './pages/WorkoutPage'
import { WorkoutsPage } from './pages/WorkoutsPage'
import { RecordsPage } from './pages/RecordsPage'

export function App() {
    const { status } = useAuth()

    // The first paint has no access token yet: it is being fetched back from the refresh cookie.
    // Rendering the login form here would make a returning person blink through it.
    if (status === 'restoring') {
        return <Loader>Connexion au System…</Loader>
    }

    if (status === 'anonymous') {
        return (
            <Routes>
                <Route path="/connexion" element={<LoginPage />} />
                <Route path="/inscription" element={<RegisterPage />} />
                <Route path="*" element={<Navigate to="/connexion" replace />} />
            </Routes>
        )
    }

    return (
        <Routes>
            <Route element={<SignedInLayout />}>
                <Route path="/" element={<DashboardPage />} />
                <Route path="/todo" element={<TodoPage />} />
                <Route path="/workouts" element={<WorkoutsPage />} />
                <Route path="/workouts/:id" element={<WorkoutPage />} />
                <Route path="/workouts/:id/complete" element={<WorkoutCompletePage />} />
                <Route path="/records" element={<RecordsPage />} />
                <Route path="/statistiques" element={<ComingSoonPage title="Statistiques" />} />
                <Route path="/messages" element={<ComingSoonPage title="Messages" />} />
                <Route path="/amis" element={<ComingSoonPage title="Amis" />} />
                <Route path="/reglages" element={<ComingSoonPage title="Réglages" />} />
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
    )
}
