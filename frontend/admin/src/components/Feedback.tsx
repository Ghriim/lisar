import { Alert as AntAlert, Empty, Spin, Tag as AntTag } from 'antd'
import type { ReactNode } from 'react'
import { Centered } from './Layout'
import { Text } from './Text'

export function Alert({ message }: { message: string }) {
    return <AntAlert type="error" message={message} style={{ marginBottom: 16 }} />
}

export function EmptyState({ description }: { description: string }) {
    return <Empty description={<Text muted>{description}</Text>} />
}

/** The whole-screen wait, while the session is being restored. */
export function FullPageLoader() {
    return (
        <Centered>
            <Spin />
        </Centered>
    )
}

export function Tag({ children, colour }: { children: ReactNode; colour: string }) {
    return <AntTag color={colour}>{children}</AntTag>
}
