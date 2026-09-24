import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'
import { humanise } from '../api/violations'
import {
    Alert,
    AuthShell,
    Button,
    Field,
    FormActions,
    SystemPanel,
    TextInput,
} from '../components'

export function LoginPage() {
    const { signIn } = useAuth()
    const [email, setEmail] = useState('')
    const [password, setPassword] = useState('')
    const [error, setError] = useState<ApiError | null>(null)
    const [pending, setPending] = useState(false)

    const submit = async (event: FormEvent) => {
        event.preventDefault()
        setPending(true)
        setError(null)

        try {
            await signIn(email, password)
        } catch (failure) {
            setError(failure instanceof ApiError ? failure : new ApiError(0))
        } finally {
            setPending(false)
        }
    }

    return (
        <AuthShell>
            <SystemPanel title="Identification">
                <p className="system-text dim" style={{ marginTop: 0 }}>
                    Le System attend tes identifiants.
                </p>

                <form className="form-grid" onSubmit={(event) => void submit(event)}>
                    <Field label="Adresse" errors={error?.violationsFor('email')}>
                        <TextInput
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            autoComplete="email"
                            required
                            autoFocus
                        />
                    </Field>

                    <Field label="Mot de passe" errors={error?.violationsFor('password')}>
                        <TextInput
                            type="password"
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            autoComplete="current-password"
                            required
                        />
                    </Field>

                    {error !== null && error.violations === null && (
                        <Alert>{signInMessage(error)}</Alert>
                    )}

                    <FormActions>
                        <Button variant="primary" submit disabled={pending}>
                            {pending ? 'Entrer…' : 'Entrer'}
                        </Button>
                    </FormActions>
                </form>

                <p className="system-text dim" style={{ marginBottom: 0 }}>
                    Pas encore de compte ? <Link to="/inscription">S’éveiller</Link>
                </p>
            </SystemPanel>
        </AuthShell>
    )
}

function signInMessage(error: ApiError): string {
    if (error.code === 'wrong_audience') {
        return 'Ce compte est un compte d’administration : il ne sert que dans le back-office.'
    }

    if (error.code === 'account_deactivated') {
        return 'Ce compte est désactivé. Un administrateur doit le réactiver.'
    }

    if (error.status === 401) {
        return 'Adresse ou mot de passe incorrect.'
    }

    return humanise(error.code ?? 'Le System ne répond pas.')
}
