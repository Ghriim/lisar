/** Thrown when the credentials are right but the account has no business in the back-office. */
export class NotAnAdministratorError extends Error {
    constructor() {
        super('not_an_administrator')

        this.name = 'NotAnAdministratorError'
    }
}
