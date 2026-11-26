import { FolderCog, Plus, Star } from 'lucide-react'
import { useState } from 'react'
import type { Task } from '../../api/types'
import { Alert, EmptyState, FoldGroup, IconButton, Loader, Modal, SectionHeading, SystemPanel, Tabs } from '../../components'
import { CategoryManager } from '../categories/CategoryManager'
import { groupByCategory } from '../categories/groupByCategory'
import { TaskComposer } from './TaskComposer'
import { TaskDetail } from './TaskDetail'
import { TaskItem } from './TaskItem'
import { useTasks } from './queries'
import { countTasks, formatTodayHeading, isDoneToday, isForToday, today } from './taskDisplay'
import { formatExperience, totalExperience } from './taskExperience'

/**
 * What the window above the list is currently for. Closed means there is no window.
 *
 * Reading a task and editing it are the same window in two states: the title opens it read-only,
 * and its pencil turns it into the form without ever closing.
 */
type Panel =
    | { mode: 'view'; task: Task }
    | { mode: 'edit'; task: Task }
    | { mode: 'create'; parent: Task | null }
    | null

type View = 'open' | 'done'

const FAILURE = 'Le System ne répond pas. Réessaie dans un instant.'

/**
 * The task list, with every window it opens. Shown on the dashboard and, alone, on the todo
 * page — one component, so the two can never drift apart.
 *
 * Open tasks come in two places, never both: « Aujourd'hui » holds the ones due today or late,
 * whatever their category; each category holds the rest — no date, or a date to come.
 */
export function TaskList() {
    const [view, setView] = useState<View>('open')
    const [panel, setPanel] = useState<Panel>(null)
    const [managingCategories, setManagingCategories] = useState(false)

    // Both lists, whichever tab is shown: the open count heads the tabs, and « Aujourd'hui » keeps
    // the ones ticked off today, ticked.
    const open = useTasks(false)
    const done = useTasks(true)
    const shown = view === 'open' ? open : done

    const close = () => setPanel(null)
    const onView = (task: Task) => setPanel({ mode: 'view', task })

    const day = today()
    const openTasks = open.data ?? []
    const doneToday = (done.data ?? []).filter((task) => isDoneToday(task, day))
    const dueToday = openTasks.filter((task) => isForToday(task, day))
    const todayTasks = [...dueToday, ...doneToday]

    return (
        <section className="board-section" aria-labelledby="tasks-title">
            <SectionHeading
                id="tasks-title"
                title="Tâches"
                actions={
                    <>
                        <IconButton icon={FolderCog} label="Gérer les catégories" large onClick={() => setManagingCategories(true)} />
                        <IconButton icon={Plus} label="Créer une tâche" variant="primary" large onClick={() => setPanel({ mode: 'create', parent: null })} />
                    </>
                }
            />

            <SystemPanel className="task-board">
                <Tabs<View>
                    current={view}
                    onChange={setView}
                    tabs={[
                        { value: 'open', label: open.data === undefined ? 'Actives' : `Actives · ${openTasks.length}` },
                        { value: 'done', label: 'Terminées' },
                    ]}
                />

                {shown.isPending && <Loader />}
                {shown.isError && <Alert>{FAILURE}</Alert>}

                {shown.isSuccess && view === 'open' && (
                    <>
                        <div className="task-group">
                            <div className="task-today-heading">
                                <Star size={14} strokeWidth={2} className="task-today-star" aria-hidden />
                                <span className="task-today-title">Aujourd’hui</span>
                                <span className="task-today-date">{formatTodayHeading()} · retards inclus</span>
                                <span className="task-today-progress">
                                    <span className="task-today-done">{doneToday.length}</span>
                                    <span className="task-today-total"> / {todayTasks.length} · </span>
                                    <span className="task-today-xp">{formatExperience(totalExperience(todayTasks))}</span>
                                </span>
                            </div>
                            {todayTasks.length === 0 ? (
                                <EmptyState>Rien pour aujourd’hui.</EmptyState>
                            ) : (
                                todayTasks.map((task) => <TaskItem key={task.id} task={task} onView={onView} inToday />)
                            )}
                        </div>

                        {groupByCategory(openTasks, (task) => task.category).map((group) => {
                            const rest = group.items.filter((task) => !isForToday(task, day))
                            const inToday = group.items.length - rest.length

                            return (
                                <FoldGroup
                                    key={group.label}
                                    label={group.label}
                                    count={countTasks(rest.length)}
                                    note={inToday > 0 ? `+${inToday} dans Aujourd’hui` : undefined}
                                >
                                    {rest.map((task) => (
                                        <TaskItem key={task.id} task={task} onView={onView} />
                                    ))}
                                </FoldGroup>
                            )
                        })}
                    </>
                )}

                {shown.isSuccess && view === 'done' && (
                    <>
                        {shown.data.length === 0 && <EmptyState>Aucune tâche terminée.</EmptyState>}
                        {groupByCategory(shown.data, (task) => task.category).map((group) => (
                            <FoldGroup key={group.label} label={group.label} count={countTasks(group.items.length)}>
                                {group.items.map((task) => (
                                    <TaskItem key={task.id} task={task} onView={onView} />
                                ))}
                            </FoldGroup>
                        ))}
                    </>
                )}
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
        </section>
    )
}

function panelTitle(panel: NonNullable<Panel>): string {
    if (panel.mode === 'view') {
        // Generic on purpose: the task's own title is the first field inside the window, and
        // saying it twice in the same box says it once too often.
        return 'Détails de la tâche'
    }

    if (panel.mode === 'edit') {
        return 'Modifier la tâche'
    }

    return panel.parent === null ? 'Nouvelle tâche' : `Sous-tâche de « ${panel.parent.title} »`
}
