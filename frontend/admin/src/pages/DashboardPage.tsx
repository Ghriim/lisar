import { Card, Empty, Typography } from 'antd'

/**
 * The landing screen. Empty on purpose for now: what a back-office dashboard should show is a
 * product question nobody has answered yet, and inventing counters to fill the space would only
 * make it harder to ask.
 */
export function DashboardPage() {
    return (
        <Card title="Dashboard">
            <Empty
                description={
                    <Typography.Text type="secondary">
                        Rien à afficher pour l’instant.
                    </Typography.Text>
                }
            />
        </Card>
    )
}
