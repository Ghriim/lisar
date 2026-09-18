import { Navigate, Route, Routes } from 'react-router-dom'
import { useAuth } from './auth/useAuth'
import { Loader } from './components'
import { LoginPage } from './pages/LoginPage'
import { RegisterPage } from './pages/RegisterPage'
import { TasksPage } from './pages/TasksPage'

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
            <Route path="/" element={<TasksPage />} />
            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
    )
}
