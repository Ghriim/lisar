import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'
import { humanise } from '../api/violations'
import { Alert, AuthShell, Button, EmailField, Form, FormActions, PasswordField, SystemPanel } from '../components'

export function LoginPage() {
    const { signIn } = useAuth()
    const [email, setEmail] = useState('')
    const [password, setPassword] = useState('')
    const [error, setError] = useState<ApiError | null>(null)
    const [pending, setPending] = useState(false)

    const submit = async () => {
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
            <SystemPanel titleInside title="Identification">
                <p className="system-text dim" style={{ marginTop: 0 }}>
                    Le System attend tes identifiants.
                </p>

                <Form onSubmit={submit}>
                    <EmailField label="Adresse" isMandatory errors={error?.violationsFor('email')} value={email} onChange={setEmail} autoFocus />

                    <PasswordField
                        label="Mot de passe"
                        isMandatory
                        errors={error?.violationsFor('password')}
                        value={password}
                        onChange={setPassword}
                        autoComplete="current-password"
                    />

                    {error !== null && error.violations === null && (
                        <Alert>{signInMessage(error)}</Alert>
                    )}

                    <FormActions>
                        <Button variant="primary" submit disabled={pending}>
                            {pending ? 'Entrer…' : 'Entrer'}
                        </Button>
                    </FormActions>
                </Form>

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
