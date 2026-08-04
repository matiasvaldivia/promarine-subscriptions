# CbMemory.ps1
# Workaround para codebase-memory-mcp CLI roto en Windows (DeusData/codebase-memory-mcp v0.8.1)
# Funciones equivalentes a las del binario pero consultan los .db SQLite directo.
# Storage: C:\Users\pc\.cache\codebase-memory-mcp\<ProjectName>.db
#
# Uso:
#   . E:\promarine-subscriptions\.mavis\CbMemory.ps1
#   Get-CbProject
#   Search-CbNode -Project E-promarine-subscriptions -Pattern '.*Subscription.*' -Label Class
#   Get-CbCodeSnippet -Project E-promarine-subscriptions -QualifiedName 'E-promarine-subscriptions.app.Models.SubscriptionPlan.SubscriptionPlan'
#   Trace-CbCall -Project E-promarine-subscriptions -FunctionName 'syncScrollLock' -Direction both -Depth 2
#
# Proyecto por defecto: E-promarine-subscriptions (workspace actual).

$script:CbSqlitePath = 'C:\Users\pc\AppData\Local\Microsoft\WinGet\Packages\Google.PlatformTools_Microsoft.Winget.Source_8wekyb3d8bbwe\platform-tools\sqlite3.exe'
$script:CbCacheDir = 'C:\Users\pc\.cache\codebase-memory-mcp'
$script:CbDefaultProject = 'E-promarine-subscriptions'

# ----------------------------------------------------------------------------
# Helpers privados
# ----------------------------------------------------------------------------

function Get-CbDbPath {
    [CmdletBinding()]
    param([Parameter(Mandatory)][string]$Project)
    $path = Join-Path $script:CbCacheDir "$Project.db"
    if (-not (Test-Path $path)) { throw "DB no encontrada: $path" }
    return $path
}

function Get-CbProjectRoot {
    [CmdletBinding()]
    param([Parameter(Mandatory)][string]$Project)
    $db = Get-CbDbPath -Project $Project
    $root = & $script:CbSqlitePath -separator '|' $db "SELECT root_path FROM projects WHERE name='$Project';" 2>&1
    if (-not $root -or $root -match 'Error|Exception') { throw "No se pudo resolver root_path para $Project" }
    $root = $root.Trim() -replace '/', [IO.Path]::DirectorySeparatorChar
    return $root
}

function Invoke-CbQuery {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)][string]$Database,
        [Parameter(Mandatory)][string]$Query,
        [Parameter(Mandatory)][string[]]$Columns
    )
    $out = & $script:CbSqlitePath -separator '|' $Database $Query 2>&1
    if ($LASTEXITCODE -ne 0) { throw "sqlite3 exit=$LASTEXITCODE en $Database. Query: $Query. Error: $out" }
    $rows = New-Object System.Collections.Generic.List[object]
    foreach ($line in $out) {
        if ([string]::IsNullOrWhiteSpace($line)) { continue }
        $parts = $line.TrimEnd() -split '\|', -1
        $obj = [ordered]@{}
        for ($i = 0; $i -lt $Columns.Count; $i++) {
            $val = if ($i -lt $parts.Count) { [string]$parts[$i] } else { '' }
            $nameSuggestsNumber = $Columns[$i] -match '^(id|count|n_|fan|size|line|mtime|seq|in|out)$' -or $Columns[$i] -match '_(id|count|in|out|size|line|mtime|seq)$'
            $valueIsNumber = $val -match '^-?\d+$'
            $textColumns = 'qualified_name|file_path|name|label|type|root_path|properties|source|dir|indexed_at|rel_path|sha256|text|path|line|column|file'
            if ($nameSuggestsNumber -and $valueIsNumber) {
                $obj[$Columns[$i]] = [long]$val
            } elseif ($valueIsNumber -and $Columns[$i] -notmatch $textColumns) {
                $obj[$Columns[$i]] = [long]$val
            } else {
                $obj[$Columns[$i]] = $val
            }
        }
        $rows.Add([PSCustomObject]$obj) | Out-Null
    }
    return $rows.ToArray()
}

