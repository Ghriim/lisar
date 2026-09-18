import type { ReactNode } from 'react'
import { Button } from './Button'
import { Row } from './Layout'

interface PageShellProps {
    username: string | undefined
    onSignOut: () => void
    children: ReactNode
}

/** The frame every signed-in screen sits in: the wordmark, who is signed in, and the way out. */
export function PageShell({ username, onSignOut, children }: PageShellProps) {
    return (
        <div className="app-shell">
            <header className="app-header">
                <Row spread wrap>
                    <div>
                        <span className="wordmark">LISAR</span>
                        <span className="wordmark-sub">Life is a RPG</span>
                    </div>

                    <Row>
                        <span className="system-text dim">{username}</span>
                        <Button variant="quiet" onClick={onSignOut}>
                            Se déconnecter
                        </Button>
                    </Row>
                </Row>
            </header>

            {children}
        </div>
    )
}

/** The frame for the screens reached before signing in. */
export function AuthShell({ children }: { children: ReactNode }) {
    return (
        <div className="auth-screen">
            <div className="auth-panel">{children}</div>
        </div>
    )
}
