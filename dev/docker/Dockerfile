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
    git clone --depth 1 --branch v1.3.2  https://github.com/rainlab/googleanalytics-plugin plugins/rainlab/googleanalytics; \
    git clone --depth 1 --branch v1.7.9  https://github.com/offline-gmbh/oc-site-search-plugin plugins/offline/sitesearch; \
    # No version tags exist for these two; pin by commit for reproducibility.
    git clone https://github.com/anand-patel/oc-wysiwyg-editors plugins/anandpatel/wysiwygeditors; \
    git -C plugins/anandpatel/wysiwygeditors checkout bad3d66e78df7a9619d2e9c2e21f0c6b4b3c91dd; \
    git clone https://github.com/jawainc/simplecontact plugins/zainab/simplecontact; \
    git -C plugins/zainab/simplecontact checkout a4bbc2107d6d796504f673c3e046972e3fc7192f; \
    rm -rf plugins/*/*/.git; \
    chown -R www-data:www-data plugins
