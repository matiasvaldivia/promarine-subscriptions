$ErrorActionPreference = 'Stop'
$workspace = Split-Path -Parent $PSScriptRoot
$sourcePath = Join-Path $workspace 'laravel-bootstrap\.env'
$targetPath = Join-Path $workspace '.env'
$mailKeys = @('MAIL_MAILER','MAIL_SCHEME','MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_FROM_ADDRESS','MAIL_FROM_NAME')

$sourceValues = @{}
foreach ($line in [IO.File]::ReadAllLines($sourcePath)) {
    if ($line -match '^([^#=]+)=(.*)$' -and $mailKeys -contains $matches[1]) {
        $sourceValues[$matches[1]] = $matches[2]
    }
}

foreach ($required in @('MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD')) {
    if (-not $sourceValues.ContainsKey($required) -or [string]::IsNullOrWhiteSpace($sourceValues[$required])) {
        throw "Falta $required en laravel-bootstrap/.env"
    }
}

$sourceValues['MAIL_MAILER'] = 'smtp'
$sourceValues['MAIL_SCHEME'] = 'smtps'
$sourceValues['MAIL_FROM_ADDRESS'] = '"' + $sourceValues['MAIL_USERNAME'].Trim('"') + '"'
$sourceValues['MAIL_FROM_NAME'] = '"Promarine"'

$targetLines = [Collections.Generic.List[string]]::new()
foreach ($line in [IO.File]::ReadAllLines($targetPath)) {
    if ($line -notmatch '^(' + (($mailKeys | ForEach-Object { [regex]::Escape($_) }) -join '|') + ')=') {
        $targetLines.Add($line)
    }
}

$targetLines.Add('')
foreach ($key in $mailKeys) {
    $targetLines.Add("$key=$($sourceValues[$key])")
}

[IO.File]::WriteAllLines($targetPath, $targetLines, [Text.UTF8Encoding]::new($false))
Write-Output ('Configuración SMTP aplicada: ' + ($mailKeys -join ', '))
