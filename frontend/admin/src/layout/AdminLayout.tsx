import { Button, Flex, Layout, Menu, Typography } from 'antd'
import { Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'

/**
 * One section per domain, and the screens of a domain underneath it. There is one account screen
 * today and it still sits under its own section: the shape of the menu says what lisar is made
 * of, and that does not change because a domain happens to have a single screen so far.
 *
 * The dashboard is the exception — it is a screen, not a domain, so it carries no children and
 * takes the root path: it is where signing in lands.
 */
interface Screen {
    key: string
    label: string
}

interface Section extends Screen {
    /** Absent on a section that is a screen in itself, like the dashboard. */
    children?: Screen[]
}

const SECTIONS: Section[] = [
    { key: '/', label: 'Dashboard' },
    {
        key: 'user',
        label: 'User',
        children: [{ key: '/comptes', label: 'Comptes' }],
    },
    {
        key: 'todo',
        label: 'Todo',
        children: [
            { key: '/priorites', label: 'Priorités' },
            { key: '/categories', label: 'Catégories' },
        ],
    },
]

const SCREENS = SECTIONS.flatMap((section) => section.children ?? [section])

export function AdminLayout() {
    const { user, signOut } = useAuth()
    const navigate = useNavigate()
    const { pathname } = useLocation()

    // Exact match, or a path underneath it. Plain startsWith would make "/" swallow every
    // route, since every path begins with it.
    const selected =
        SCREENS.find((screen) => pathname === screen.key || pathname.startsWith(`${screen.key}/`))
            ?.key ?? SCREENS[0].key

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Layout.Sider breakpoint="lg" collapsedWidth={0}>
                <Typography.Text strong style={{ display: 'block', padding: 16, letterSpacing: 4 }}>
                    LISAR
                </Typography.Text>

                <Menu
                    mode="inline"
                    selectedKeys={[selected]}
                    // Both open: the whole map is visible at a glance, and nothing hides behind a
                    // click in a back-office with three screens.
                    defaultOpenKeys={SECTIONS.filter((section) => undefined !== section.children).map(
                        (section) => section.key,
                    )}
                    // Mapped rather than passed straight through: Ant's item type is a union
                    // where a leaf has no `children` key at all, not one holding undefined.
                    items={SECTIONS.map((section) =>
                        undefined === section.children
                            ? { key: section.key, label: section.label }
                            : { key: section.key, label: section.label, children: section.children },
                    )}
                    onClick={({ key }) => void navigate(key)}
                />
            </Layout.Sider>

            <Layout>
                <Layout.Header>
                    <Flex align="center" justify="end" gap={16} style={{ height: '100%' }}>
                        <Typography.Text type="secondary">{user?.username}</Typography.Text>
                        <Button onClick={() => void signOut()}>Se déconnecter</Button>
                    </Flex>
                </Layout.Header>

                <Layout.Content style={{ padding: 24 }}>
                    <Outlet />
                </Layout.Content>
            </Layout>
        </Layout>
    )
}
