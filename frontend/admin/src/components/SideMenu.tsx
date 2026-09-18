import { Menu } from 'antd'

export interface Screen {
    /** The route it leads to. */
    key: string
    label: string
}

export interface Section extends Screen {
    /** Absent on a section that is a screen in itself, like the dashboard. */
    children?: Screen[]
}

interface SideMenuProps {
    sections: Section[]
    currentPath: string
    onSelect: (path: string) => void
}

export function SideMenu({ sections, currentPath, onSelect }: SideMenuProps) {
    const screens = sections.flatMap((section) => section.children ?? [section])

    // Exact match, or a path underneath it. Plain startsWith would make "/" swallow every route,
    // since every path begins with it.
    const selected =
        screens.find((screen) => currentPath === screen.key || currentPath.startsWith(`${screen.key}/`))
            ?.key ?? screens[0].key

    return (
        <Menu
            mode="inline"
            selectedKeys={[selected]}
            // Everything open: the whole map is visible at a glance, and nothing hides behind a
            // click in a back-office this size.
            defaultOpenKeys={sections.filter((section) => undefined !== section.children).map((section) => section.key)}
            // Mapped rather than passed straight through: Ant's item type is a union where a leaf
            // has no `children` key at all, not one holding undefined.
            items={sections.map((section) =>
                undefined === section.children
                    ? { key: section.key, label: section.label }
                    : { key: section.key, label: section.label, children: section.children },
            )}
            onClick={({ key }) => onSelect(key)}
        />
    )
}
