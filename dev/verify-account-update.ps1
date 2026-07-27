$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repoRoot = Split-Path -Parent $PSScriptRoot
$updatePath = Join-Path $repoRoot 'themes\heroeslounge-next\partials\slothaccount\update.htm'
$viewAppsPath = Join-Path $repoRoot 'themes\heroeslounge-next\partials\viewapps\default.htm'
$countryPath = Join-Path $repoRoot 'themes\heroeslounge-next\partials\user\country-select.htm'
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

function Get-TwigForBody {
    param(
        [Parameter(Mandatory)]
        [string] $Text,
        [Parameter(Mandatory)]
        [string] $OpeningTag,
        [Parameter(Mandatory)]
        [string] $Description
    )

    $start = $Text.IndexOf($OpeningTag, [StringComparison]::Ordinal)
    Assert-True ($start -ge 0) "Could not find the $Description loop."

    $bodyStart = $start + $OpeningTag.Length
    $depth = 1
    $cursor = $bodyStart
    $tokenPattern = [regex]'\{%\s*(for\b|endfor\b)[^%]*%\}'

    while ($depth -gt 0) {
        $token = $tokenPattern.Match($Text, $cursor)
        Assert-True $token.Success "Could not find the closing tag for the $Description loop."
        if ($token.Groups[1].Value.StartsWith('for', [StringComparison]::Ordinal)) {
            $depth++
        }
        else {
            $depth--
        }
        if ($depth -eq 0) {
            return $Text.Substring($bodyStart, $token.Index - $bodyStart)
        }
        $cursor = $token.Index + $token.Length
    }
}

Assert-True (Test-Path -LiteralPath $updatePath) "Missing themed SlothAccount update override: $updatePath"
Assert-True (Test-Path -LiteralPath $countryPath) "Missing theme-wide country selector: $countryPath"

$update = Get-Content -Raw -LiteralPath $updatePath
$country = Get-Content -Raw -LiteralPath $countryPath
$accountPage = Get-Content -Raw -LiteralPath $accountPagePath

# lounge.js tabs shell. These assertions catch a regression back to the frozen
# Bootstrap surface or a panel that the existing delegated tab handler cannot find.
Assert-Contains $update 'data-tabs' 'Account update must use the lounge.js data-tabs contract.'
Assert-Contains $update 'class="account-tabs tabs"' 'Account update must use the shared tabs visual primitive.'
foreach ($tab in @('general', 'media', 'social', 'game', 'apps')) {
    Assert-Regex $update ('<button[^>]+data-tab-target="account-' + $tab + '"[^>]*>') "Missing account $tab tab button."
    Assert-Regex $update ('id="account-' + $tab + '"[^>]+role="tabpanel"') "Missing account $tab tab panel."
}
Assert-Regex $update '<section class="p account-panel" id="account-general" role="tabpanel"[^>]*>' 'General must be the visible default panel.'
foreach ($hiddenTab in @('media', 'social', 'game', 'apps', 'notifications')) {
    Assert-Regex $update ('<section class="p account-panel" id="account-' + $hiddenTab + '" role="tabpanel"[^>]* hidden>') "Account $hiddenTab panel must start hidden."
}
Assert-Contains $update '{% if this.session.get(''notifications'') %}' 'Notifications tab must keep the frozen session gate.'
Assert-Contains $update '{{ this.session.get(''notifications'') | length }}' 'Notifications tab count must keep the frozen expression.'
Assert-Contains $update 'Applications [{{ __SELF__.appsCount }}]' 'Applications tab must render the component appsCount.'
Assert-True (-not [regex]::IsMatch($update, 'nav-tabs|tab-pane|table-striped')) 'Bootstrap tab/table remnants remain in themed account update.'
Assert-True (-not [regex]::IsMatch($update, '\bfa(?:\s|-)')) 'Font Awesome remnants remain in themed account update.'
Assert-True (([regex]::Matches($update, '<h1\b')).Count -eq 0) 'Task 2 default partial must remain the sole account h1 owner.'

