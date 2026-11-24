import { TaskList } from '../features/tasks/TaskList'

/** The task list on its own. Filters will land here, not on the dashboard. */
export function TodoPage() {
    return <TaskList />
}