function Get-CbProjectsFromCache {
    [CmdletBinding()]
    param()
    $projects = @()
    Get-ChildItem -Path $script:CbCacheDir -Filter '*.db' -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -ne '_config.db' } |
        ForEach-Object {
            $name = $_.BaseName
            try {
                $rows = Invoke-CbQuery -Database $_.FullName -Query "SELECT name, indexed_at, root_path FROM projects;" -Columns 'name','indexed_at','root_path'
                $stats = Invoke-CbQuery -Database $_.FullName -Query "SELECT (SELECT COUNT(*) FROM nodes) AS n, (SELECT COUNT(*) FROM edges) AS e;" -Columns 'n','e'
                $projects += [PSCustomObject]@{
                    Name      = $rows[0].name
                    IndexedAt = $rows[0].indexed_at
                    RootPath  = $rows[0].root_path
                    Nodes     = $stats[0].n
                    Edges     = $stats[0].e
                    SizeBytes = $_.Length
                }
            } catch {
                Write-Warning "Skipping $name : $_"
            }
        }
    return $projects | Sort-Object Name
}

# ----------------------------------------------------------------------------
# API publica
# ----------------------------------------------------------------------------

function Get-CbProject {
<#
.SYNOPSIS
Lista todos los proyectos indexados (equivalente a list_projects del binario roto).
.EXAMPLE
Get-CbProject
.EXAMPLE
Get-CbProject | Where-Object Nodes -gt 1000 | Format-Table Name, Nodes, Edges
#>
    [CmdletBinding()]
    param()
    Get-CbProjectsFromCache
}

function Get-CbGraphSchema {
<#
.SYNOPSIS
Devuelve labels y edge types disponibles en un proyecto (equivalente a get_graph_schema).
.PARAMETER Project
Nombre del proyecto (default: el workspace actual).
.EXAMPLE
Get-CbGraphSchema
#>
    [CmdletBinding()]
    param([string]$Project = $script:CbDefaultProject)
    $db = Get-CbDbPath -Project $Project
    Write-Host "=== Project: $Project ===" -ForegroundColor Cyan
    Write-Host "--- Labels (con count) ---" -ForegroundColor Yellow
    $labels = Invoke-CbQuery -Database $db -Query "SELECT label, COUNT(*) AS count FROM nodes GROUP BY label ORDER BY count DESC;" -Columns 'label','count'
    $labels | Format-Table -AutoSize
    Write-Host "--- Edge types (con count) ---" -ForegroundColor Yellow
    $edges = Invoke-CbQuery -Database $db -Query "SELECT type, COUNT(*) AS count FROM edges GROUP BY type ORDER BY count DESC;" -Columns 'type','count'
    $edges | Format-Table -AutoSize
    return [PSCustomObject]@{ Project = $Project; Labels = $labels; EdgeTypes = $edges }
}

function Search-CbNode {
<#
.SYNOPSIS
Busca nodos (funciones, clases, modulos, etc) por pattern, label y file (equivalente a search_graph).
.PARAMETER Project
Nombre del proyecto.
.PARAMETER Pattern
Regex o substring a buscar en name, qualified_name o file_path. Case-insensitive.
.PARAMETER Label
Filtrar por label exacto (Function, Class, Method, Module, File, etc).
.PARAMETER FilePattern
Regex contra file_path.
.PARAMETER Limit
Maximo de resultados (default 50).
.EXAMPLE
Search-CbNode -Pattern '.*Subscription.*'
.EXAMPLE
Search-CbNode -Pattern 'tap|sync' -Label Function -Limit 10
.EXAMPLE
Search-CbNode -FilePattern 'app/Services/.*' -Limit 30
#>
    [CmdletBinding()]
    param(
        [string]$Project = $script:CbDefaultProject,
        [string]$Pattern,
        [string]$Label,
        [string]$FilePattern,
        [int]$Limit = 50
    )
    $db = Get-CbDbPath -Project $Project
    $where = @()
    if ($Pattern) { $where += "(name LIKE '%$Pattern%' COLLATE NOCASE OR qualified_name LIKE '%$Pattern%' COLLATE NOCASE)" }
    if ($Label) { $where += "label = '$Label'" }
    if ($FilePattern) { $where += "file_path LIKE '%$FilePattern%' COLLATE NOCASE" }
    if (-not $where) { $where += "1=1" }
    $sql = "SELECT id, label, name, qualified_name, file_path, start_line, end_line FROM nodes WHERE ($($where -join ' AND ')) ORDER BY label, name LIMIT $Limit;"
    Invoke-CbQuery -Database $db -Query $sql -Columns 'id','label','name','qualified_name','file_path','start_line','end_line'
}

