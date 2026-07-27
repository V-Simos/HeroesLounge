$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repoRoot = Split-Path -Parent $PSScriptRoot
$themeRoot = Join-Path $repoRoot 'themes\heroeslounge-next'
$scriptPath = Join-Path $themeRoot 'assets\js\lounge.js'
$behaviorVerifierPath = Join-Path $PSScriptRoot 'verify-tabs-behavior.js'

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

function Get-AttributeValue {
    param(
        [Parameter(Mandatory)]
        [string] $Tag,
        [Parameter(Mandatory)]
        [string] $Attribute,
        [Parameter(Mandatory)]
        [string] $Context
    )

    $match = [regex]::Match(
        $Tag,
        ('(?:^|\s)' + [regex]::Escape($Attribute) + '="([^"]*)"'),
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    Assert-True $match.Success "$Context lacks $Attribute."
    Assert-True (-not [string]::IsNullOrWhiteSpace($match.Groups[1].Value)) "$Context has an empty $Attribute."
    return $match.Groups[1].Value
}

Assert-True (Test-Path -LiteralPath $scriptPath -PathType Leaf) "Missing shared lounge.js: $scriptPath"
Assert-True (Test-Path -LiteralPath $behaviorVerifierPath -PathType Leaf) "Missing executable tab behavior verifier: $behaviorVerifierPath"

& node $behaviorVerifierPath
Assert-True ($LASTEXITCODE -eq 0) 'Executable shared tab behavior contracts failed.'

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

    $tabContracts = @($tabs | ForEach-Object {
        $context = "$($file.FullName) tab template"
        [pscustomobject]@{
            Tag = $_.Value
            Id = Get-AttributeValue $_.Value 'id' $context
            Controls = Get-AttributeValue $_.Value 'aria-controls' $context
            Selected = Get-AttributeValue $_.Value 'aria-selected' $context
            Tabindex = Get-AttributeValue $_.Value 'tabindex' $context
            Target = Get-AttributeValue $_.Value 'data-tab-target' $context
        }
    })
    $panelContracts = @($panels | ForEach-Object {
        $context = "$($file.FullName) tabpanel template"
        [pscustomobject]@{
            Tag = $_.Value
            Id = Get-AttributeValue $_.Value 'id' $context
            LabelledBy = Get-AttributeValue $_.Value 'aria-labelledby' $context
            Hidden = [regex]::IsMatch($_.Value, '\shidden(?:\s|>)', [Text.RegularExpressions.RegexOptions]::IgnoreCase)
        }
    })

    foreach ($tab in $tabContracts) {
        Assert-True ([regex]::IsMatch($tab.Tag, '\stype="button"(?:\s|>)', [Text.RegularExpressions.RegexOptions]::IgnoreCase)) "$($file.FullName) tab $($tab.Id) must use type=button."
        Assert-True ($tab.Controls -ceq $tab.Target) "$($file.FullName) tab $($tab.Id) aria-controls must equal data-tab-target."

        $matchingPanels = @($panelContracts | Where-Object { $_.Id -ceq $tab.Target })
        Assert-True ($matchingPanels.Count -eq 1) "$($file.FullName) tab $($tab.Id) must target exactly one panel id."
        Assert-True ($matchingPanels[0].LabelledBy -ceq $tab.Id) "$($file.FullName) panel $($matchingPanels[0].Id) must point back to tab $($tab.Id)."
    }

    foreach ($panel in $panelContracts) {
        Assert-True (@($tabContracts | Where-Object { $_.Id -ceq $panel.LabelledBy }).Count -eq 1) "$($file.FullName) panel $($panel.Id) must be labelled by exactly one tab id."
    }

    Assert-True (@($tabContracts.Id | Group-Object | Where-Object Count -gt 1).Count -eq 0) "$($file.FullName) contains duplicate tab id templates."
    Assert-True (@($panelContracts.Id | Group-Object | Where-Object Count -gt 1).Count -eq 0) "$($file.FullName) contains duplicate panel id templates."

    $hasOnlyConcreteState = @($tabContracts | Where-Object {
        $_.Selected -match '\{\{|\{%' -or $_.Tabindex -match '\{\{|\{%'
    }).Count -eq 0
    if ($hasOnlyConcreteState) {
        $selectedTabs = @($tabContracts | Where-Object { $_.Selected -ceq 'true' })
        $focusableTabs = @($tabContracts | Where-Object { $_.Tabindex -ceq '0' })
        $visiblePanels = @($panelContracts | Where-Object { -not $_.Hidden })

        Assert-True ($selectedTabs.Count -eq 1) "$($file.FullName) concrete tabs must have exactly one initial aria-selected=true."
        Assert-True ($focusableTabs.Count -eq 1) "$($file.FullName) concrete tabs must have exactly one initial tabindex=0."
        Assert-True ($visiblePanels.Count -eq 1) "$($file.FullName) concrete tabs must have exactly one initially visible panel."
        Assert-True ($selectedTabs[0].Id -ceq $focusableTabs[0].Id) "$($file.FullName) selected and focusable tabs must be the same."
        Assert-True ($selectedTabs[0].Target -ceq $visiblePanels[0].Id) "$($file.FullName) selected tab's panel must be the initially visible panel."
    }
}

Write-Output "ARIA tab structure contracts pass ($($tabFiles.Count) consumers)."
