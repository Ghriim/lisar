import { Card, Typography } from 'antd'
import type { LucideIcon } from 'lucide-react'

interface IconGalleryProps {
    icons: [name: string, icon: LucideIcon][]
    /** Clicking a tile hands its name back: the page decides what to do with it. */
    onPick: (name: string) => void
}

/** Every icon drawn with its name underneath, in a grid that fills whatever width it gets. */
export function IconGallery({ icons, onPick }: IconGalleryProps) {
    return (
        <div
            style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(128px, 1fr))',
                gap: 8,
            }}
        >
            {icons.map(([name, Icon]) => (
                <Card
                    key={name}
                    size="small"
                    hoverable
                    onClick={() => onPick(name)}
                    styles={{ body: { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 } }}
                >
                    <Icon size={24} strokeWidth={1.8} aria-hidden />
                    <Typography.Text type="secondary" ellipsis={{ tooltip: name }} style={{ fontSize: 12, maxWidth: '100%' }}>
                        {name}
                    </Typography.Text>
                </Card>
            ))}
        </div>
    )
}
