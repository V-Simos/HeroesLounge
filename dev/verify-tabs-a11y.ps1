$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repoRoot = Split-Path -Parent $PSScriptRoot
$themeRoot = Join-Path $repoRoot 'themes\heroeslounge-next'
$scriptPath = Join-Path $themeRoot 'assets\js\lounge.js'

function Assert-True {
    param(
        [Parameter(Mandatory)]
        [bool] $Condition,
        [Parameter(Mandatory)]
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

function Assert-Contains {
    param(
        [Parameter(Mandatory)]
        [string] $Text,
        [Parameter(Mandatory)]
        [string] $Needle,
        [Parameter(Mandatory)]
        [string] $Message
    )

    Assert-True ($Text.Contains($Needle)) $Message
}

Assert-True (Test-Path -LiteralPath $scriptPath -PathType Leaf) "Missing shared lounge.js: $scriptPath"
$script = Get-Content -Raw -LiteralPath $scriptPath

# A regression that drops any of these branches breaks the APG keyboard contract:
# only one tab is in the page Tab sequence, and arrows/Home/End both focus and
# activate another tab.
foreach ($needle in @(
    'function activateTab(',
    'function initializeTabs(',
    "case 'ArrowLeft':",
    "case 'ArrowRight':",
    "case 'Home':",
    "case 'End':",
    '.focus()',
    "setAttribute('tabindex'",
    "setAttribute('aria-selected'"
)) {
    Assert-Contains $script $needle "Shared tabs behavior is incomplete: $needle"
}

$tabFiles = Get-ChildItem -LiteralPath $themeRoot -Recurse -File -Filter '*.htm' |
    Where-Object { (Get-Content -Raw -LiteralPath $_.FullName).Contains('data-tabs') }

Assert-True ($tabFiles.Count -gt 0) 'No lounge.js tab consumers were found.'

foreach ($file in $tabFiles) {
    $source = Get-Content -Raw -LiteralPath $file.FullName
    $markup = [regex]::Replace(
        $source,
        '\{#.*?#\}',
        '',
        [Text.RegularExpressions.RegexOptions]::Singleline
    )

    Assert-True ([regex]::IsMatch($markup, 'role="tablist"')) "$($file.FullName) lacks a tablist role."

    $tabs = [regex]::Matches(
        $markup,
        '<button\b(?=[^>]*\brole="tab")[^>]*>',
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    $panels = [regex]::Matches(
        $markup,
        '<(?:div|section)\b(?=[^>]*\brole="tabpanel")[^>]*>',
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )

    Assert-True ($tabs.Count -gt 0) "$($file.FullName) has data-tabs but no role=tab buttons."
    Assert-True ($panels.Count -gt 0) "$($file.FullName) has data-tabs but no role=tabpanel panels."
    Assert-True ($tabs.Count -eq $panels.Count) "$($file.FullName) must emit one panel template per tab template."

    foreach ($tab in $tabs) {
        foreach ($attribute in @('id', 'aria-controls', 'aria-selected', 'tabindex', 'data-tab-target')) {
            Assert-True ([regex]::IsMatch($tab.Value, ('\b' + [regex]::Escape($attribute) + '="'))) "$($file.FullName) has a tab without $attribute."
        }
    }

    foreach ($panel in $panels) {
        foreach ($attribute in @('id', 'aria-labelledby')) {
            Assert-True ([regex]::IsMatch($panel.Value, ('\b' + [regex]::Escape($attribute) + '="'))) "$($file.FullName) has a tabpanel without $attribute."
        }
    }
}

Write-Output "ARIA tabs contracts pass ($($tabFiles.Count) consumers)."
