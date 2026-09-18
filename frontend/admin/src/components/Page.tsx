import { Card } from 'antd'
import type { ReactNode } from 'react'

interface PageProps {
    title: string
    /** The one action a screen offers on itself, top right: creating a row, usually. */
    action?: ReactNode
    children: ReactNode
}

export function Page({ title, action, children }: PageProps) {
    return (
        <Card title={title} extra={action}>
            {children}
        </Card>
    )
}
