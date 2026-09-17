# frontend

Two independent React + TypeScript + Vite applications, both talking to the same API:

| Folder | What it is | Styling |
| --- | --- | --- |
| `website/` | the app users use | its own design, no component library |
| `admin/` | the back-office | Ant Design components |

Each one is installed and run on its own — `make start-website`, `make start-admin` — and they
share no build. They do share a backend and a refresh cookie, so the admin re-checks the role
of any session it restores.

The API client, the auth provider and the error wording are deliberately duplicated rather than
extracted: the two apps have different audiences, different lifecycles and different vocabulary
for the same error codes. A shared package would couple two things that are meant to diverge.
