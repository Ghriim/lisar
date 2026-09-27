import type { ReactNode } from 'react'

/** The frame every signed-in screen sits in: the menu down the side, the screen beside it. */
export function PageShell({ nav, children }: { nav: ReactNode; children: ReactNode }) {
    return (
        <div className="app-frame">
            {nav}
            <main className="app-shell">{children}</main>
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
