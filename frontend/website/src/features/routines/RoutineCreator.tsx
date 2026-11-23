import { useNavigate } from 'react-router-dom'
import { Modal } from '../../components'
import { useCreateRoutine } from './queries'
import { RoutineNameForm } from './RoutineNameForm'

/** A new routine, named and empty, and then its page: that is where it is shaped. */
export function RoutineCreator({ onClose }: { onClose: () => void }) {
    const create = useCreateRoutine()
    const navigate = useNavigate()

    return (
        <Modal title="Nouvelle routine" onClose={onClose}>
            <RoutineNameForm
                current={null}
                save={async (name) => {
                    const routine = await create.mutateAsync(name)
                    void navigate(`/routines/${routine.id}`)
                }}
                pending={create.isPending}
                failure={create.error}
                onDone={onClose}
            />
        </Modal>
    )
}
