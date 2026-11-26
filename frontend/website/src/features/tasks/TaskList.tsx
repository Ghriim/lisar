import { FolderCog, Plus, Star, Sun } from 'lucide-react'
import { useState } from 'react'
import type { Task, Todo } from '../../api/types'
import { Alert, EmptyState, FoldGroup, IconButton, Loader, Modal, SectionHeading, SystemPanel, Tabs } from '../../components'
import { CategoryManager } from '../categories/CategoryManager'
import { groupByCategory } from '../categories/groupByCategory'
import { TaskComposer } from './TaskComposer'
import { TaskDetail } from './TaskDetail'
import { useActiveTodos } from '../todos/queries'
import { TaskItem } from './TaskItem'
import { useTaskOverview, useTasks } from './queries'
import { countTasks, formatTodayHeading, isDoneToday, isDueByToday, today } from './taskDisplay'

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

/**
 * Where the list's tasks come from: one TODO — the TODOs page — or the overview the API answers
 * across the active ones, for the dashboard.
 */
export type TaskSource = { kind: 'todo'; todo: Todo } | { kind: 'overview' }

interface TaskListProps {
    source: TaskSource
    /** The section's title: « Tâches » on the dashboard, the TODO's on its page. */
    title?: string
}

const FAILURE = 'Le System ne répond pas. Réessaie dans un instant.'

/**
 * The task list, with every window it opens. One TODO on the TODOs page, with its two tabs; the
 * overview on the dashboard, open tasks only — one component, so the two can never drift apart.
 *
 * Tasks come in two places, never both: « Aujourd'hui » holds the ones due today or late, and
 * those of them ticked off today, ticked; each category holds the rest — on a TODO's page, no
 * date or a date to come; on the dashboard, the starred ones. A category left with nothing is not
 * shown.
 */
export function TaskList({ source, title = 'Tâches' }: TaskListProps) {
    const [view, setView] = useState<View>('open')
    const [panel, setPanel] = useState<Panel>(null)
    const [managingCategories, setManagingCategories] = useState(false)

    const todoId = source.kind === 'todo' ? source.todo.id : null
    const isTodo = source.kind === 'todo'

    // On a TODO's page, both lists whichever tab is shown: the open count heads the tabs, and
    // « Aujourd'hui » keeps the ones ticked off today, ticked. The overview holds both at once.
    const open = useTasks(false, todoId, isTodo)
    const done = useTasks(true, todoId, isTodo)
    const overview = useTaskOverview()
    const shownView = isTodo ? view : 'open'
    const shown = !isTodo ? overview : shownView === 'open' ? open : done

    // A task created from here goes in this TODO; from the dashboard, in « A faire ».
    const todos = useActiveTodos()
    const defaultTodoId = todoId ?? todos.data?.find((todo) => todo.isDefault)?.id ?? null

    const close = () => setPanel(null)
    const onView = (task: Task) => setPanel({ mode: 'view', task })

    const day = today()
    const all = isTodo ? [...(open.data ?? []), ...(done.data ?? [])] : (overview.data ?? [])
    // What is listed: the open tasks, and those ticked off today — still shown, ticked, so the day's
    // work stays in sight.
    const listed = all.filter((task) => task.state !== 'done' || isDoneToday(task, day))
    // « Aujourd'hui »: due today or late, open or ticked off today. The rest goes in the categories —
    // on a TODO's page its undated or later tasks, on the dashboard the favourites.
    const todayTasks = listed.filter((task) => isDueByToday(task, day))
    const rest = listed.filter((task) => !isDueByToday(task, day))

    const categoryGroups = groupByCategory(listed, (task) => task.category).map((group) => {
        const left = group.items.filter((task) => !isDueByToday(task, day))
        const inToday = group.items.length - left.length

        // A category whose every task is in « Aujourd'hui » has nothing left to show here.
        if (left.length === 0) {
            return null
        }

        return (
            <FoldGroup
                key={group.label}
                label={group.label}
                count={countTasks(left.length)}
                note={inToday > 0 ? `+${inToday} dans Aujourd’hui` : undefined}
            >
                {left.map((task) => (
                    <TaskItem key={task.id} task={task} onView={onView} />
                ))}
            </FoldGroup>
        )
    })

    return (
        <section className="board-section" aria-labelledby="tasks-title">
            <SectionHeading
                id="tasks-title"
                title={title}
                actions={
                    <>
                        <IconButton icon={FolderCog} label="Gérer les catégories" large onClick={() => setManagingCategories(true)} />
                        <IconButton icon={Plus} label="Créer une tâche" variant="primary" large onClick={() => setPanel({ mode: 'create', parent: null })} />
                    </>
                }
            />

            <SystemPanel className="task-board">
                {isTodo && (
                    <Tabs<View>
                        current={view}
                        onChange={setView}
                        tabs={[
                            { value: 'open', label: open.data === undefined ? 'Actives' : `Actives · ${open.data.length}` },
                            { value: 'done', label: 'Terminées' },
                        ]}
                    />
                )}

                {shown.isPending && <Loader />}
                {shown.isError && <Alert>{FAILURE}</Alert>}

                {shown.isSuccess && shownView === 'open' && (
                    <>
                        <div className="task-group">
                            <div className="task-today-heading">
                                <Sun size={14} strokeWidth={2} className="task-today-icon" aria-hidden />
                                <span className="task-today-title">Aujourd’hui</span>
                                <span className="task-today-date">{formatTodayHeading()} · retards inclus</span>
                                <GroupProgress tasks={todayTasks} />
                            </div>
                            {todayTasks.length === 0 ? (
                                <p className="task-group-empty">Rien pour aujourd’hui.</p>
                            ) : (
                                todayTasks.map((task) => <TaskItem key={task.id} task={task} onView={onView} inToday />)
                            )}
                        </div>

                        {isTodo ? (
                            categoryGroups
                        ) : (
                            // On the dashboard, the rest is the starred tasks: a section of their own, lit like « Aujourd'hui », its categories inside.
                            <div className="task-group">
                                <div className="task-today-heading">
                                    <Star size={14} strokeWidth={2} className="task-today-icon" aria-hidden />
                                    <span className="task-today-title">Favorites</span>
                                    <GroupProgress tasks={rest} />
                                </div>
                                {rest.length === 0 ? <p className="task-group-empty">Aucune tâche en favori.</p> : categoryGroups}
                            </div>
                        )}
                    </>
                )}

                {shown.isSuccess && shownView === 'done' && (
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
                            onEdit={(task) => setPanel({ mode: 'edit', task })}
                            onAddSubtask={() => setPanel({ mode: 'create', parent: panel.task })}
                            onDeleted={close}
                        />
                    ) : (
                        <TaskComposer
                            parent={panel.mode === 'create' ? panel.parent : null}
                            editing={panel.mode === 'edit' ? panel.task : null}
                            defaultTodoId={defaultTodoId}
                            onDone={close}
                        />
                    )}
                </Modal>
            )}
        </section>
    )
}

/** « 1 / 3 »: of a group's tasks, how many are done — on the right of its heading. */
function GroupProgress({ tasks }: { tasks: Task[] }) {
    const doneCount = tasks.filter((task) => task.state === 'done').length

    return (
        <span className="task-today-progress" aria-label={`${doneCount} sur ${tasks.length} terminées`}>
            <span className="task-today-done">{doneCount}</span>
            <span className="task-today-total"> / {tasks.length}</span>
        </span>
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
