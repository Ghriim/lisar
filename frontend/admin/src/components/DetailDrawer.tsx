import { Divider, Drawer } from 'antd'
import type { ReactNode } from 'react'

interface DetailDrawerProps {
    title: string
    onClose: () => void
    children: ReactNode
}

/** Render it only while it is open, like FormModal: mounting is what resets it. */
export function DetailDrawer({ title, onClose, children }: DetailDrawerProps) {
    return (
        <Drawer open width={520} title={title} onClose={onClose}>
            {children}
        </Drawer>
    )
}

export function Separator({ children }: { children?: ReactNode }) {
    return <Divider>{children}</Divider>
}
