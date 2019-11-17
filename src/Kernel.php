<?php

declare(strict_types=1);

namespace App;

use App\Tenant\MetadataCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

final class Kernel extends SymfonyKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @return iterable<int, BundleInterface>
     */
    public function registerBundles(): iterable
    {
        $path = $this->getProjectDir().'/config/bundles.php';
        \assert(\file_exists($path));

        /** @noinspection PhpIncludeInspection */
        $contents = require $path;
        foreach ((array) $contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->getEnvironment()])) {
                if (!\class_exists($class)) {
                    throw new \LogicException(\sprintf('Class %s not exists', $class));
                }

                \assert(\is_subclass_of($class, BundleInterface::class));

                yield new $class();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/logs';
    }

    public function getConfDir(): string
    {
        return $this->getProjectDir().'/config';
    }

    /**
     * {@inheritdoc}
     */
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MetadataCompilerPass(), PassConfig::TYPE_OPTIMIZE);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getConfDir();

        if (\is_dir($confDir.'/routes/'.$this->environment)) {
            $routes->import($confDir.'/routes/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        $routes->import($confDir.'/routes'.self::CONFIG_EXTS, '/', 'glob');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('container.dumper.inline_class_loader', true);
        $container->setParameter('container.autowiring.strict_mode', true);

        $confDir = $this->getConfDir();

        $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');

        if (\is_dir($confDir.'/packages/'.$this->getEnvironment())) {
            $loader->load($confDir.'/packages/'.$this->getEnvironment().'/**/*'.self::CONFIG_EXTS, 'glob');
        }

        $loader->load($confDir.'/services'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/services_'.$this->getEnvironment().self::CONFIG_EXTS, 'glob');
    }
}
