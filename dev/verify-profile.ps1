$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repoRoot = Split-Path -Parent $PSScriptRoot
$pagePath = Join-Path $repoRoot 'themes\heroeslounge-next\pages\user\view.htm'
$profilePath = Join-Path $repoRoot 'themes\heroeslounge-next\partials\profile\default.htm'
$statsPath = Join-Path $repoRoot 'themes\heroeslounge-next\partials\slothstatistics\default.htm'
$roundMatchesPath = Join-Path $repoRoot 'themes\heroeslounge-next\partials\roundMatches\default.htm'
$cssPath = Join-Path $repoRoot 'themes\heroeslounge-next\assets\css\pages.css'
$accountPagePath = Join-Path $repoRoot 'themes\heroeslounge-next\pages\user\account.htm'

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

function Assert-Regex {
    param(
        [Parameter(Mandatory)]
        [string] $Text,
        [Parameter(Mandatory)]
        [string] $Pattern,
        [Parameter(Mandatory)]
        [string] $Message
    )

    Assert-True ([regex]::IsMatch($Text, $Pattern, [Text.RegularExpressions.RegexOptions]::Singleline)) $Message
}

Assert-True (Test-Path -LiteralPath $pagePath) "Missing themed profile route: $pagePath"
Assert-True (Test-Path -LiteralPath $profilePath) "Missing lowercase Profile override: $profilePath"
Assert-True (Test-Path -LiteralPath $statsPath) "Missing lowercase slothStatistics override: $statsPath"

$page = Get-Content -Raw -LiteralPath $pagePath
$profile = Get-Content -Raw -LiteralPath $profilePath
$stats = Get-Content -Raw -LiteralPath $statsPath
$roundMatches = Get-Content -Raw -LiteralPath $roundMatchesPath
$css = Get-Content -Raw -LiteralPath $cssPath
$accountPage = Get-Content -Raw -LiteralPath $accountPagePath
$statsMarkup = [regex]::Replace(
    $stats,
    '\{#.*?#\}',
    '',
    [Text.RegularExpressions.RegexOptions]::Singleline
)

# Page/component contract and the documented detail-page 404 improvement.
Assert-Contains $page 'title = "Profile"' 'Profile page title contract changed.'
Assert-Contains $page 'url = "/user/view/:id"' 'Profile route contract changed.'
Assert-Contains $page 'layout = "default"' 'Profile must use the new default layout.'
Assert-Contains $page '[Profile]' 'Profile page lost the Profile component.'
Assert-Contains $page 'maxTimelineEntries = 5' 'Profile timeline limit changed.'
Assert-Contains $page "{% component 'Profile' %}" 'Profile page body must render the Profile component.'
Assert-Contains $page "\Rikki\Heroeslounge\Models\Sloth::find(`$this->param('id'))" 'Profile onEnd must resolve the requested sloth.'
Assert-Contains $page "`$this->setStatusCode(404)" 'Unknown profiles must return HTTP 404.'
Assert-Contains $page "'Profile not found'" 'Unknown-profile title changed.'
Assert-Regex $page 'SANCTIONED DEVIATION:.*frozen page returns HTTP 200.*empty' 'The intentional bad-ID behavior change needs its code comment.'

# Profile override: three mutually exclusive states each own one h1.
Assert-True (-not $profile.Contains('profile-theme-override-marker')) 'Temporary profile marker remains.'
Assert-Contains $profile '{% if user %}' 'Profile must retain the full-page authentication gate.'
Assert-Contains $profile '{% if __SELF__.sloth %}' 'Authenticated profile rendering needs a null-sloth branch.'
Assert-True (([regex]::Matches($profile, '<h1\b')).Count -eq 3) 'Valid, not-found, and guest profile states must each own exactly one h1.'
Assert-Contains $profile 'You must be logged in to view this page.' 'Frozen guest-gate copy changed.'
Assert-Contains $profile '<h1>{{ sloth.title }}</h1>' 'Authenticated profile h1 must use sloth.title.'
Assert-Contains $profile '<h1>Profile not found</h1>' 'Authenticated bad-ID state needs its sole h1.'
Assert-Contains $profile '<h1>Sign in to view profiles</h1>' 'Guest state needs its sole h1.'

