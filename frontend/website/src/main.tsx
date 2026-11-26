import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { App } from './App'
import { AuthProvider } from './auth/AuthProvider'
// The two faces of the System, served from the app itself: Inter for the text, JetBrains Mono for
// titles, numbers and hours — only the weights the design uses, latin only (French is covered).
import '@fontsource/inter/latin-400.css'
import '@fontsource/inter/latin-500.css'
import '@fontsource/inter/latin-600.css'
import '@fontsource/jetbrains-mono/latin-400.css'
import '@fontsource/jetbrains-mono/latin-600.css'
import '@fontsource/jetbrains-mono/latin-700.css'
import './index.css'

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            // The 401 retry lives in the API client; retrying here would only hide failures.
            retry: false,
            refetchOnWindowFocus: false,
        },
    },
})

const container = document.getElementById('root')

if (container === null) {
    throw new Error('The #root element is missing from index.html.')
}

createRoot(container).render(
    <StrictMode>
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <BrowserRouter>
                    <App />
                </BrowserRouter>
            </AuthProvider>
        </QueryClientProvider>
    </StrictMode>,
)