# General: the handler/model and field names are server contracts.
Assert-Regex $update '\{\{\s*form_open\(\{request:\s*''onUpdateGeneral'',\s*model:\s*user\s*\}\)\s*\}\}' 'General form request/model contract changed.'
Assert-Contains $update 'name="username"' 'General form lost username.'
Assert-Contains $update 'value="{{ form_value(''username'') }}"' 'Username must use form_value.'
Assert-Contains $update 'name="password"' 'General form lost password.'
Assert-Contains $update 'name="password_confirmation"' 'General form lost password_confirmation.'
Assert-Contains $update 'request: ''onUnsubscribeNewsletter''' 'Newsletter unsubscribe handler changed.'
Assert-Contains $update 'request: ''onSubscribeNewsletter''' 'Newsletter subscribe handler changed.'
Assert-Contains $update '{% if user.sloth.newsletter_subscription %}' 'Newsletter subscription branch changed.'

# Social: preserve the frozen request idioms and handler-readable field names.
Assert-Contains $update 'request: ''onUpdateDescription''' 'Description handler changed.'
Assert-Regex $update '<textarea[^>]+name="short_description"[^>]+maxlength="255"[^>]*>\{\{\s*__SELF__\.sloth\.short_description\s*\|\s*striptags\s*\}\}</textarea>' 'Description field contract changed.'
Assert-Regex $update '<form[^>]*class="account-links-form"[^>]*>' 'Links must remain a plain form.'
$linksRequest = 'data-request="{{ __SELF__ }}::onUpdateLinks"'
Assert-True (([regex]::Matches($update, [regex]::Escape($linksRequest))).Count -eq 1) 'Links request must occur exactly once.'
Assert-Regex $update ('<button(?=[^>]*type="submit")(?=[^>]*' + [regex]::Escape($linksRequest) + ')[^>]*>') 'Links request must remain only on its submit button.'
Assert-True (-not [regex]::IsMatch($update, '<form[^>]*class="account-links-form"[^>]*data-request=')) 'Links form itself must not carry the AJAX request.'
foreach ($field in @('facebook_url', 'twitter_url', 'twitch_url', 'youtube_url', 'website_url', 'discord_tag', 'battle_tag', 'region_id')) {
    Assert-Contains $update ('name="' + $field + '"') "Links form lost $field."
}
Assert-Contains $update '{% if __SELF__.sloth.discord_id %}' 'Discord linked/unlinked branch changed.'
Assert-Contains $update 'data-request="{{ __SELF__ }}::onSyncDiscord"' 'Discord sync handler changed.'
Assert-Contains $update "{% partial 'site/icon' name='radio' %}" 'Discord sync button must use a supported lucide site icon.'
Assert-True (-not $update.Contains("name='refresh-cw'")) 'Unsupported refresh-cw icon name would render an unknown-icon comment.'
Assert-Regex $update '<input[^>]+name="discord_tag"[^>]+disabled' 'Linked Discord tag must remain disabled.'
Assert-Regex $update '<input[^>]+name="battle_tag"[^>]+disabled[^>]+title="Battle Tag - talk to a moderator on Discord if this has changed"' 'BattleTag disabled/tooltip contract changed.'
Assert-Contains $update "{% partial 'user/country-select' countryId=user.country_id %}" 'Links form must use the planned country selector.'
Assert-Contains $update '<option value="1"' 'EU region option value changed.'
Assert-Contains $update '<option value="2"' 'NA region option value changed.'
foreach ($icon in @('facebook', 'twitter', 'twitch', 'youtube', 'globe')) {
    Assert-Contains $update ("{% partial 'site/icon' name='" + $icon + "' %}") "Missing themed $icon icon."
}
Assert-Contains $update "{{ 'assets/img/discord.svg'|theme }}" 'Discord brand asset is not ported.'
Assert-Contains $update "{{ 'assets/img/battlenet.svg'|theme }}" 'Battle.net brand asset is not ported.'

# Country selector: preserve RainLab.Location's markup-helper contract.
Assert-Contains $country "{% set countryId = countryId|default(form_value('country_id')) %}" 'Country selector defaulting contract changed.'
Assert-Contains $country "{{ form_select_country('country_id', countryId, {" 'RainLab.Location country helper contract changed.'
Assert-Contains $country "'data-request': 'onInit'" 'Country selector onInit contract changed.'

