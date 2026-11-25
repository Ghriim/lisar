import type { CSSProperties, ReactNode } from 'react'
import { Link } from 'react-router-dom'

/** A chip that is also a way in: it opens the page of what it names. */
export function LinkChip({ children, colour, to }: { children: ReactNode; colour?: string; to: string }) {
    return (
        <Link to={to} className="chip" style={colour === undefined ? undefined : ({ color: colour } as CSSProperties)}>
            {children}
        </Link>
    )
}
