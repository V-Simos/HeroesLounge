param(
    [string]$RepositoryRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

function Require-File {
    param([string]$Path, [string]$Description)

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "Missing ${Description}: $Path"
    }

    return Get-Content -Raw -LiteralPath $Path
}

function Require-Text {
    param([string]$Content, [string]$Needle, [string]$Description)

    if (-not $Content.Contains($Needle)) {
        throw "Missing ${Description}: $Needle"
    }
}

$theme = Join-Path $RepositoryRoot 'themes/heroeslounge-next'
$account = Require-File (Join-Path $theme 'pages/user/account.htm') 'account page override'
$default = Require-File (Join-Path $theme 'partials/slothaccount/default.htm') 'SlothAccount default override'
$signin = Require-File (Join-Path $theme 'partials/slothaccount/signin.htm') 'SlothAccount sign-in override'
$register = Require-File (Join-Path $theme 'partials/slothaccount/register.htm') 'SlothAccount registration override'
$activation = Require-File (Join-Path $theme 'partials/slothaccount/activation_check.htm') 'SlothAccount activation override'
$ruleset = Require-File (Join-Path $theme 'partials/site/ruleset.htm') 'ruleset partial'
$countryState = Require-File (Join-Path $theme 'partials/country-state/default.htm') 'interim SlothAccount country-state dependency'
$css = Require-File (Join-Path $theme 'assets/css/pages.css') 'page stylesheet'

@(
    'title = "Account"',
    'url = "/user/:code?"',
    'layout = "default"',
    '[SlothAccount]',
    'redirect = "user/account"',
    'paramCode = "code"',
    'forceSecure = 1',
    "{% component 'SlothAccount' %}"
) | ForEach-Object { Require-Text $account $_ 'frozen account-page contract' }

@(
    '{% if not user %}',
    "{% partial __SELF__ ~ '::signin' %}",
    "{% partial __SELF__ ~ '::register' %}",
    "{% partial __SELF__ ~ '::activation_check' %}",
    "{% partial __SELF__ ~ '::update' %}"
) | ForEach-Object { Require-Text $default $_ 'SlothAccount branch contract' }

@(
    'data-request="onSignin"',
    'data-request-flash',
    'name="login"',
    'name="login" type="text"',
    'autocomplete="username"',
    'name="password"',
    "{{ 'user/forgotpassword'|page }}"
) | ForEach-Object { Require-Text $signin $_ 'sign-in request contract' }

@(
    'data-request="onRegister"',
    'data-request-flash',
    'name="username"',
    'name="email"',
    'name="email_confirmation"',
    'name="battle_tag"',
    'name="discord_tag"',
    'name="region_id"',
    'name="password"',
    'name="password_confirmation"',
    'name="newsletter_subscription"',
    '<dialog',
    "{% partial 'site/ruleset' %}",
    'type="button"',
    'type="submit"'
) | ForEach-Object { Require-Text $register $_ 'registration request contract' }

Require-Text $activation 'data-request="onSendActivationEmail"' 'activation-email request contract'
Require-Text $ruleset 'We are the largest Heroes of the Storm amateur league.' 'ruleset welcome copy'
Require-Text $countryState "form_select_country('country_id'" 'frozen update country selector contract'

@('dialog.showModal()', 'dialog.close()') |
    ForEach-Object { Require-Text $account $_ 'native dialog script contract' }

@('data-rules-dialog-open', 'data-rules-dialog-close', 'data-rules-dialog-submit') |
    ForEach-Object { Require-Text $register $_ 'native dialog control contract' }

if ([regex]::Matches($default, '<h1\b', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase).Count -ne 1) {
    throw 'The SlothAccount default override must own exactly one rendered-state h1.'
}

if ([regex]::Matches($default, 'class="p auth-card"').Count -ne 2) {
    throw 'The guest account branch must contain exactly two themed panels.'
}

@('.auth-guest-grid', '.auth-dialog', '@media (max-width: 760px)') |
    ForEach-Object { Require-Text $css $_ 'guest account stylesheet contract' }

$reducedMotion = $css.LastIndexOf('@media (prefers-reduced-motion: reduce)')
$authGuest = $css.IndexOf('.auth-guest-grid')
if ($authGuest -gt $reducedMotion) {
    throw 'Guest account stylesheet must precede the final reduced-motion block.'
}

Write-Output 'Account guest static contracts pass.'