# Game: preserve hidden tab value, role iteration, NA-only server choices.
Assert-Contains $update "request: 'onUpdateGame'" 'Game handler changed.'
Assert-Contains $update 'name="tab" value="game"' 'Game hidden tab contract changed.'
Assert-Contains $update 'name="role_id"' 'Game role field changed.'
Assert-Contains $update '{% for role in __SELF__.roles %}' 'Game role iteration changed.'
Assert-Contains $update '{% if __SELF__.sloth.region_id == 2 %}' 'NA server-preference gate changed.'
Assert-Contains $update 'name="server_preference"' 'Server preference field changed.'
Assert-Contains $update '<option value="NA West"' 'NA West option value changed.'
Assert-Contains $update '<option value="NA Central"' 'NA Central option value changed.'

# Media: selectFile.js depends on this exact wrapper/input/text-input relationship.
foreach ($media in @(
    @{ Name = 'avatar'; Handler = 'onUpdateAvatar'; ErrorId = 'avatarUploadError' },
    @{ Name = 'banner'; Handler = 'onUpdateBanner'; ErrorId = 'bannerUploadError' }
)) {
    $openForm = "{{ form_open({request: '$($media.Handler)', model: user, files: true }) }}"
    $mediaFormMatch = [regex]::Match(
        $update,
        [regex]::Escape($openForm) + '(.*?)' + [regex]::Escape('{{ form_close() }}'),
        [Text.RegularExpressions.RegexOptions]::Singleline
    )
    Assert-True $mediaFormMatch.Success "Media $($media.Name) form contract changed."
    $mediaForm = $mediaFormMatch.Groups[1].Value
    $fileSelectMatch = [regex]::Match(
        $mediaForm,
        '<div class="fileselect">(.*?)</div>',
        [Text.RegularExpressions.RegexOptions]::Singleline
    )
    Assert-True $fileSelectMatch.Success "Missing $($media.Name) fileselect wrapper."
    $fileSelect = $fileSelectMatch.Groups[1].Value
    Assert-True (([regex]::Matches($fileSelect, 'type="file"')).Count -eq 1) "$($media.Name) fileselect must contain exactly one file input."
    Assert-True (([regex]::Matches($fileSelect, 'type="text"')).Count -eq 1) "$($media.Name) fileselect must contain exactly one text sibling."
    Assert-Regex $fileSelect ('<input[^>]+type="file"[^>]+accept="image/png"[^>]+name="' + $media.Name + '"[^>]+style="display:none"[^>]*>.*?<input[^>]+type="text"[^>]+readonly') "selectFile.js $($media.Name) sibling contract changed."
    Assert-Contains $fileSelect ('aria-label="Selected ' + $media.Name + ' file"') "Readonly $($media.Name) filename lacks an accessible name."
    Assert-Contains $fileSelect ('id="' + $media.ErrorId + '"') "Missing $($media.Name) upload error target."
    Assert-Regex $fileSelect ('id="' + $media.ErrorId + '"[^>]+role="alert"') "$($media.Name) upload errors must be announced."
    Assert-True (-not $mediaForm.Contains('account-media-preview')) "$($media.Name) preview must remain outside its upload form."
}
Assert-Contains $update 'Uploading a new avatar will replace your current avatar.<br />' 'Avatar helper copy changed.'
Assert-Contains $update 'Image must be a PNG, under 100MB, and under 1280x1280 pixels.' 'Avatar limits copy changed.'
Assert-Contains $update 'The Avatar will be used to recognize you even if no username is shown.<br />' 'Avatar preview copy changed.'
Assert-Contains $update 'We suggest to take a square image e.g. 128x128 Pixel.' 'Avatar dimensions copy changed.'
Assert-Contains $update 'Uploading a new banner will replace your current banner.<br />' 'Banner helper copy changed.'
Assert-Contains $update 'Image must be a PNG and under 100MB in size.' 'Banner limits copy changed.'
Assert-Contains $update 'The Banner will be used to spice up your Profile Page.<br />' 'Banner preview copy changed.'
Assert-Contains $update 'We suggest to take a rectangular image e.g. 1000x250 Pixel.' 'Banner dimensions copy changed.'
Assert-Contains $update 'src="{{ __SELF__.sloth.user.avatar.path }}"' 'Avatar preview binding changed.'
Assert-Contains $update 'src="{{ __SELF__.sloth.banner.path }}"' 'Banner preview binding changed.'
Assert-True (([regex]::Matches($update, 'onerror="this\.onerror=null;this\.src=''\{\{ accountMediaPlaceholder \}\}''"')).Count -eq 2) 'Both media previews must use the neutral fallback without an error loop.'

