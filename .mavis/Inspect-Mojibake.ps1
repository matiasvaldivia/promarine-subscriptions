# Inspect-Mojibake.ps1 - Inspeccion segura sin chars literales problematicos
param([string]$File)

if (-not $File) { Write-Host "Uso: Inspect-Mojibake.ps1 -File <path>" -ForegroundColor Yellow; exit 1 }
if (-not (Test-Path $File)) { Write-Host "No existe: $File" -ForegroundColor Red; exit 1 }

$bytes = [System.IO.File]::ReadAllBytes($File)[0..5]
Write-Host "Primeros bytes: $($bytes -join ' ')"
if ($bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
    Write-Host "BOM UTF-8: SI" -ForegroundColor Green
} else {
    Write-Host "BOM UTF-8: NO"
}

# Leo con UTF-8 explicito
$content = [System.IO.File]::ReadAllText($File, [System.Text.Encoding]::UTF8)
Write-Host "Tamano: $($content.Length) chars"
Write-Host ""

# Lista de mojibakes a buscar (solo Latin-1 2-3 char sequences)
$mojibake2 = @{
    "$([char]0xC3)$([char]0xAD)" = "0xED  (i con tilde)"
    "$([char]0xC3)$([char]0xA1)" = "0xE1  (a con tilde)"
    "$([char]0xC3)$([char]0xA9)" = "0xE9  (e con tilde)"
    "$([char]0xC3)$([char]0xB3)" = "0xF3  (o con tilde)"
    "$([char]0xC3)$([char]0xBA)" = "0xFA  (u con tilde)"
    "$([char]0xC3)$([char]0xB1)" = "0xF1  (enie)"
}

$mojibake3 = @{
    "$([char]0xE2)$([char]0x80)$([char]0x94)" = "0x2014 (em dash)"
    "$([char]0xE2)$([char]0x80)$([char]0x93)" = "0x2013 (en dash)"
    "$([char]0xE2)$([char]0x86)$([char]0x93)" = "0x2193 (down arrow)"
    "$([char]0xE2)$([char]0x86)$([char]0x92)" = "0x2190 (left arrow)"
    "$([char]0xE2)$([char]0x86)$([char]0x91)" = "0x2191 (up arrow)"
    "$([char]0xE2)$([char]0x86)$([char]0x90)" = "0x2192 (right arrow)"
    "$([char]0xE2)$([char]0x9C)$([char]0x93)" = "0x2713 (check)"
}

Write-Host "=== Mojibakes de 2 chars ==="
foreach ($k in $mojibake2.Keys) {
    $c = ([regex]::Matches($content, [regex]::Escape($k))).Count
    if ($c -gt 0) { Write-Host "  $($mojibake2[$k]): $c" -ForegroundColor Yellow }
}
Write-Host ""
Write-Host "=== Mojibakes de 3 chars ==="
foreach ($k in $mojibake3.Keys) {
    $c = ([regex]::Matches($content, [regex]::Escape($k))).Count
    if ($c -gt 0) { Write-Host "  $($mojibake3[$k]): $c" -ForegroundColor Yellow }
}

# Mostrar primera linea con contenido problematico
$lines = $content -split "`n"
for ($i = 0; $i -lt [Math]::Min(5, $lines.Count); $i++) {
    $line = $lines[$i]
    if ($line -match "$([char]0xE2)") {
        Write-Host ""
        Write-Host "L$($i+1) hex: " -NoNewline
        $chars = $line.ToCharArray()
        for ($j = 0; $j -lt [Math]::Min(80, $chars.Count); $j++) {
            $b = [int]$chars[$j]
            if ($b -ge 32 -and $b -lt 127) {
                Write-Host $chars[$j] -NoNewline
            } else {
                Write-Host "[" -NoNewline
                Write-Host ("{0:X2}" -f $b) -NoNewline
                Write-Host "]" -NoNewline
            }
        }
        Write-Host ""
    }
}
