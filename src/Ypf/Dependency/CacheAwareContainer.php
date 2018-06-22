<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Class CacheAwareContainer
 *
 * A cache-aware container that should speed up dependency
 * resolution by storing everything resolved inside the
 * provided cache. This is a production optimization and
 * it's use while developing is discouraged.
 *
 * @package Onion\Framework\Dependency
 */
class CacheAwareContainer implements ContainerInterface
{
    /**
     * A container which holds the dependencies
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Factory for lazy initializing the container
     *
     * @var FactoryInterface
     */
    private $containerFactory;

    /**
     * The cache backend in which to store the dependencies
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * A list of keys that are excluded from the caching and
     * will always be retrieved from the resolved container.
     * This is to allow the construction of dependencies that
     * might change on some factors external to the application.
     *
     * @var array
     */
    private $blacklist;

    /**
     * CacheAwareContainer constructor.
     * This is a composition-based extension to the regular container,
     * it receives a factory class that should prevent initialization
     * of the container on every run(which will remove the benefits of
     * the cache) by initializing it only when the dependency is not
     * present in the cache.
     *
     * @param FactoryInterface $factory A factory to build the real container
     * @param CacheInterface $cache Cache in which to store resolved deps
     * @param array $blacklist List of keys to not include in the cache
     */
    public function __construct(FactoryInterface $factory, CacheInterface $cache, array $blacklist = [])
    {
        $this->containerFactory = $factory;
        $this->cache = $cache;
        $this->blacklist = $blacklist;
    }

    /**
     * Instantiate the container if not and return it
     *
     * @return ContainerInterface
     */
    private function resolveContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = $this->containerFactory->build($this);
        }

        return $this->container;
    }

    /**
     * @inheritdoc
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Onion\Framework\Dependency\Exception\ContainerErrorException
     */
    public function get($key)
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $dependency = $this->resolveContainer()->get($key);
        if (!in_array($key, $this->blacklist, true)) {
            $this->cache->set($key, $dependency);
        }

        return $dependency;
    }

    /**
     * @inheritdoc
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function has($key): bool
    {
        return $this->cache->has($key) || $this->resolveContainer()->has($key);
    }
}
