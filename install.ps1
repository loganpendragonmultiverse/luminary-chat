$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
php install.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
