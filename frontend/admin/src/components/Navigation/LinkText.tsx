import { Typography } from 'antd'
import type { ReactNode } from 'react'

/** Text that acts: the account name in a table cell that opens its drawer. */
export function LinkText({ children, onClick }: { children: ReactNode; onClick: () => void }) {
    return <Typography.Link onClick={onClick}>{children}</Typography.Link>
}