# Banner/avatar/social fallbacks are presentation-only sanctioned deviations.
Assert-Contains $profile '{% if sloth.banner %} style="background-image:url({{ sloth.banner.path }})"{% endif %}' 'Profile banner attachment binding changed.'
Assert-Contains $profile 'SANCTIONED IMAGE FALLBACK' 'Profile image fallback must be code-commented.'
Assert-Contains $profile "{% partial 'site/initials' name=sloth.title %}" 'Profile avatar fallback must use the shared initials pattern.'
Assert-Contains $profile 'src="{{ sloth.user.avatar.path }}"' 'Profile avatar attachment binding changed.'
Assert-Contains $profile 'onerror="this.onerror=null;this.remove()"' 'Broken profile avatars must reveal the initials fallback without looping.'
Assert-Contains $profile "{% partial 'team/social' obj=sloth light=true %}" 'Profile social links must use the shared lucide row.'
Assert-True (-not [regex]::IsMatch($profile, '\bfa(?:\s|-)')) 'Font Awesome remnants remain in the profile override.'
Assert-True (-not [regex]::IsMatch($profile, '\{\{[^}]*bg_CCC\.png')) 'Frozen bg_CCC banner fallback must not be rendered.'
Assert-True (-not [regex]::IsMatch($profile, '\{\{[^}]*profile-icon\.png')) 'Frozen profile-icon fallback must not be rendered.'

# Info/data bindings. MMR deliberately stays outside a null guard.
Assert-Contains $profile "'assets/img/roles/' ~ sloth.role.title|replace({' ':'-'})|lower ~ '.svg'" 'Profile role SVG binding changed.'
Assert-Contains $profile "{{ 'assets/img/battlenet.svg'|theme }}" 'Profile Battle.net brand asset is missing.'
Assert-Contains $profile "{{ 'assets/img/discord.svg'|theme }}" 'Profile Discord brand asset is missing.'
Assert-Contains $profile 'https://www.heroesprofile.com/Player/{{ sloth.getHeroesProfileBattletagReformatted }}/{{ sloth.heroesprofile_id }}/{{ sloth.getHeroesProfileRegionId }}' 'HeroesProfile deep-link shape changed.'
Assert-Contains $profile '{{ sloth.battle_tag }}' 'Profile lost battle_tag.'
Assert-Contains $profile '{{ sloth.discord_tag }}' 'Profile lost discord_tag.'
Assert-Regex $profile '<dt>MMR</dt>\s*<dd>\{\{\s*sloth\.heroesprofile_mmr\s*\}\}</dd>' 'MMR must render faithfully even when null.'
Assert-Contains $profile '{{ sloth.user.country.name }}' 'Profile lost country.'
Assert-Contains $profile '{{ sloth.birthday }}' 'Profile lost birthday.'
Assert-Contains $profile '{{ sloth.short_description|striptags }}' 'Profile description must stay stripped.'

# Teams and nested component integrations.
Assert-Contains $profile '{% for team in sloth.teams %}' 'Profile team collection loop changed.'
Assert-Contains $profile "{% partial 'team/shield' team=team size='sm' %}" 'Profile teams must use the shared shield.'
Assert-Contains $profile 'href="/team/view/{{ team.slug }}"' 'Profile team links must retain literal frozen URLs.'
Assert-True (([regex]::Matches($profile, [regex]::Escape("{% component 'roundMatches' %}"))).Count -eq 1) 'Profile must render roundMatches exactly once.'
Assert-True (([regex]::Matches($profile, [regex]::Escape("{% component 'slothStatistics' %}"))).Count -eq 1) 'Profile must render slothStatistics exactly once.'
Assert-Contains $profile "{% component 'timeLine' id=__SELF__.sloth.id %}" 'Profile timeline component contract changed.'
Assert-Regex $profile 'SPOILER INTENT:.*revealScore=true' 'Player profile revealed-score intent must be code-commented.'
Assert-True (-not [regex]::IsMatch($profile, 'removeClass|SocialSharingButtons|\{%\s*put scripts')) 'Frozen unmask/share script remnants remain in the profile override.'
Assert-True (-not [regex]::IsMatch($profile, 'jumbotron|nav-tabs|tab-pane|card-body|table-striped')) 'Bootstrap presentation remnants remain in the profile override.'

