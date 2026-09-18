import { Typography } from 'antd'
import type { ReactNode } from 'react'

interface TextProps {
    children: ReactNode
    /** Dimmed, for anything that is context rather than content. */
    muted?: boolean
    size?: 'small' | 'normal'
    strong?: boolean
}

export function Text({ children, muted = false, size = 'normal', strong = false }: TextProps) {
    return (
        <Typography.Text type={muted ? 'secondary' : undefined} strong={strong} style={size === 'small' ? { fontSize: 12 } : undefined}>
            {children}
        </Typography.Text>
    )
}

export function Paragraph({ children, muted = false }: { children: ReactNode; muted?: boolean }) {
    return <Typography.Paragraph type={muted ? 'secondary' : undefined}>{children}</Typography.Paragraph>
}

export function Title({ children }: { children: ReactNode }) {
    return <Typography.Title level={4}>{children}</Typography.Title>
}

/** Text that acts: the account name in a table cell that opens its drawer. */
export function LinkText({ children, onClick }: { children: ReactNode; onClick: () => void }) {
    return <Typography.Link onClick={onClick}>{children}</Typography.Link>
}
