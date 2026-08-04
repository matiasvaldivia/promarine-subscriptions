<#
Fix-Mojibake.ps1
Repara mojibake UTF-8 (secuencias mal interpretadas como Latin-1) en archivos blade y JS.
Lee en UTF-8 con BOM, reemplaza secuencias comunes, escribe UTF-8 SIN BOM.
Sin chars literales Unicode: usa [char]0xXX para construir los reemplazos.
#>
param(
    [string]$Path = ".\resources",
    [string[]]$Include = @("*.blade.php", "*.js"),
    [switch]$DryRun
)

# Mojibakes son secuencias de 2-3 bytes Latin-1 que se generaron al leer UTF-8 mal.
# Construyo cada mojibake como concat de [char]0xXX (donde XX es el byte Latin-1).
# Los reemplazos son chars Unicode correctos via [char]0x00XX.

$mojibake = [ordered]@{}
# Vocales minusculas con tilde
$mojibake["$([char]0xC3)$([char]0xAD)"] = [char]0xED   # í
$mojibake["$([char]0xC3)$([char]0xA1)"] = [char]0xE1   # á
$mojibake["$([char]0xC3)$([char]0xA9)"] = [char]0xE9   # é
$mojibake["$([char]0xC3)$([char]0xB3)"] = [char]0xF3   # ó
$mojibake["$([char]0xC3)$([char]0xBA)"] = [char]0xFA   # ú
$mojibake["$([char]0xC3)$([char]0xB1)"] = [char]0xF1   # ñ
$mojibake["$([char]0xC3)$([char]0xBC)"] = [char]0xFC   # ü
$mojibake["$([char]0xC3)$([char]0xA0)"] = [char]0xE0   # à
$mojibake["$([char]0xC3)$([char]0xA8)"] = [char]0xE8   # è
$mojibake["$([char]0xC3)$([char]0xAC)"] = [char]0xEC   # ì
$mojibake["$([char]0xC3)$([char]0xB2)"] = [char]0xF2   # ò
$mojibake["$([char]0xC3)$([char]0xB9)"] = [char]0xF9   # ù
$mojibake["$([char]0xC3)$([char]0xA4)"] = [char]0xE4   # ä
$mojibake["$([char]0xC3)$([char]0xB6)"] = [char]0xF6   # ö
$mojibake["$([char]0xC3)$([char]0xAB)"] = [char]0xEB   # ë
$mojibake["$([char]0xC3)$([char]0xAF)"] = [char]0xEF   # ï
$mojibake["$([char]0xC3)$([char]0xA7)"] = [char]0xE7   # ç

# Mayusculas
$mojibake["$([char]0xC3)$([char]0x81)"] = [char]0xC1   # Á
$mojibake["$([char]0xC3)$([char]0x89)"] = [char]0xC9   # É
$mojibake["$([char]0xC3)$([char]0x8D)"] = [char]0xCD   # Í
$mojibake["$([char]0xC3)$([char]0x93)"] = [char]0xD3   # Ó
$mojibake["$([char]0xC3)$([char]0x9A)"] = [char]0xDA   # Ú
$mojibake["$([char]0xC3)$([char]0x91)"] = [char]0xD1   # Ñ
$mojibake["$([char]0xC3)$([char]0x9C)"] = [char]0xDC   # Ü

# Signos tipograficos (3 bytes Latin-1 = 0xE2 0x80 0xXX)
$mojibake["$([char]0xE2)$([char]0x80)$([char]0x94)"] = [char]0x2014   # — em dash
$mojibake["$([char]0xE2)$([char]0x80)$([char]0x93)"] = [char]0x2013   # – en dash
$mojibake["$([char]0xE2)$([char]0x80)$([char]0xA6)"] = [char]0x2026   # … ellipsis
$mojibake["$([char]0xE2)$([char]0x80)$([char]0x99)"] = [char]0x2019   # ’ right single
$mojibake["$([char]0xE2)$([char]0x80)$([char]0x98)"] = [char]0x2018   # ‘ left single
$mojibake["$([char]0xE2)$([char]0x80)$([char]0x9C)"] = [char]0x201C   # “ left double
$mojibake["$([char]0xE2)$([char]0x80)$([char]0x9D)"] = [char]0x201D   # ” right double
$mojibake["$([char]0xE2)$([char]0x82)$([char]0xAC)"] = [char]0x20AC   # € euro

