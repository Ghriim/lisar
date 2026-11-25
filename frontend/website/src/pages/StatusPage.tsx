import { Alert, Loader, Stack, SystemPanel } from '../components'
import { ProgressLine } from '../features/status/ProgressLine'
import { useStatus } from '../features/status/queries'
import { GROUP_LABELS, statLabel } from '../features/status/statFormat'

/**
 * The Statut window, as the System shows a hunter theirs: the global level on all the experience,
 * then each stat, by group, with its level. It is read, not acted on.
 */
export function StatusPage() {
    const status = useStatus()

    if (status.isPending) {
        return <Loader />
    }

    if (status.isError) {
        return <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
    }

    return (
        <Stack>
            <SystemPanel title="Statut">
                <ProgressLine name="Niveau" progress={status.data.progress} />
                <p className="tracker-note">{status.data.progress.experience} XP au total</p>
            </SystemPanel>

            <div className="status-groups">
                {status.data.groups.map((group) => (
                    <SystemPanel key={group.group} title={GROUP_LABELS[group.group] ?? group.group}>
                        <Stack>
                            {group.stats.map((stat) => (
                                <ProgressLine key={stat.stat} name={statLabel(stat.stat)} progress={stat.progress} />
                            ))}
                        </Stack>
                    </SystemPanel>
                ))}
            </div>
        </Stack>
    )
}
