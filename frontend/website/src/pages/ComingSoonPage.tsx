import { EmptyState, SystemPanel } from '../components'

/** A screen the menu already leads to, before it has anything to show. */
export function ComingSoonPage({ title }: { title: string }) {
    return (
        <SystemPanel title={title}>
            <EmptyState>Bientôt disponible.</EmptyState>
        </SystemPanel>
    )
}