# The shared roundMatches override must distinguish player context without
# changing the existing team branch.
Assert-Contains $roundMatches "set isSloth = __SELF__.property('type') == 'sloth'" 'roundMatches must detect its sloth component context.'
Assert-Contains $roundMatches 'revealScore=isSloth' 'Sloth results must render spoiler-revealed.'
Assert-Contains $roundMatches "isSloth ? 'player competes' : 'team plays'" 'roundMatches empty copy must distinguish player and team contexts.'

# Lowercase slothStatistics override: static themed tables, no DataTables app.
Assert-True (-not $stats.Contains('slothstatistics-theme-override-marker')) 'Temporary slothstatistics marker remains.'
Assert-Contains $stats 'id="slothstatistics"' 'Sloth statistics container id changed.'
Assert-Contains $stats '{% set selSeason = __SELF__.selectedSeason %}' 'Selected sloth season binding changed.'
Assert-Contains $stats 'No statistics yet' 'Sloth statistics needs a fixture-blind empty state.'
Assert-Contains $stats 'No hero data for this season.' 'Sloth hero table needs its own empty row.'
Assert-Contains $stats 'No map data for this season.' 'Sloth map table needs its own empty row.'
Assert-Contains $stats '{% for h in __SELF__.heroes %}' 'Sloth hero statistics loop changed.'
Assert-Contains $stats '{% for m in __SELF__.maps %}' 'Sloth map statistics loop changed.'
foreach ($field in @('picks', 'winrate', 'bans_by_team', 'bans_against_team', 'kills', 'assists', 'deaths', 'siege_dmg', 'hero_dmg', 'healing', 'dmg_taken', 'xp')) {
    Assert-Contains $stats ("h['" + $field + "']") "Sloth hero table lost $field."
}
Assert-True (-not [regex]::IsMatch($statsMarkup, 'DataTable|data-request|onSeasonChange|collapse|data-toggle|nav-tabs|table-bordered|table-responsive')) 'DataTables/AJAX/Bootstrap collapse remnants remain in slothstatistics markup.'
Assert-True (([regex]::Matches($stats, '<h1\b')).Count -eq 0) 'slothstatistics must not introduce another h1.'

# Profile-only CSS extends shared team/banner/table primitives and stays before
# the final reduced-motion guard.
foreach ($selector in @(
    '.profile-avatar',
    '.profile-stack',
    '.profile-info',
    '.profile-facts',
    '.profile-fact',
    '.profile-team-row',
    '.profile-section-title'
)) {
    Assert-Contains $css $selector "Missing profile styling selector $selector."
}
Assert-Regex $css '@media\s*\(max-width:\s*640px\).*?\.profile-facts' 'Profile facts lack narrow-screen collapse.'
$profileCssIndex = $css.IndexOf('/* ---------- player profile')
$reducedMotionIndex = $css.LastIndexOf('@media (prefers-reduced-motion: reduce)')
Assert-True ($profileCssIndex -ge 0 -and $profileCssIndex -lt $reducedMotionIndex) 'Profile CSS must remain before the final reduced-motion block.'

Assert-Regex $accountPage 'forceSecure\s*=\s*1' 'Account page forceSecure must be restored to 1.'

Write-Output 'Profile static contracts pass.'
