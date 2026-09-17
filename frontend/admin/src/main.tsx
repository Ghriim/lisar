import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { App as AntApp, ConfigProvider, theme } from 'antd'
import frFR from 'antd/locale/fr_FR'
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
        {/* Ant Design as it comes, dark, with the family's accent. The back-office has no design
            of its own: that is the point of using a component library here. */}
        <ConfigProvider
            locale={frFR}
            theme={{ algorithm: theme.darkAlgorithm, token: { colorPrimary: '#4dd2ff' } }}
        >
            <AntApp>
                <QueryClientProvider client={queryClient}>
                    <AuthProvider>
                        <BrowserRouter>
                            <App />
                        </BrowserRouter>
                    </AuthProvider>
                </QueryClientProvider>
            </AntApp>
        </ConfigProvider>
    </StrictMode>,
)