# Flechas
$mojibake["$([char]0xE2)$([char]0x86)$([char]0x93)"] = [char]0x2193   # ↓
$mojibake["$([char]0xE2)$([char]0x86)$([char]0x92)"] = [char]0x2190   # ←
$mojibake["$([char]0xE2)$([char]0x86)$([char]0x91)"] = [char]0x2191   # ↑
$mojibake["$([char]0xE2)$([char]0x86)$([char]0x90)"] = [char]0x2192   # →

# Checkmarks y simbolos
$mojibake["$([char]0xE2)$([char]0x9C)$([char]0x93)"] = [char]0x2713   # ✓
$mojibake["$([char]0xE2)$([char]0x9C)$([char]0x97)"] = [char]0x2717   # ✗
$mojibake["$([char]0xE2)$([char]0x96)$([char]0xA1)"] = [char]0x25A1   # □
$mojibake["$([char]0xE2)$([char]0x96)$([char]0xA3)"] = [char]0x25A3   # ▣
$mojibake["$([char]0xE2)$([char]0x97)$([char]0x86)"] = [char]0x25C6   # ◆
$mojibake["$([char]0xE2)$([char]0x97)$([char]0x8F)"] = [char]0x25CF   # ●

# Latin-1 residual: espacio non-breaking, etc
$mojibake["$([char]0xC2)$([char]0xA0)"] = ' '   # NBSP -> espacio normal
$mojibake["$([char]0xC2)$([char]0xA1)"] = [char]0xA1   # ¡
$mojibake["$([char]0xC2)$([char]0xBF)"] = [char]0xBF   # ¿
$mojibake["$([char]0xC2)$([char]0xA9)"] = [char]0xA9   # ©
$mojibake["$([char]0xC2)$([char]0xAE)"] = [char]0xAE   # ®
$mojibake["$([char]0xC2)$([char]0xB0)"] = [char]0xB0   # °
$mojibake["$([char]0xC2)$([char]0xAB)"] = [char]0xAB   # «
$mojibake["$([char]0xC2)$([char]0xBB)"] = [char]0xBB   # »
$mojibake["$([char]0xC2)$([char]0xA7)"] = [char]0xA7   # §
$mojibake["$([char]0xC2)$([char]0xA2)"] = [char]0xA2   # ¢
$mojibake["$([char]0xC2)$([char]0xA3)"] = [char]0xA3   # £

# Lista para mostrar
Write-Host "Mapeos configurados: $($mojibake.Count)" -ForegroundColor DarkCyan

$files = Get-ChildItem -Path $Path -Recurse -Include $Include -File -ErrorAction SilentlyContinue
$fixed = 0
$skipped = 0
$total = 0
foreach ($f in $files) {
    $content = Get-Content $f.FullName -Raw -ErrorAction SilentlyContinue
    if (-not $content) { $skipped++; continue }
    $original = $content
    $hits = 0
    foreach ($key in $mojibake.Keys) {
        $count = ([regex]::Matches($content, [regex]::Escape($key))).Count
        if ($count -gt 0) {
            $content = $content.Replace($key, $mojibake[$key])
            $hits += $count
        }
    }
    if ($content -ne $original) {
        $total += $hits
        $fixed++
        if ($DryRun) {
            Write-Host "[DRY] $($f.FullName.Substring($PWD.Path.Length + 1)) -> $hits reemplazos" -ForegroundColor Yellow
        } else {
            $utf8NoBom = New-Object System.Text.UTF8Encoding $false
            [System.IO.File]::WriteAllText($f.FullName, $content, $utf8NoBom)
            Write-Host "[OK]  $($f.FullName.Substring($PWD.Path.Length + 1)) -> $hits reemplazos" -ForegroundColor Green
        }
    } else {
        $skipped++
    }
}
Write-Host ""
Write-Host "=== Resumen ===" -ForegroundColor Cyan
Write-Host "Archivos escaneados: $($files.Count)"
Write-Host "Archivos modificados: $fixed"
Write-Host "Ocurrencias reemplazadas: $total"
Write-Host "Archivos sin cambios: $skipped"
if ($DryRun) { Write-Host "MODO DRY-RUN: nada modificado en disco" -ForegroundColor Yellow }
