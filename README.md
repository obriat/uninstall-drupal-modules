uninstall-drupal-modules
========================

Uninstall modules and themes from Drupal before composer remove their files.

The main purpose is to avoid `drush config:import` errors after switching branches on a dev environment or
before a deployment.

It uses the `drush` binary (and does not support the `--uri=URI` option).

# Install

## Development environments
`composer require --dev obriat/uninstall-drupal-modules`

## Deployment scripts
`composer require obriat/uninstall-drupal-modules`