function Get-CbCodeSnippet {
<#
.SYNOPSIS
Lee el codigo fuente de un nodo por qualified_name (equivalente a get_code_snippet).
.PARAMETER Project
Nombre del proyecto.
.PARAMETER QualifiedName
qualified_name exacto del nodo. Usar Search-CbNode primero para descubrirlo.
.PARAMETER Sibling
Si el nodo no matchea exacto, devuelve sugerencias (default true).
.EXAMPLE
Get-CbCodeSnippet -QualifiedName 'E-promarine-subscriptions.app.Models.SubscriptionPlan.SubscriptionPlan'
#>
    [CmdletBinding()]
    param(
        [string]$Project = $script:CbDefaultProject,
        [Parameter(Mandatory)][string]$QualifiedName,
        [bool]$Sibling = $true
    )
    $db = Get-CbDbPath -Project $Project
    $root = Get-CbProjectRoot -Project $Project
    $escapedQN = $QualifiedName -replace "'", "''"
    $rows = Invoke-CbQuery -Database $db -Query "SELECT label, name, qualified_name, file_path, start_line, end_line, properties FROM nodes WHERE qualified_name = '$escapedQN' COLLATE NOCASE;" -Columns 'label','name','qualified_name','file_path','start_line','end_line','properties'
    if (-not $rows -and $Sibling) {
        Write-Warning "qualified_name exacto no encontrado. Buscando sugerencias con LIKE..."
        $short = ($QualifiedName -split '\.')[-1]
        $sug = Search-CbNode -Project $Project -Pattern $short -Limit 10
        return $sug
    }
    if (-not $rows) { return $null }
    $n = $rows[0]
    $file = Join-Path $root ($n.file_path -replace '/', [IO.Path]::DirectorySeparatorChar)
    if (-not (Test-Path $file)) { Write-Warning "Archivo no encontrado: $file"; return $n }
    $lines = Get-Content -Path $file -ErrorAction SilentlyContinue
    if (-not $lines) { Write-Warning "Archivo vacio o ilegible: $file"; return $n }
    $start = [Math]::Max(0, $n.start_line - 1)
    $count = $n.end_line - $n.start_line + 1
    if ($count -le 0) { $count = 1 }
    $snippet = $lines[$start..([Math]::Min($start + $count - 1, $lines.Count - 1))]
    [PSCustomObject]@{
        Label         = $n.label
        Name          = $n.name
        QualifiedName = $n.qualified_name
        FilePath      = $n.file_path
        StartLine     = $n.start_line
        EndLine       = $n.end_line
        Properties    = $n.properties
        Source        = ($snippet -join "`n")
    }
}

