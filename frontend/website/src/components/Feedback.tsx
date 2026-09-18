/** Nothing to show, and why. */
export function EmptyState({ children }: { children: string }) {
    return <p className="empty">{children}</p>
}

/** Something went wrong, said once, in red. */
export function Alert({ children }: { children: string }) {
    return <p className="alert">{children}</p>
}

/** Waiting. Deliberately the same shape as EmptyState: both are the absence of content. */
export function Loader({ children = 'Synchronisation…' }: { children?: string }) {
    return <p className="empty">{children}</p>
}