# Applications and notifications keep their component/session data flows.
Assert-Contains $update 'href="/application"' 'Deferred application create link must be literal.'
Assert-True (-not $update.Contains("'application/create' | page")) 'Deferred application create page filter would emit an empty href.'
Assert-Contains $update "{% component 'viewApps' %}" 'Applications panel lost the viewApps runtime component.'
Assert-Contains $update "{% for notification in this.session.get('notifications') %}" 'Notifications loop changed.'
Assert-Contains $update '{{ notification.type|capitalize }}' 'Notification type binding changed.'
Assert-Contains $update '{{ notification.message }}' 'Notification message binding changed.'
Assert-True (-not [regex]::IsMatch($update, '\bwow\b|\bshake\b')) 'Frozen animation classes remain in notifications.'

# viewApps: retain both collection/data/handler contracts while replacing the
# frozen tables with responsive themed rows. Deferred detail pages stay literal.
Assert-True (Test-Path -LiteralPath $viewAppsPath) "Missing lowercase viewapps override: $viewAppsPath"
$viewApps = Get-Content -Raw -LiteralPath $viewAppsPath
Assert-True (-not $viewApps.Contains('viewapps-theme-override-marker')) 'Temporary viewapps marker remains.'
Assert-Contains $viewApps '{% for app in __SELF__.slothApps %}' 'Own-application collection loop changed.'
Assert-Contains $viewApps '{% set applicationTeam = __SELF__.slothAppsTeams[app.team_id]|default(null) %}' 'Own-application team lookup needs an unavailable-team guard.'
Assert-Contains $viewApps '{% if applicationTeam %}{{ applicationTeam.title }}{% else %}Team unavailable{% endif %}' 'Own-application team fallback changed.'
Assert-Contains $viewApps '{% if app.approved == 0 %}No{% else %}Yes{% endif %}' 'Own-application approval binding changed.'
Assert-Contains $viewApps '{% for app in __SELF__.teamApps %}' 'Team-application collection loop changed.'
Assert-Contains $viewApps '{% set applicationPlayer = __SELF__.teamAppsUsers[app.user_id]|default(null) %}' 'Team-application user lookup needs an unavailable-player guard.'
Assert-Contains $viewApps '{% if applicationPlayer %}{{ applicationPlayer.title }}{% else %}Player unavailable{% endif %}' 'Team-application player fallback changed.'
Assert-Contains $viewApps '{% if app.approved == 0 %}Unaccepted{% else %}Invite pending{% endif %}' 'Team-application status binding changed.'
Assert-True (([regex]::Matches($viewApps, 'href="/application/view/\{\{ app\.id \}\}"')).Count -eq 2) 'Both deferred application detail links must be literal.'
Assert-True (-not $viewApps.Contains("'application/view' | page")) 'Deferred application detail page filters remain.'

$ownAppsLoop = Get-TwigForBody $viewApps '{% for app in __SELF__.slothApps %}' 'own-applications'
$teamAppsLoop = Get-TwigForBody $viewApps '{% for app in __SELF__.teamApps %}' 'team-applications'
$idPayloadPattern = 'data-request-data="id: \{\{ app\.id \}\}"'

Assert-True (([regex]::Matches($viewApps, $idPayloadPattern)).Count -eq 4) 'ViewApps must emit four handler id payload wrappers.'
Assert-True (([regex]::Matches($viewApps, 'data-request="onAccept"')).Count -eq 1) 'ViewApps must emit one onAccept handler.'
Assert-True (([regex]::Matches($viewApps, 'data-request="onSendAccept"')).Count -eq 1) 'ViewApps must emit one onSendAccept handler.'
Assert-True (([regex]::Matches($viewApps, 'data-request="onWithdraw"')).Count -eq 2) 'ViewApps must emit two onWithdraw handlers.'