function Trace-CbCall {
<#
.SYNOPSIS
Traza call chains de una funcion: quien la llama (inbound), que llama ella (outbound), o ambos.
Equivalente a trace_path del binario.
.PARAMETER Project
Nombre del proyecto.
.PARAMETER FunctionName
qualified_name (exacto) o nombre corto (busca con LIKE).
.PARAMETER Direction
'inbound' (quien me llama), 'outbound' (que llamo yo), 'both' (default).
.PARAMETER Depth
Niveles de recursividad (default 2, max 4).
.EXAMPLE
Trace-CbCall -FunctionName 'syncScrollLock' -Direction both -Depth 2
.EXAMPLE
Trace-CbCall -FunctionName 'SubscriptionPlan' -Direction inbound
#>
    [CmdletBinding()]
    param(
        [string]$Project = $script:CbDefaultProject,
        [Parameter(Mandatory)][string]$FunctionName,
        [ValidateSet('inbound','outbound','both')][string]$Direction = 'both',
        [ValidateRange(1,4)][int]$Depth = 2
    )
    $db = Get-CbDbPath -Project $Project
    $escapedFN = $FunctionName -replace "'", "''"
    # Resolver nombre exacto si no es qualified. Preferir Class > Method > Function > Route > otros.
    $exact = Invoke-CbQuery -Database $db -Query "SELECT qualified_name FROM nodes WHERE qualified_name = '$escapedFN' COLLATE NOCASE LIMIT 1;" -Columns 'qualified_name'
    if (-not $exact) {
        $sql = @"
SELECT qualified_name, label FROM nodes
WHERE name = '$escapedFN' COLLATE NOCASE OR qualified_name LIKE '%$escapedFN%' COLLATE NOCASE
ORDER BY CASE label
    WHEN 'Class' THEN 1 WHEN 'Method' THEN 2 WHEN 'Function' THEN 3
    WHEN 'Route' THEN 4 WHEN 'Interface' THEN 5
    WHEN 'Variable' THEN 6 WHEN 'Section' THEN 7
    ELSE 99 END
LIMIT 1;
"@
        $exact = Invoke-CbQuery -Database $db -Query $sql -Columns 'qualified_name','label'
    }
    if (-not $exact) { Write-Warning "No se encontro la funcion: $FunctionName"; return @() }
    $qn = $exact[0].qualified_name
    Write-Host "Tracing: $qn (direction=$Direction, depth=$Depth)" -ForegroundColor Cyan
    $results = @()
    $visited = [System.Collections.Generic.HashSet[string]]::new()
    $queue = [System.Collections.Queue]::new()
    $queue.Enqueue(@{ QN = $qn; Level = 0; Dir = 'self' })
    while ($queue.Count -gt 0) {
        $cur = $queue.Dequeue()
        if ($visited.Contains($cur.QN)) { continue }
        $visited.Add($cur.QN) | Out-Null
        $indent = '  ' * $cur.Level
        $results += [PSCustomObject]@{
            Level    = $cur.Level
            Direction = $cur.Dir
            Indent   = $indent
            QualifiedName = $cur.QN
        }
        if ($cur.Level -ge $Depth) { continue }
        $curEsc = $cur.QN -replace "'", "''"
        if ($Direction -in 'inbound','both') {
            $sql = "SELECT n1.qualified_name AS qn FROM edges e JOIN nodes n1 ON n1.id = e.source_id JOIN nodes n2 ON n2.id = e.target_id WHERE n2.qualified_name = '$curEsc' AND e.type = 'CALLS';"
            foreach ($r in (Invoke-CbQuery -Database $db -Query $sql -Columns 'qn')) {
                $queue.Enqueue(@{ QN = $r.qn; Level = $cur.Level + 1; Dir = 'inbound' })
            }
        }
        if ($Direction -in 'outbound','both') {
            $sql = "SELECT n2.qualified_name AS qn FROM edges e JOIN nodes n1 ON n1.id = e.source_id JOIN nodes n2 ON n2.id = e.target_id WHERE n1.qualified_name = '$curEsc' AND e.type = 'CALLS';"
            foreach ($r in (Invoke-CbQuery -Database $db -Query $sql -Columns 'qn')) {
                $queue.Enqueue(@{ QN = $r.qn; Level = $cur.Level + 1; Dir = 'outbound' })
            }
        }
    }
    $results | Format-Table Level, Direction, Indent, QualifiedName -AutoSize
    return $results
}

