import { Layout } from 'antd'
import type { ReactNode } from 'react'
import { Button } from './Button'
import { Row } from './Layout'
import { SideMenu, type Section } from './SideMenu'
import { Text } from './Text'

interface AppShellProps {
    sections: Section[]
    currentPath: string
    onNavigate: (path: string) => void
    username: string | undefined
    onSignOut: () => void
    children: ReactNode
}

/** The frame every signed-in screen sits in. */
export function AppShell({
    sections,
    currentPath,
    onNavigate,
    username,
    onSignOut,
    children,
}: AppShellProps) {
    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Layout.Sider breakpoint="lg" collapsedWidth={0}>
                <div style={{ padding: 16, letterSpacing: 4 }}>
                    <Text strong>LISAR</Text>
                </div>

                <SideMenu sections={sections} currentPath={currentPath} onSelect={onNavigate} />
            </Layout.Sider>

            <Layout>
                <Layout.Header>
                    <Row justify="end" gap={16} style={{ height: '100%' }}>
                        <Text muted>{username}</Text>
                        <Button onClick={onSignOut}>Se déconnecter</Button>
                    </Row>
                </Layout.Header>

                <Layout.Content style={{ padding: 24 }}>{children}</Layout.Content>
            </Layout>
        </Layout>
    )
}

/**
 * The frame for the screens reached before signing in. It exists for one reason: Ant's Layout is
 * what paints the page background, and without it these screens sit on the browser's white
 * canvas.
 */
export function PlainShell({ children }: { children: ReactNode }) {
    return <Layout style={{ minHeight: '100vh' }}>{children}</Layout>
}

export function Panel({ children, width = 380 }: { children: ReactNode; width?: number }) {
    return <div style={{ width, maxWidth: '100%' }}>{children}</div>
}
