$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# Phase 4 (static content wave) static contracts. Run from anywhere:
#   powershell -ExecutionPolicy Bypass -File dev\verify-static-content.ps1
# Checks the byte-verbatim body contract against the frozen theme, the
# RainLab.Pages manifest, the three CMS pages, the lowercase Bans override,
# the section menus, the static layout, and the lounge.js / pages.css hooks
# the legacy content vocabulary depends on.

$repoRoot = Split-Path -Parent $PSScriptRoot
$frozenDir = Join-Path $repoRoot 'themes\HeroesLounge-Theme\content\static-pages'
$themeDir = Join-Path $repoRoot 'themes\heroeslounge-next'
$bodyDir = Join-Path $themeDir 'content\static-pages'

function Assert-True {
    param([Parameter(Mandatory)] [bool] $Condition, [Parameter(Mandatory)] [string] $Message)
    if (-not $Condition) { throw $Message }
}

function Assert-Contains {
    param([Parameter(Mandatory)] [string] $Text, [Parameter(Mandatory)] [string] $Needle, [Parameter(Mandatory)] [string] $Message)
    Assert-True ($Text.Contains($Needle)) $Message
}

function Read-Lf {
    param([Parameter(Mandatory)] [string] $Path)
    Assert-True (Test-Path -LiteralPath $Path) "Missing file: $Path"
    return ([IO.File]::ReadAllText($Path)) -replace "`r", ''
}

# --- 1. Bodies: byte-identical to the frozen source after normalising ONLY the layout line.
$ported = @(
    'guides', 'guides-new-captains-guide-heroes-lounge', 'guides-scheduling-and-reporting-matches',
    'guides-signup-guide', 'guides-aram-signup-guide', 'guides-guide-scheduling-and-playing-your-frist-game',
    'guides-uploading-replays',
    'privacy-statement', 'general', 'playoff-rules', 'general-test', 'schedule', 'general-hall-fame',
    'division-s-ruleset', 'division-s-ruleset-division-s-playoffs', 'division-s-crew',
    'division-s-standings', 'division-s-schedule', 'division-s-qualifier-standings',
    'aram-league-ruleset', 'ruleset', 'tournament-ruleset',
    'faqpage', 'general-rules', 'general-staff'
)
$layoutLine = [regex] '(?m)^layout = "[^"]*"$'
foreach ($name in $ported) {
    $frozen = Read-Lf (Join-Path $frozenDir "$name.htm")
    $theme = Read-Lf (Join-Path $bodyDir "$name.htm")
    $expected = $layoutLine.Replace($frozen, 'layout = "static"', 1)
    Assert-True ($expected -eq $theme) "Body drifted from the frozen source (only the layout line may change): $name.htm"
    Assert-Contains $theme 'layout = "static"' "Body must use the static layout: $name.htm"
}
$extra = @(Get-ChildItem -LiteralPath $bodyDir -Filter '*.htm' | Where-Object { $ported -notcontains $_.BaseName })
Assert-True ($extra.Count -eq 0) ("Unexpected static bodies not covered by this contract: " + (($extra | ForEach-Object { $_.Name }) -join ', '))

# --- 2. Manifest: routed set mirrors the frozen nesting; hidden {% content %}-only bodies stay unrouted.
$manifest = Read-Lf (Join-Path $themeDir 'meta\static-pages.yaml')
$routed = @(
    'guides', 'guides-new-captains-guide-heroes-lounge', 'guides-scheduling-and-reporting-matches',
    'guides-signup-guide', 'guides-aram-signup-guide', 'guides-guide-scheduling-and-playing-your-frist-game',
    'guides-uploading-replays', 'privacy-statement', 'general', 'playoff-rules', 'general-test', 'schedule',
    'general-hall-fame', 'division-s-ruleset', 'division-s-ruleset-division-s-playoffs', 'division-s-crew',
    'division-s-standings', 'division-s-schedule', 'aram-league-ruleset', 'division-s-qualifier-standings',
    'ruleset', 'tournament-ruleset'
)
foreach ($name in $routed) {
    Assert-True ([regex]::IsMatch($manifest, "(?m)^\s+$([regex]::Escape($name)):\s*\{\s*\}\s*$|(?m)^\s+$([regex]::Escape($name)):\s*$")) "Manifest must route $name"
}
foreach ($hidden in @('faqpage', 'general-rules', 'general-staff', 'division-s-overview', 'testriggingpage', 'method-mayhem-overview', 'method-mayhem-point-standings', 'heroes-cup-emim')) {
    Assert-True (-not [regex]::IsMatch($manifest, "(?m)^\s+$([regex]::Escape($hidden)):")) "Manifest must NOT route $hidden"
}
Assert-True ([regex]::IsMatch($manifest, '(?ms)^    general:\s*\n        playoff-rules: \{ \}\s*\n        general-test: \{ \}\s*\n        schedule: \{ \}\s*\n        general-hall-fame: \{ \}')) 'general subtree nesting must mirror the frozen manifest'
Assert-True ([regex]::IsMatch($manifest, '(?ms)^    division-s-ruleset:\s*\n        division-s-ruleset-division-s-playoffs: \{ \}')) 'division-s-ruleset nesting must mirror the frozen manifest'

