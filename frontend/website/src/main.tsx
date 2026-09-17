import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { App } from './App'
import { AuthProvider } from './auth/AuthProvider'
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
