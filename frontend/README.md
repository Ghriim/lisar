# frontend

Two independent React + TypeScript + Vite applications, both talking to the same API:

| Folder | What it is | Styling |
| --- | --- | --- |
| `website/` | the app users use | its own design, no component library |
| `admin/` | the back-office | Ant Design components |

Each one is installed and run on its own (`npm install`, `npm run dev`); they share no build.
