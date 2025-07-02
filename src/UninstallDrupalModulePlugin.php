<?php

namespace OBriat;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * A Composer plugin to display a message after creating a project.
 *
 * @internal
 */
class UninstallDrupalModulePlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Composer object.
     *
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     * @var ProcessExecutor $executor
     */
    protected $executor;
    /**
     * IO object.
     *
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * Configuration.
     *
     * @var \Drupal\Composer\Plugin\VendorHardening\Config
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->executor = new ProcessExecutor($this->io);
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
        PackageEvents::PRE_PACKAGE_UNINSTALL => 'executeDrushPmu',
        ];
    }

    public function executeDrushPmu(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        $type =  $package->getType();
        // drupal-theme ?
        if ($type === "drupal-module") {
            $this->io->write('  - Uninstalling all Drupal modules in <info>' . $package->getName() . '</info> before removing it:');
            $manager = $event->getComposer()->getInstallationManager();
            $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);
            $info_files = iterator_to_array(
                Finder::create()
                ->files()
                ->name('*.info.yml')
                ->in($install_path)
                ->exclude('tests')
                ->sortByName()
                ->reverseSorting()
            );
            foreach ($info_files as $key => $info_file) {
                $sep = ($key === array_key_last($info_files)) ? '└' : '├';
                $module_name = $info_file->getBasename('.info.yml');
                $checked = $this->executeCommand('drush pm:uninstall %s -y', $module_name);
                $output = trim($this->executor->getErrorOutput());
                if (substr($output, 0, 10) === '[success] ') {
                    $this->io->write('    ' . $sep . '─ drush pmu ' . $module_name . ': <info>' . substr($output, 10) . '</info>');
                } else {
                    $this->io->writeError('    ' . $sep . '─ drush pmu ' . $module_name . ': <comment>' . preg_replace('/^\n*.*:\n*\s*/im', '', $output) . '</comment>');
                }
            }
        }
    }

    /**
     * Executes a shell command with escaping.
     *
     * @param  string $cmd
     * @return bool
     */
    protected function executeCommand($cmd)
    {
        // Shell-escape all arguments except the command.
        $args = func_get_args();
        foreach ($args as $index => $arg) {
            if ($index !== 0) {
                $args[$index] = escapeshellarg($arg);
            }
        }

        // And replace the arguments.
        $command = call_user_func_array('sprintf', $args);
        $output = '';
        if ($this->io->isVerbose()) {
            $this->io->write('<comment>' . $command . '</comment>');
            $io = $this->io;
            $output = function ($type, $data) use ($io) {
                if ($type == Process::ERR) {
                    $io->write('<error>' . $data . '</error>');
                } else {
                    $io->write('<comment>' . $data . '</comment>');
                }
            };
        }
        return ($this->executor->execute($command, $output) == 0);
    }
}