function Get-CbArchitecture {
<#
.SYNOPSIS
Resumen de alto nivel del proyecto: labels, top archivos, top funciones por fan-in, dead code candidates.
Equivalente parcial a get_architecture (project_summaries esta vacio en esta DB, asi que reconstruimos on-demand).
.PARAMETER Project
Nombre del proyecto.
.PARAMETER TopN
Cantidad de top entidades a mostrar (default 10).
.EXAMPLE
Get-CbArchitecture
.EXAMPLE
Get-CbArchitecture -Project E-PPOS-Platform -TopN 20
#>
    [CmdletBinding()]
    param(
        [string]$Project = $script:CbDefaultProject,
        [int]$TopN = 10
    )
    $db = Get-CbDbPath -Project $Project
    $root = Get-CbProjectRoot -Project $Project
    Write-Host "=== Arquitectura: $Project ===" -ForegroundColor Cyan
    Write-Host "Root: $root" -ForegroundColor Gray
    Write-Host ""
    Write-Host "--- Labels ---" -ForegroundColor Yellow
    Invoke-CbQuery -Database $db -Query "SELECT label, COUNT(*) AS count FROM nodes GROUP BY label ORDER BY count DESC;" -Columns 'label','count' | Format-Table -AutoSize
    Write-Host "--- Edge types ---" -ForegroundColor Yellow
    Invoke-CbQuery -Database $db -Query "SELECT type, COUNT(*) AS count FROM edges GROUP BY type ORDER BY count DESC;" -Columns 'type','count' | Format-Table -AutoSize
    Write-Host "--- Top $TopN archivos por # de funciones ---" -ForegroundColor Yellow
    Invoke-CbQuery -Database $db -Query "SELECT file_path, COUNT(*) AS fn_count FROM nodes WHERE label IN ('Function','Method') AND file_path != '' GROUP BY file_path ORDER BY fn_count DESC LIMIT $TopN;" -Columns 'file_path','fn_count' | Format-Table -AutoSize
    Write-Host "--- Top $TopN funciones por fan-in (mas llamadas) ---" -ForegroundColor Yellow
    $rows1 = Invoke-CbQuery -Database $db -Query "SELECT n.qualified_name, COUNT(e.id) AS fan_in FROM nodes n LEFT JOIN edges e ON e.target_id = n.id AND e.type = 'CALLS' WHERE n.label IN ('Function','Method') GROUP BY n.id ORDER BY fan_in DESC LIMIT $TopN;" -Columns 'qualified_name','fan_in'
    $rows1 | Select-Object @{N='qualified_name';E={ if ($_.qualified_name.Length -gt 70) { $_.qualified_name.Substring(0, 67) + '...' } else { $_.qualified_name } }}, fan_in | Format-Table -AutoSize
    Write-Host "--- Top $TopN funciones por fan-out (mas llamadoras) ---" -ForegroundColor Yellow
    $rows2 = Invoke-CbQuery -Database $db -Query "SELECT n.qualified_name, COUNT(e.id) AS fan_out FROM nodes n LEFT JOIN edges e ON e.source_id = n.id AND e.type = 'CALLS' WHERE n.label IN ('Function','Method') GROUP BY n.id ORDER BY fan_out DESC LIMIT $TopN;" -Columns 'qualified_name','fan_out'
    $rows2 | Select-Object @{N='qualified_name';E={ if ($_.qualified_name.Length -gt 70) { $_.qualified_name.Substring(0, 67) + '...' } else { $_.qualified_name } }}, fan_out | Format-Table -AutoSize
    Write-Host "--- Dead code candidates (sin callers, no entry points) ---" -ForegroundColor Yellow
    $rows3 = Invoke-CbQuery -Database $db -Query "SELECT n.qualified_name, n.file_path FROM nodes n LEFT JOIN edges e ON e.target_id = n.id AND e.type = 'CALLS' WHERE n.label IN ('Function','Method') AND e.id IS NULL AND n.qualified_name NOT LIKE '%.%main%' AND n.qualified_name NOT LIKE '%test%' AND n.qualified_name NOT LIKE '%Test%' LIMIT $TopN;" -Columns 'qualified_name','file_path'
    $rows3 | Select-Object @{N='qualified_name';E={ if ($_.qualified_name.Length -gt 70) { $_.qualified_name.Substring(0, 67) + '...' } else { $_.qualified_name } }}, file_path | Format-Table -AutoSize
}

