uninstall-drupal-modules
========================

Uninstall Drupal modules before composer remove their files.

The main purpose is to avoid `drush config:import` errors after switching branches on a dev environment

⚠️ At the moment, it's just a POC, it uses the `drush` binary (and does not support the `--uri=URI` option).

# Install
`composer require --dev obriat/uninstall-drupal-modules main-dev`