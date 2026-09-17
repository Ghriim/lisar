# Environment variables

## Loading order

Each step overrides the previous one.

1. **`.env`** — the canonical, complete list, with a local-dev default for every variable.
   This file is the inventory: **a variable that is not in it does not exist**. A new variable
   goes here first, always.
2. **`docker-compose.yml`** — per-container overrides. Notably the `app-test` / `mysql-test` pair,
   which points at a dedicated database so a test run can never touch the working data.
3. **`.deploy/`** — Terraform/HCL per environment, then a secrets file, for stage and production.

`.env.local` is never committed and is meant for one developer's own overrides.

## The variables

| Variable | Default (local dev) | What it drives |
| --- | --- | --- |
| `APP_ENV` | `dev` | Symfony environment |
| `APP_SECRET` | *(empty)* | Symfony secret; set per environment in deployment |
| `APP_SHARE_DIR` | `var/share` | writable share directory |
| `DEFAULT_URI` | `http://localhost` | URL generation outside an HTTP context (CLI) |
| `DATABASE_URL` | `mysql://lisar:lisar@mysql:3306/lisar` | Doctrine connection; the `app-test` container points it at `mysql-test` / `lisar_test` |
| `HTTP_PORT` | `8080` | host port nginx is published on |
| `MYSQL_PORT` | `3306` | host port the dev database is published on |

## Xdebug

Xdebug is installed in the PHP image but idle (`xdebug.mode = off`). Turn it on per command:

- coverage — `make test-unit coverage=true`, which sets `XDEBUG_MODE=coverage`
- step debugging — `XDEBUG_MODE=debug` in front of the command