Assert-True (([regex]::Matches($ownAppsLoop, $idPayloadPattern)).Count -eq 2) 'Own applications must carry two id payload wrappers.'
Assert-True (([regex]::Matches($ownAppsLoop, 'data-request="onAccept"')).Count -eq 1) 'Own applications must carry one onAccept handler.'
Assert-True (([regex]::Matches($ownAppsLoop, 'data-request="onWithdraw"')).Count -eq 1) 'Own applications must carry one onWithdraw handler.'
Assert-True (([regex]::Matches($ownAppsLoop, 'data-request="onSendAccept"')).Count -eq 0) 'Own applications must not carry onSendAccept.'
Assert-Regex $ownAppsLoop '\{% if app\.approved == 1 %\}(?:(?!\{% endif %\})[\s\S])*?<div data-request-data="id: \{\{ app\.id \}\}">\s*<button data-request="onAccept"(?:(?!\{% endif %\})[\s\S])*?\{% endif %\}' 'onAccept must stay id-bound inside the approved-invite branch.'
Assert-Regex $ownAppsLoop '<div data-request-data="id: \{\{ app\.id \}\}">\s*<button data-request="onWithdraw"' 'Own-application onWithdraw must stay id-bound.'

Assert-True (([regex]::Matches($teamAppsLoop, $idPayloadPattern)).Count -eq 2) 'Team applications must carry two id payload wrappers.'
Assert-True (([regex]::Matches($teamAppsLoop, 'data-request="onSendAccept"')).Count -eq 1) 'Team applications must carry one onSendAccept handler.'
Assert-True (([regex]::Matches($teamAppsLoop, 'data-request="onWithdraw"')).Count -eq 1) 'Team applications must carry one onWithdraw handler.'
Assert-True (([regex]::Matches($teamAppsLoop, 'data-request="onAccept"')).Count -eq 0) 'Team applications must not carry onAccept.'
Assert-Regex $teamAppsLoop '\{% if app\.approved == 0 %\}(?:(?!\{% endif %\})[\s\S])*?<div data-request-data="id: \{\{ app\.id \}\}">\s*<button data-request="onSendAccept"(?:(?!\{% endif %\})[\s\S])*?\{% else %\}' 'onSendAccept must stay id-bound inside the unaccepted branch.'
Assert-Regex $teamAppsLoop '<div data-request-data="id: \{\{ app\.id \}\}">\s*<button data-request="onWithdraw"' 'Team-application onWithdraw must stay id-bound.'

Assert-True (-not [regex]::IsMatch($viewApps, 'table-striped|btn-primary|btn-warning|btn-danger')) 'Bootstrap presentation remnants remain in viewapps.'
Assert-True (([regex]::Matches($viewApps, '<h1\b')).Count -eq 0) 'viewapps must not introduce a second h1.'

# Page-level styling: the hidden rule is behavioral (without it, lounge.js
# updates state but panels remain visible); the grids/queries protect layout.
$css = Get-Content -Raw -LiteralPath $cssPath
foreach ($selector in @(
    '.account-tabs',
    '.account-panel[hidden]',
    '.account-panel-body',
    '.account-form-grid',
    '.account-links-grid',
    '.account-media-row',
    '.fileselect',
    '.account-app-row'
)) {
    Assert-Contains $css $selector "Missing account styling selector $selector."
}
Assert-Regex $css '\.account-panel\[hidden\]\s*\{\s*display:\s*none' 'Hidden account panels must not display.'
Assert-Regex $css '@media\s*\(max-width:\s*900px\).*?\.account-media-row' 'Account media row lacks tablet collapse.'
Assert-Regex $css '@media\s*\(max-width:\s*640px\).*?\.account-tabs' 'Account tabs lack narrow-screen overflow handling.'
$accountCssIndex = $css.IndexOf('/* ---------- authenticated account update')
$reducedMotionIndex = $css.LastIndexOf('@media (prefers-reduced-motion: reduce)')
Assert-True ($accountCssIndex -ge 0 -and $accountCssIndex -lt $reducedMotionIndex) 'Account CSS must remain before the final reduced-motion block.'

Assert-Regex $accountPage 'forceSecure\s*=\s*1' 'Account page forceSecure must be restored to 1.'

Write-Output 'Account update static contracts pass.'