# --- 3. CMS pages (frozen front matter, sanctioned layout mapping, no share-button component).
$faq = Read-Lf (Join-Path $themeDir 'pages\faq.htm')
Assert-Contains $faq 'title = "FAQ"' 'FAQ title contract changed.'
Assert-Contains $faq 'url = "/faq"' 'FAQ url contract changed.'
Assert-Contains $faq 'layout = "static"' 'FAQ must use the static layout.'
Assert-Contains $faq "{% content 'static-pages/faqpage' %}" 'FAQ must render the frozen faqpage body.'
Assert-True (-not [regex]::IsMatch($faq, '(?m)^\[ssbuttonsnb\]')) 'FAQ must not attach the unported ssbuttonsnb component.'

$ruleset = Read-Lf (Join-Path $themeDir 'pages\general\ruleset.htm')
Assert-Contains $ruleset 'title = "Ruleset"' 'Ruleset title contract changed.'
Assert-Contains $ruleset 'url = "/general/ruleset"' 'Ruleset url contract changed.'
Assert-Contains $ruleset 'layout = "static"' 'Ruleset must use the static layout.'
Assert-Contains $ruleset "`n[Bans]`n" 'Ruleset must attach the frozen Bans component.'
Assert-True ($ruleset.IndexOf("{% component 'Bans' %}") -lt $ruleset.IndexOf("{% content 'static-pages/general-rules' %}")) 'Ruleset body order must be Bans then the rules body (frozen order).'

$staff = Read-Lf (Join-Path $themeDir 'pages\general\staff.htm')
Assert-Contains $staff 'title = "Staff"' 'Staff title contract changed.'
Assert-Contains $staff 'url = "/general/staff"' 'Staff url contract changed.'
Assert-Contains $staff 'layout = "static"' 'Staff must use the static layout.'
Assert-Contains $staff '{% put banner %}' 'Staff must supply the layout banner placeholder.'
Assert-Contains $staff '<h1>Staff</h1>' 'Staff page owns its h1.'
Assert-Contains $staff "'assets/img/staff/dreamhack2018.jpg' | theme" 'Staff banner must use the ported theme asset.'
Assert-Contains $staff "{% content 'static-pages/general-staff' %}" 'Staff must render the frozen general-staff body.'
Assert-True (Test-Path -LiteralPath (Join-Path $themeDir 'assets\img\staff\dreamhack2018.jpg')) 'Ported banner asset missing.'
Assert-True (-not [regex]::IsMatch($staff, '(?m)^\[ssbuttonsnb\]')) 'Staff must not attach the unported ssbuttonsnb component.'

# --- 4. Bans override: ALL-LOWERCASE dir, frozen bindings + copy.
$bansPath = Join-Path $themeDir 'partials\bans\default.htm'
$bans = Read-Lf $bansPath
Assert-True (@(Get-ChildItem -LiteralPath (Join-Path $themeDir 'partials') -Directory | Where-Object { $_.Name -ceq 'bans' }).Count -eq 1) 'Bans override dir must be exactly lowercase "bans".'
foreach ($needle in @('__SELF__.heroes', '__SELF__.literals', '__SELF__.talents', 'ban.hero.title', 'ban.talent.title', 'ban.literal', 'ban.round_start', 'ban.round_length',
        'Banned Heroes/Bugs/Talents/Skins', 'This list may not be comprehensive', 'All banned talent/hero/bug breaches must be reported within 7 days')) {
    Assert-Contains $bans $needle "Bans override lost frozen binding/copy: $needle"
}

