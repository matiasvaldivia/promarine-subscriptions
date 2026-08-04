# CbMemory — Workaround para codebase-memory-mcp CLI roto

## Contexto

El binario `codebase-memory-mcp` v0.8.1 (DeusData/codebase-memory-mcp) tiene un bug
en Windows: el subcomando `cli <tool>` no parsea los argumentos JSON correctamente.
Solo funcionan `list_projects` y `config`. El resto (`get_graph_schema`,
`search_graph`, `get_code_snippet`, `trace_path`, `get_architecture`,
`search_code`, `index_repository`, etc.) devuelven `{"error":"project not found
or not indexed"}` aunque el proyecto esté indexado.

`update -y` no funciona (no hay feed). `install --force` borra los 17 .db pero
no garantiza fix (el bug es de CLI, no de datos).

Este módulo consulta los `.db` SQLite directamente, evitando el binario.

## Instalación

Los `.db` están en `C:\Users\pc\.cache\codebase-memory-mcp\`. Este módulo ya
los encuentra solo.

```powershell
. E:\promarine-subscriptions\.mavis\CbMemory.ps1
```

`sqlite3.exe` debe estar en:
`C:\Users\pc\AppData\Local\Microsoft\WinGet\Packages\Google.PlatformTools_Microsoft.Winget.Source_8wekyb3d8bbwe\platform-tools\sqlite3.exe`
(ya estaba, del Android Platform Tools).

## Funciones

| Función | Equivalente binario | Descripción |
|---|---|---|
| `Get-CbProject` | `list_projects` | Lista los 16 proyectos indexados con stats (nodes, edges, indexed_at) |
| `Get-CbGraphSchema` | `get_graph_schema` | Labels (Module, File, Class, Method, Function, etc) y edge types (DEFINES, CALLS, IMPORTS, etc) |
| `Search-CbNode` | `search_graph` | Busca nodos por pattern, label, file. Ej: `Search-CbNode -Pattern 'Subscription' -Label Class` |
| `Get-CbCodeSnippet` | `get_code_snippet` | Lee el código fuente de un nodo por qualified_name |
| `Trace-CbCall` | `trace_path` | Call chains in/out/both con depth configurable |
| `Get-CbArchitecture` | `get_architecture` | Resumen: labels, top archivos, top funciones, dead code candidates |
| `Search-CbCode` | `search_code` | Grep de texto en archivos del proyecto |
| `Get-CbHotspots` | (extra) | Top fan-in / fan-out (auditoría de calidad) |
| `Get-CbDeadCode` | (extra) | Funciones sin callers |
| `Show-CbHelp` | (extra) | Ayuda rápida |

Todos aceptan `-Project <name>` (default: `E-promarine-subscriptions`, el workspace actual).

## Ejemplos

```powershell
# 1. Ver qué hay indexado
Get-CbProject

# 2. Schema del proyecto
Get-CbGraphSchema

# 3. Buscar una clase y leerla
$cls = Search-CbNode -Pattern 'SubscriptionPlan' -Label Class
$cls | Format-Table
Get-CbCodeSnippet -QualifiedName $cls[0].qualified_name

# 4. Call chains
Trace-CbCall -FunctionName 'tap' -Direction both -Depth 2

# 5. Buscar un texto
Search-CbCode -Pattern 'MockSubscription' -FilePattern '*.php'

# 6. Resumen ejecutivo
Get-CbArchitecture -TopN 10

# 7. Hotspots de calidad
Get-CbHotspots -By fanin -TopN 5
Get-CbHotspots -By fanout -TopN 5

# 8. Dead code
Get-CbDeadCode -Limit 20
```

## Pipelines

Las funciones devuelven objetos PowerShell, así que se pueden componer:

```powershell
# Buscar todas las classes que matchean "Mock" y leer su código
Search-CbNode -Pattern 'Mock' -Label Class -Limit 5 |
    ForEach-Object { Get-CbCodeSnippet -QualifiedName $_.qualified_name }

# Encontrar dead code en app/Services
Get-CbDeadCode -Limit 100 | Where-Object { $_.file_path -like 'app/Services/*' }

# Trace de las top 3 funciones más llamadas
Get-CbHotspots -TopN 3 -By fanin |
    ForEach-Object { Trace-CbCall -FunctionName $_.qualified_name -Direction inbound -Depth 1 }
```

## Limitaciones vs el binario

- **Sin búsqueda semántica por vectores** (las tablas `node_vectors` y
  `token_vectors` están pobladas pero el módulo no las usa — son BLOB y
  requieren el motor de similitud del binario).
- **Sin parseo incremental de cambios** (el binario tiene un watcher; este
  módulo lee snapshots).
- **Sin HTTP viz** (la UI en localhost:9749 requiere la variante `-ui` del
  binario, no la standard).
- **Call chains BFS, no DFS** (idem en comportamiento al binario, pero
  diferentes heurísticas de paginación).

## Schema del DB (para queries avanzadas)

Si querés armar tus propias queries, las tablas principales son:

- `projects(name PK, indexed_at, root_path)`
- `nodes(id PK, project, label, name, qualified_name UNIQUE(project,qualified_name), file_path, start_line, end_line, properties JSON)`
- `edges(id PK, project, source_id → nodes, target_id → nodes, type, properties JSON)`
- `file_hashes(project, rel_path, sha256, mtime_ns, size)`
- `project_summaries(project PK, summary, source_hash, created_at, updated_at)` — VACÍA en esta DB
- `nodes_fts` — FTS5 sobre name, qualified_name, label, file_path (no usado por este módulo todavía)

Helper para queries custom:

```powershell
. E:\promarine-subscriptions\.mavis\CbMemory.ps1
$db = Get-CbDbPath -Project E-promarine-subscriptions
Invoke-CbQuery -Database $db -Query "SELECT ..." -Columns col1,col2,col3
```
