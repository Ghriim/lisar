import { FolderCog, Plus } from 'lucide-react'
import { useState } from 'react'
import type { Task } from '../api/types'
import { useAuth } from '../auth/useAuth'
import {
    DataList,
    IconButton,
    Modal,
    PageShell,
    Row,
    SystemPanel,
    Tabs,
    useReloadOnDayChange,
} from '../components'
import { HabitPanel } from '../features/habits/HabitPanel'
import { HydrationWidget } from '../features/hydration/HydrationWidget'
import { useHydrationToday } from '../features/hydration/queries'
import { SleepWidget } from '../features/sleep/SleepWidget'
import { StepWidget } from '../features/steps/StepWidget'
import { WeightWidget } from '../features/weight/WeightWidget'
import { CategoryManager } from '../features/tasks/CategoryManager'
import { TaskComposer } from '../features/tasks/TaskComposer'
import { TaskDetail } from '../features/tasks/TaskDetail'
import { TaskItem } from '../features/tasks/TaskItem'
import { useTasks } from '../features/tasks/queries'
import { groupByCategory } from '../features/tasks/taskDisplay'

/**
 * What the window above the list is currently for. Closed means there is no window.
 *
 * Reading a quest and editing it are the same window in two states: the eye opens it read-only,
 * and its pencil turns it into the form without ever closing.
 */
type Panel =
    | { mode: 'view'; task: Task }
    | { mode: 'edit'; task: Task }
    | { mode: 'create'; parent: Task | null }
    | null

type View = 'open' | 'done'

export function TasksPage() {
    const { user, signOut } = useAuth()
    const [view, setView] = useState<View>('open')
    const [panel, setPanel] = useState<Panel>(null)
    const [managingCategories, setManagingCategories] = useState(false)

    const tasks = useTasks(view === 'done')
    const groups = groupByCategory(tasks.data ?? [])

    // When the day turns under a page left open, the whole screen is stale, not one widget: the
    // day's totals, its goal, its entries. So the page is what watches for it — and the day
    // comes from the API, never from the browser, because the timezone days are counted in is a
    // back-end decision and computing it here is how the two start disagreeing.
    //
    // This reads the same cached query the hydration widget does, so it costs no extra request.
    const hydration = useHydrationToday()
    useReloadOnDayChange(hydration.data?.day, hydration.refetch)

    const close = () => setPanel(null)

    return (
        <PageShell username={user?.username} onSignOut={() => void signOut()}>
            <div className="tracker-row" style={{ marginBottom: 'calc(var(--step) * 3)' }}>
                <HydrationWidget />
                <StepWidget />
                <SleepWidget />
                <WeightWidget />
            </div>

            <div className="main-columns">
                <SystemPanel
                    title="Journal de quêtes"
                actions={
                    <Row style={{ gap: 6 }}>
                        <IconButton
                            icon={FolderCog}
                            label="Gérer les catégories"
                            onClick={() => setManagingCategories(true)}
                        />
                        <IconButton
                            icon={Plus}
                            label="Créer une quête"
                            onClick={() => setPanel({ mode: 'create', parent: null })}
                        />
                    </Row>
                }
            >
                <Tabs<View>
                    current={view}
                    onChange={setView}
                    tabs={[
                        { value: 'open', label: 'En cours' },
                        { value: 'done', label: 'Terminées' },
                    ]}
                />

                <DataList<Task>
                    groups={groups.map((group) => ({ label: group.label, items: group.tasks }))}
                    keyOf={(task) => task.id}
                    loading={tasks.isPending}
                    error={tasks.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
                    emptyText={view === 'done' ? 'Aucune quête terminée.' : 'Aucune quête en cours.'}
                    renderItem={(task) => (
                        <TaskItem task={task} onView={(target) => setPanel({ mode: 'view', task: target })} />
                    )}
                />
            </SystemPanel>

                <HabitPanel />
            </div>

            {managingCategories && (
                <Modal title="Catégories" onClose={() => setManagingCategories(false)}>
                    <CategoryManager />
                </Modal>
            )}

            {panel !== null && (
                <Modal title={panelTitle(panel)} onClose={close}>
                    {panel.mode === 'view' ? (
                        <TaskDetail
                            task={panel.task}
                            onEdit={() => setPanel({ mode: 'edit', task: panel.task })}
                            onAddSubtask={() => setPanel({ mode: 'create', parent: panel.task })}
                            onDeleted={close}
                        />
                    ) : (
                        <TaskComposer
                            parent={panel.mode === 'create' ? panel.parent : null}
                            editing={panel.mode === 'edit' ? panel.task : null}
                            onDone={close}
                        />
                    )}
                </Modal>
            )}
        </PageShell>
    )
}

function panelTitle(panel: NonNullable<Panel>): string {
    if (panel.mode === 'view') {
        // Generic on purpose: the quest's own title is the first field inside the window, and
        // saying it twice in the same box says it once too often.
        return 'Détails de la quête'
    }

    if (panel.mode === 'edit') {
        return 'Modifier la quête'
    }

    return panel.parent === null ? 'Nouvelle quête' : `Sous-quête de « ${panel.parent.title} »`
}
