import { FolderCog, Plus } from 'lucide-react'
import { useState } from 'react'
import type { Task } from '../../api/types'
import { DataList, IconButton, Modal, Row, SystemPanel, Tabs } from '../../components'
import { CategoryManager } from './CategoryManager'
import { TaskComposer } from './TaskComposer'
import { TaskDetail } from './TaskDetail'
import { TaskItem } from './TaskItem'
import { useTasks } from './queries'
import { groupByCategory } from './taskDisplay'

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

/**
 * The quest log, with every window it opens. Shown on the dashboard and, alone, on the todo
 * page — one component, so the two can never drift apart.
 */
export function QuestLog() {
    const [view, setView] = useState<View>('open')
    const [panel, setPanel] = useState<Panel>(null)
    const [managingCategories, setManagingCategories] = useState(false)

    const tasks = useTasks(view === 'done')
    const groups = groupByCategory(tasks.data ?? [])

    const close = () => setPanel(null)

    return (
        <>
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
        </>
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
