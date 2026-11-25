import type { CSSProperties, ReactNode } from 'react'

interface LayoutProps {
    children: ReactNode
    /** Pushes the first and last child apart. */
    spread?: boolean
    /** Pushes everything to the right edge: an action that heads what is under it. */
    end?: boolean
    wrap?: boolean
    style?: CSSProperties
}

export function Row({ children, spread = false, end = false, wrap = false, style }: LayoutProps) {
    return (
        <div className={['row', spread ? 'spread' : '', end ? 'end' : '', wrap ? 'wrap' : ''].filter(Boolean).join(' ')} style={style}>
            {children}
        </div>
    )
}

export function Stack({ children, style }: { children: ReactNode; style?: CSSProperties }) {
    return (
        <div className="stack" style={style}>
            {children}
        </div>
    )
}
