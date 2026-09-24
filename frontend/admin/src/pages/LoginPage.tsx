import { useState } from 'react'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'
import {
    Alert,
    Button,
    Centered,
    EmailField,
    Form,
    FormActions,
    Page,
    Panel,
    PasswordField,
} from '../components'

interface Credentials {
    email: string
    password: string
}

export function LoginPage() {
    const { signIn } = useAuth()
    const [error, setError] = useState<string | null>(null)
    const [pending, setPending] = useState(false)

    const submit = async (credentials: Credentials) => {
        setPending(true)
        setError(null)

        try {
            await signIn(credentials.email, credentials.password)
        } catch (failure) {
            setError(messageFor(failure))
        } finally {
            setPending(false)
        }
    }

    return (
        <Centered>
            <Panel>
                <Page title="lisar · back-office">
                    <Form<Credentials> onSubmit={(values) => void submit(values)}>
                        <EmailField name="email" label="Adresse" required autoFocus />
                        <PasswordField name="password" label="Mot de passe" required />

                        {error !== null && <Alert message={error} />}

                        <FormActions>
                            <Button variant="primary" submit loading={pending}>
                                Entrer
                            </Button>
                        </FormActions>
                    </Form>
                </Page>
            </Panel>
        </Centered>
    )
}

function messageFor(failure: unknown): string {
    if (failure instanceof ApiError) {
        if (failure.code === 'wrong_audience') {
            return 'Ce compte existe, mais il n’est pas administrateur.'
        }

        if (failure.code === 'account_deactivated') {
            return 'Ce compte est désactivé.'
        }

        if (failure.status === 401) {
            return 'Adresse ou mot de passe incorrect.'
        }
    }

    return 'Le serveur ne répond pas. Réessayez dans un instant.'
}
