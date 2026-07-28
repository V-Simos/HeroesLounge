# October CMS v1.1 (Laravel 6, PHP 7.4) with October preinstalled.
# aspendigital's images are frozen (project archived) - the develop tag is the only
# October v1.1 build; pin it by digest so it can never drift.
FROM aspendigital/octobercms@sha256:070c94a0ecad4bf7b01a3ec35ed1c7cdee04eb67ca0429a12541227a42389b96

# Open-source plugins the theme / rikki plugins depend on, installed from GitHub
# (no marketplace Project ID needed). Tags pinned to the last October-v1-compatible
# release lines (RainLab kept 1.x maintained for October v1).
RUN set -eux; \
    git clone --depth 1 --branch v1.7.2  https://github.com/rainlab/user-plugin            plugins/rainlab/user; \
    git clone --depth 1 --branch v1.6.3  https://github.com/rainlab/blog-plugin            plugins/rainlab/blog; \
    git clone --depth 1 --branch v1.5.12 https://github.com/rainlab/pages-plugin           plugins/rainlab/pages; \
    git clone --depth 1 --branch v1.12.0 https://github.com/rainlab/translate-plugin       plugins/rainlab/translate; \
    # Provides the googleTracker component declared by all theme layouts.
    git clone --depth 1 --branch v1.3.2  https://github.com/rainlab/googleanalytics-plugin plugins/rainlab/googleanalytics; \
    # Provides the form_select_country()/form_select_state() Twig functions the theme uses.
    git clone --depth 1 --branch v1.2.5  https://github.com/rainlab/location-plugin        plugins/rainlab/location; \
    git clone --depth 1 --branch v1.7.9  https://github.com/offline-gmbh/oc-site-search-plugin plugins/offline/sitesearch; \
    # No version tags exist for these two; pin by commit for reproducibility.
    git clone https://github.com/anand-patel/oc-wysiwyg-editors plugins/anandpatel/wysiwygeditors; \
    git -C plugins/anandpatel/wysiwygeditors checkout bad3d66e78df7a9619d2e9c2e21f0c6b4b3c91dd; \
    git clone https://github.com/jawainc/simplecontact plugins/zainab/simplecontact; \
    git -C plugins/zainab/simplecontact checkout a4bbc2107d6d796504f673c3e046972e3fc7192f; \
    # Provides the `can()` / `hasRole()` Twig functions the theme's navigation uses.
    git clone https://github.com/Shahiem/frontend-roles-manager plugins/shahiemseymor/roles; \
    git -C plugins/shahiemseymor/roles checkout 3c01b921bf0d69cc249198d5cac6ca6f89a32edf; \
    # Provides the `| resize` Twig filter used all over the theme; unlike the
    # October v1.1 core filter it renders a placeholder for missing images
    # (fixture teams have no logos).
    git clone --depth 1 --branch v1.4.0 https://github.com/toughdeveloper/oc-imageresizer-plugin plugins/toughdeveloper/imageresizer; \
    # The image ships Composer 1 which current Packagist no longer serves.
    composer self-update --2 --quiet; \
    composer --working-dir=plugins/toughdeveloper/imageresizer config --no-plugins allow-plugins.composer/installers false; \
    composer --working-dir=plugins/toughdeveloper/imageresizer --no-interaction --quiet install; \
    rm -rf plugins/*/*/.git; \
    # This October build compares version.yaml keys against the DB version with
    # a loose == ; "v"-prefixed keys ("v1.7.2") numeric-compare equal to 0 and
    # the plugin is silently skipped by october:up. Strip the prefix.
    sed -i -E 's/^v([0-9])/\1/' plugins/*/*/updates/version.yaml; \
    chown -R www-data:www-data plugins