# --- 5. Section menus + static layout.
foreach ($menu in @('guides', 'general', 'division_s', 'events_archive')) {
    $yaml = Read-Lf (Join-Path $themeDir "meta\menus\$menu.yaml")
    Assert-Contains $yaml "name: $menu" "Menu $menu.yaml must declare its code."
}
$generalMenu = Read-Lf (Join-Path $themeDir 'meta\menus\general.yaml')
Assert-Contains $generalMenu 'url: /general/ruleset' 'General menu must reach the Ruleset CMS page.'
Assert-Contains $generalMenu 'url: /general/staff' 'General menu must reach the Staff CMS page.'
$divisionMenu = Read-Lf (Join-Path $themeDir 'meta\menus\division_s.yaml')
foreach ($ref in @('division-s-crew', 'division-s-standings', 'division-s-qualifier-standings', 'division-s-schedule', 'division-s-ruleset-division-s-playoffs')) {
    Assert-Contains $divisionMenu "reference: $ref" "Division S menu must reference $ref."
}

$layout = Read-Lf (Join-Path $themeDir 'layouts\static.htm')
foreach ($needle in @('[staticMenu sectionGuides]', 'code = "guides"', '[staticMenu sectionGeneral]', 'code = "general"', '[staticMenu sectionDivisionS]', 'code = "division_s"',
        '{% placeholder banner %}', "{% partial 'site/subnav'", "'/general/playoff-rules'", "'/general/staff'", "'/division-s-ruleset/division-s-playoffs'",
        'static-page-{{ this.page.id }}', 'has-layout-title', '{% page %}')) {
    Assert-Contains $layout $needle "Static layout lost: $needle"
}
$subnav = Read-Lf (Join-Path $themeDir 'partials\site\subnav.htm')
Assert-Contains $subnav 'aria-current="page"' 'Subnav must mark the active link.'
Assert-Contains $subnav 'class="subnav-link' 'Subnav link class contract changed.'

# --- 6. lounge.js / pages.css hooks the legacy vocabulary depends on.
$js = Read-Lf (Join-Path $themeDir 'assets\js\lounge.js')
foreach ($needle in @("'.static-content table'", "'table-scroll'", '[data-toggle^="collapse"]', 'data-parent', 'aria-expanded', '[data-toggle="tab"]', 'aria-selected', '.blogPostWrapper', "'tabindex'")) {
    Assert-Contains $js $needle "lounge.js lost the legacy content hook: $needle"
}
$css = Read-Lf (Join-Path $themeDir 'assets\css\pages.css')
foreach ($needle in @('.static-grid.has-subnav', '.subnav-link.on', '.static-content .table-scroll', '.static-content .collapse:not(.show):not(.navbar-collapse)',
        '.static-content [data-toggle^="collapse"][aria-expanded="true"]::after', '.static-content .tab-pane.active', '.static-content .blogPostWrapper:focus-within .hover-fadein',
        '.static-content i.fa-twitch', '.static-banner', '.bans-grid', '.static-page-faq .static-content > h2:first-child')) {
    Assert-Contains $css $needle "pages.css lost the static-content rule: $needle"
}
Assert-True ($css.IndexOf('.static-content [data-toggle^="collapse"]::after { content') -lt $css.IndexOf('.static-content [data-toggle^="collapse"]::after { transition: none; }')) 'Accordion chevron transition must be neutralised in a reduced-motion block AFTER the rule it neutralises.'
Assert-True ($css.IndexOf('.static-content .blogImage { position: relative') -lt $css.IndexOf('.static-content .blogImage, .static-content .hover-fadein { transition: none !important; }')) 'Crew-card motion must be neutralised in a reduced-motion block AFTER the rules it neutralises.'
$components = Read-Lf (Join-Path $themeDir 'assets\css\components.css')
$lastMedia = $components.LastIndexOf('@media (prefers-reduced-motion: reduce)')
Assert-True ($lastMedia -gt 0 -and $components.Substring($lastMedia).TrimEnd().EndsWith('}') -and $components.Substring($lastMedia).IndexOf('@media', 10) -lt 0) 'components.css reduced-motion block must remain LAST.'

# --- 7. No empty/hash hrefs in the theme-authored Phase 4 markup.
foreach ($file in @('layouts\static.htm', 'partials\site\subnav.htm', 'partials\bans\default.htm', 'pages\faq.htm', 'pages\general\ruleset.htm', 'pages\general\staff.htm')) {
    $text = Read-Lf (Join-Path $themeDir $file)
    Assert-True (-not [regex]::IsMatch($text, 'href="(#)?"')) "Empty/hash href in $file"
}

Write-Host 'verify-static-content: all Phase 4 static contracts pass.'
