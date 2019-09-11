<?php

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

class DecoratorManager extends DataProvider
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $result = [];

    /**
     * @var CacheItemInterface
     */
    private $cacheItem;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        parent::__construct($host, $user, $password);
        $this->cache = $cache;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $input
     *
     * @return array|mixed
     *
     * @throws InvalidArgumentException
     */
    public function getResponse(array $input)
    {
        try {
            $this->getResult($input);
            $this->saveResult();
        } catch (Exception $exception) {
            $this->logger->critical('Error: ' . $exception->getMessage());
        }

        return $this->result;
    }

    /**
     * @param array $input
     *
     * @return false|string
     */
    private function getCacheKey(array $input)
    {
        return json_encode($input);
    }

    /**
     * @param array $input
     *
     * @throws InvalidArgumentException
     */
    private function getResult(array $input)
    {
        $this->setCacheItem($this->getCacheItem($input));

        if ($this->cacheItem->isHit()) {
            $this->setResult($this->cacheItem->get());
        }

        $this->setResult($this->get($input));
    }

    /**
     * @param array $input
     *
     * @return CacheItemInterface
     *
     * @throws InvalidArgumentException
     */
    private function getCacheItem(array $input)
    {
        $cacheKey = $this->getCacheKey($input);

        return $this->cache->getItem($cacheKey);
    }

    /**
     * @param array $result
     */
    private function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @param CacheItemInterface $cacheItem
     */
    private function setCacheItem($cacheItem)
    {
        $this->cacheItem = $cacheItem;
    }

    /**
     * @throws Exception
     */
    private function saveResult()
    {
        $this->cacheItem
            ->set($this->result)
            ->expiresAt(
                (new DateTime())->modify('+1 day')
            );
        $this->cache->save($this->cacheItem);
    }
}
