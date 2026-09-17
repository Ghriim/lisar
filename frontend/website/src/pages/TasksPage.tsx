import { useState } from 'react'
import type { Task } from '../api/types'
import { useAuth } from '../auth/useAuth'
import { Modal } from '../components/Modal'
import { SystemPanel } from '../components/SystemPanel'
import { CategoryManager } from '../features/tasks/CategoryManager'
import { TaskComposer } from '../features/tasks/TaskComposer'
import { TaskItem } from '../features/tasks/TaskItem'
import { useTasks } from '../features/tasks/queries'
import { groupByCategory } from '../features/tasks/taskDisplay'

/** What the window above the list is currently for. Closed means there is no window. */
type Composer = { mode: 'create'; parent: Task | null } | { mode: 'edit'; task: Task } | null

export function TasksPage() {
    const { user, signOut } = useAuth()
    const [showDone, setShowDone] = useState(false)
    const [composer, setComposer] = useState<Composer>(null)
    const [managingCategories, setManagingCategories] = useState(false)

    const tasks = useTasks(showDone)
    const groups = groupByCategory(tasks.data ?? [])

    const close = () => setComposer(null)

    return (
        <div className="app-shell">
            <header className="app-header row spread wrap">
                <div>
                    <span className="wordmark">LISAR</span>
                    <span className="wordmark-sub">Life is a RPG</span>
                </div>

                <div className="row">
                    <span className="system-text dim">{user?.username}</span>
                    <button type="button" className="button button-quiet" onClick={() => void signOut()}>
                        Se déconnecter
                    </button>
                </div>
            </header>

            <SystemPanel
                title="Journal de quêtes"
                actions={
                    <span className="row" style={{ gap: 6 }}>
                        <button
                            type="button"
                            className="icon-button"
                            aria-label="Gérer les catégories"
                            title="Gérer les catégories"
                            onClick={() => setManagingCategories(true)}
                        >
                            ▤
                        </button>
                        <button
                            type="button"
                            className="icon-button"
                            aria-label="Créer une quête"
                            title="Créer une quête"
                            onClick={() => setComposer({ mode: 'create', parent: null })}
                        >
                            +
                        </button>
                    </span>
                }
            >
                <div className="tabs" role="tablist">
                    <button
                        type="button"
                        role="tab"
                        className="tab"
                        aria-selected={!showDone}
                        onClick={() => setShowDone(false)}
                    >
                        En cours
                    </button>
                    <button
                        type="button"
                        role="tab"
                        className="tab"
                        aria-selected={showDone}
                        onClick={() => setShowDone(true)}
                    >
                        Terminées
                    </button>
                </div>

                {tasks.isPending && <p className="empty">Synchronisation…</p>}

                {tasks.isError && <p className="alert">Le System ne répond pas. Réessaie dans un instant.</p>}

                {tasks.isSuccess && groups.length === 0 && (
                    <p className="empty">{showDone ? 'Aucune quête terminée.' : 'Aucune quête en cours.'}</p>
                )}

                {groups.map((group) => (
                    <div key={group.label} className="category-group">
                        <h2 className="category-heading">{group.label}</h2>

                        {group.tasks.map((task) => (
                            <TaskItem
                                key={task.id}
                                task={task}
                                onAddSubtask={(parent) => setComposer({ mode: 'create', parent })}
                                onEdit={(target) => setComposer({ mode: 'edit', task: target })}
                            />
                        ))}
                    </div>
                ))}
            </SystemPanel>

            {managingCategories && (
                <Modal title="Catégories" onClose={() => setManagingCategories(false)}>
                    <CategoryManager />
                </Modal>
            )}

            {composer !== null && (
                <Modal title={composerTitle(composer)} onClose={close}>
                    <TaskComposer
                        parent={composer.mode === 'create' ? composer.parent : null}
                        editing={composer.mode === 'edit' ? composer.task : null}
                        onDone={close}
                    />
                </Modal>
            )}
        </div>
    )
}

function composerTitle(composer: NonNullable<Composer>): string {
    if (composer.mode === 'edit') {
        return 'Modifier la quête'
    }

    return composer.parent === null ? 'Nouvelle quête' : `Sous-quête de « ${composer.parent.title} »`
}