function Search-CbCode {
<#
.SYNOPSIS
Busqueda de texto en archivos del proyecto (equivalente a search_code, sin augment de grafo).
Usa Get-ChildItem + Select-String para evitar problemas de Select-String con -Path a directorios.
.PARAMETER Project
Nombre del proyecto.
.PARAMETER Pattern
Texto o regex simple a buscar.
.PARAMETER FilePattern
Filtro de archivo (default '*', ej '*.php', '*.js').
.PARAMETER Limit
Maximo de matches a devolver (default 100).
.EXAMPLE
Search-CbCode -Pattern 'MockSubscription' -FilePattern '*.php'
.EXAMPLE
Search-CbCode -Pattern 'TODO' -FilePattern '*.php' -Limit 50
#>
    [CmdletBinding()]
    param(
        [string]$Project = $script:CbDefaultProject,
        [Parameter(Mandatory)][string]$Pattern,
        [string]$FilePattern = '*',
        [int]$Limit = 100
    )
    $root = Get-CbProjectRoot -Project $Project
    $files = @(Get-ChildItem -Path $root -Recurse -Filter $FilePattern -File -ErrorAction SilentlyContinue)
    if (-not $files) { Write-Host "Sin archivos '$FilePattern' en $root" -ForegroundColor Yellow; return @() }
    $results = New-Object System.Collections.Generic.List[object]
    foreach ($f in $files) {
        if ($results.Count -ge $Limit) { break }
        $matches = Select-String -Path $f.FullName -Pattern $Pattern -ErrorAction SilentlyContinue
        foreach ($m in $matches) {
            if ($results.Count -ge $Limit) { break }
            $rel = $f.FullName
            if ($rel.StartsWith($root)) { $rel = $rel.Substring($root.Length).TrimStart('\','/') }
            $results.Add([PSCustomObject]@{
                File   = $rel
                Line   = [long]$m.LineNumber
                Column = [long]$m.ColumnNumber
                Text   = $m.Line.Trim()
            }) | Out-Null
        }
    }
    if ($results.Count -eq 0) { Write-Host "Sin matches para '$Pattern' en $($files.Count) archivos ($FilePattern) en $root" -ForegroundColor Yellow; return @() }
    $results.ToArray() | Format-Table -AutoSize
}

function Get-CbHotspots {
<#
.SYNOPSIS
Top N funciones mas llamadas (high fan-in) o mas llamadoras (high fan-out). Util para auditoria de calidad.
.PARAMETER Project
Nombre del proyecto.
.PARAMETER TopN
Cantidad (default 15).
.PARAMETER By
'fanin' (default, mas llamadas) o 'fanout' (mas llamadoras).
.EXAMPLE
Get-CbHotspots
.EXAMPLE
Get-CbHotspots -By fanout -TopN 20
#>
    [CmdletBinding()]
    param(
        [string]$Project = $script:CbDefaultProject,
        [int]$TopN = 15,
        [ValidateSet('fanin','fanout')][string]$By = 'fanin'
    )
    $db = Get-CbDbPath -Project $Project
    $col = if ($By -eq 'fanin') { 'fan_in' } else { 'fan_out' }
    $srcCol = if ($By -eq 'fanin') { 'target_id' } else { 'source_id' }
    $sql = "SELECT n.qualified_name, n.file_path, COUNT(e.id) AS $col FROM nodes n LEFT JOIN edges e ON e.$srcCol = n.id AND e.type = 'CALLS' WHERE n.label IN ('Function','Method') GROUP BY n.id HAVING $col > 0 ORDER BY $col DESC LIMIT $TopN;"
    $rows = Invoke-CbQuery -Database $db -Query $sql -Columns 'qualified_name','file_path',$col
    $rows | Select-Object @{N='qualified_name';E={ if ($_.qualified_name.Length -gt 70) { $_.qualified_name.Substring(0, 67) + '...' } else { $_.qualified_name } }}, file_path, $col | Format-Table -AutoSize
}

function Get-CbDeadCode {
<#
.SYNOPSIS
Funciones/metodos sin callers. Excluye entry points, tests, y nombres comunes de bootstrap.
.PARAMETER Project
Nombre del proyecto.
.PARAMETER Limit
Maximo de candidatos a mostrar (default 50).
.EXAMPLE
Get-CbDeadCode
.EXAMPLE
Get-CbDeadCode -Limit 100 | Where-Object File -match 'app/Services'
#>
    [CmdletBinding()]
    param(
        [string]$Project = $script:CbDefaultProject,
        [int]$Limit = 50
    )
    $db = Get-CbDbPath -Project $Project
    $sql = "SELECT n.qualified_name, n.file_path, n.start_line, n.end_line FROM nodes n LEFT JOIN edges e ON e.target_id = n.id AND e.type = 'CALLS' WHERE n.label IN ('Function','Method') AND e.id IS NULL AND n.qualified_name NOT LIKE '%test%' AND n.qualified_name NOT LIKE '%Test%' AND n.qualified_name NOT LIKE '%.__construct%' AND n.qualified_name NOT LIKE '%.__destruct%' AND n.qualified_name NOT LIKE '%.__call%' AND n.qualified_name NOT LIKE '%main%' AND n.qualified_name NOT LIKE '%Main%' ORDER BY n.file_path, n.start_line LIMIT $Limit;"
    Invoke-CbQuery -Database $db -Query $sql -Columns 'qualified_name','file_path','start_line','end_line' | Format-Table -AutoSize
}

function Show-CbHelp {
<#
.SYNOPSIS
Muestra la ayuda rapida de las funciones del modulo.
.EXAMPLE
Show-CbHelp
#>
    [CmdletBinding()]
    param()
    Write-Host @"
=== CbMemory - Workaround para codebase-memory-mcp CLI roto en Windows ===

Funciones disponibles (equivalentes al binario):

  Get-CbProject         <-> list_projects       Lista proyectos con stats
  Get-CbGraphSchema     <-> get_graph_schema    Labels y edge types del proyecto
  Search-CbNode         <-> search_graph        Busca nodos (Function, Class, etc) por pattern/label
  Get-CbCodeSnippet     <-> get_code_snippet    Lee el source de un nodo por qualified_name
  Trace-CbCall          <-> trace_path          Call chains in/out/both con depth
  Get-CbArchitecture    <-> get_architecture    Resumen: labels, top files, top functions, dead code
  Search-CbCode         <-> search_code         Grep de texto en archivos del proyecto
  Get-CbHotspots        <-> (extra)             Top fan-in/fan-out
  Get-CbDeadCode        <-> (extra)             Funciones sin callers
  Show-CbHelp           <-> (extra)             Esta ayuda

Proyecto por defecto: $script:CbDefaultProject (cambiar con -Project en cada cmdlet)
Storage: $script:CbCacheDir

Quick start:
  . '$PSCommandPath'
  Get-CbProject
  Search-CbNode -Pattern 'Subscription' -Label Class
  Get-CbCodeSnippet -QualifiedName '<copiar de Search-CbNode>'
  Trace-CbCall -FunctionName 'syncScrollLock' -Direction both
  Get-CbArchitecture
"@ -ForegroundColor Cyan
}

# ----------------------------------------------------------------------------
# Init
# ----------------------------------------------------------------------------
if (-not (Test-Path $script:CbSqlitePath)) {
    Write-Warning "sqlite3.exe no encontrado en: $script:CbSqlitePath. Algunas funciones fallaran."
}
Write-Host "CbMemory cargado. Ejecuta Show-CbHelp para la ayuda." -ForegroundColor Green
