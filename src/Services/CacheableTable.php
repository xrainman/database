<?php

namespace PavolEichler\Database;

/**
 *
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class CacheableTable extends ReadOnlyTable
{


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * 
     *                              Cache
     * 
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Cache tag to use in automatic cache invalidation.
     */
    const CACHE_AUTO_INVALIDATE = '__auto_invalidate';

    /** 
     * Cache service.
     * 
     * @var \Nette\Caching\Cache
     */
    protected $cache = null;

    /**
     * If true, all queries will be cached by default.
     * @var boolean
     */
    protected $useCache = false;

    /**
     * Time to expire cached values. Set to false to disable expiration.
     * @var string
     */
    protected $cacheExpire = '+ 30 days';

    /**
     * An array of cache tags to apply on all cache items. Turn automatic ivalidation on by default.
     * @var array
     */
    protected $cacheTags = array(self::CACHE_AUTO_INVALIDATE);

    /**
     *
     * @param \DibiConnection $dibi
     * @param \Nette\Caching\Cache $cache
     * @param array $table
     */
    public function __construct(\DibiConnection $dibi, \Nette\Caching\Cache $cache = null, $table = null){
        parent::__construct($dibi, $table);

        $this->cache = $cache;
        
    }

    /**
     * Fetches all rows.
     *
     * @param \DibiFluent $fluent
     * @return \DibiResult The returned rows.
     * @throws \Exception
     */
    protected function fetchAll(\DibiFluent $fluent) {

        if ($this->useCache){

            // use cache

            // verify the cache object is available
            if (!$this->cache)
                throw new \Exception('Cache object is not available.');

            // calculate the cache item unique ID
            $cacheId = $this->cacheId($fluent);

            // use the existing value, if it exists
            if($this->cache->load($cacheId) !== null)
                return $this->cache->load($cacheId);

            // fetch the data and cache results
            $self = $this;

            // using a callback allows Nette cache to optimize performance for concurrent requests
            $result = $this->cache->save($cacheId, function() use ($self, $fluent) {

                // fetch the data from the database

                // TODO call parent::fetchAll()
                // PHP 5.3 does not allow accessing protected methods for via $this keyword in an anonymous function
                // we will overcome this by calling the protected method through its reflection
                // for PHP 5.4, this could be replaced by simply callling 'return $this->fetch($fluent);'

                // get the reflection
                $method = new \Nette\Reflection\Method($self, 'fetch');
                // set the method as accessible
                $method->setAccessible(true);

                // call the method
                $result = $method->invokeArgs($self, array($fluent));

                // return data as a DibiRowCollection
                return DibiRowCollection::from($result);

            }, array(
                \Nette\Caching\Cache::EXPIRE => $this->cacheExpire,
                \Nette\Caching\Cache::TAGS => $this->getCacheTags(),
                \Nette\Caching\Cache::SLIDING => true
            ));

            return $result;

        }else{

            // do not use cache
            
            return parent::fetchAll($fluent);

        }

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Cache
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Get the cache object.
     *
     * @return \Nette\Caching\Cache Cache object or null.
     */
    public function getCache() {

        return $this->cache;

    }

    /**
     * Provide a new cache object.
     *
     * @param \Nette\Caching\Cache $cache
     */
    public function setCache(\Nette\Caching\Cache $cache) {

        $this->cache = $cache;

    }
    
    /**
     * Use cache by default for all queries.
     */
    public function enableCache() {

        $this->useCache = true;

    }

    /**
     * Do not cache queries by default.
     */
    public function disableCache() {

        $this->useCache = false;

    }

    /**
     * Turn the cache on for the next query. Provides fluent interface.
     *
     * @param string|array $tags Tag name or an array of tag names to use.
     * @return \Models\SingleTable Returns a cloned object with caching turned on.
     */
    public function cache($tags = null) {

        // create a cloned object with the cache turned on
        $clone = clone $this;
        $clone->enableCache();

        // configure cache tags, if available
        if ($tags !== null)
            $clone->setCacheTags ($tags);

        return $clone;

    }

    /**
     * Turn the cache off for the next query. Provides fluent interface.
     *
     * @return \Models\SingleTable Returns a cloned object with caching turned off.
     */
    public function nocache() {

        // create a cloned object with the cache turned on
        $clone = clone $this;
        $clone->disableCache();

        return $clone;

    }

    /**
     * Set a custom set of cache tags. This enables to take control of the class cache invalidation and bypass the automatic invalidation on update or insert.
     *
     * @param string|array $tags Tag name or an array of tag names.
     */
    public function setCacheTags($tags) {

        $this->cacheTags = is_array($tags) ? $tags : array($tags);

    }

    /**
     * Get the current set of cache tags.
     *
     * @return array An array of tag names.
     */
    public function getCacheTags() {

        return $this->cacheTags;

    }

    /**
     * Set the expiration time for cached values. Provides fluent interface.
     *
     * @param mixed $time Accepts same formats as Nette Cache Expire flag (seconds as integer, strings in format '+ X days'...).
     * @param boolean $persist If set to false, method will return a cloned object with the new cache expiration setting. This allows to change the setting for one method call only. Defaults to true.
     * @return \Models\SingleTable Returns this object. If $persist is set to false, this will be a new cloned object.
     */
    public function setCacheExpiration($time, $persist = true) {

        if ($persist){
            // set cache expiration
            $this->cacheExpire = $time;
            // fluent interface
            return $this;
        }else{
            // create a cloned object with the cache turned on
            $clone = clone $this;
            $clone->setCacheExpiration($time);
            // fluent interface
            return $clone;
        }

    }

    /**
     * Removes cached values.
     *
     * @param string|array $tags If provided, removes cached values with the given tags. Otherwise only CACHE_AUTO_INVALIDATE items will be removed.
     */
    public function invalidateCache($tags = null) {

        // cache not available
        if (!$this->cache)
            return;

        // remove auto invalidate items only or remove items with specific tags, if provided
        $this->cache->clean(array(
            \Nette\Caching\Cache::TAGS => ($tags === null) ? array(self::CACHE_AUTO_INVALIDATE) : $tags
        ));

    }

    /**
     * Create a unique ID string for the given fluent query.
     *
     * @param \DibiFluent $fluent
     * @return string Unique ID hash for this query and database connection.
     */
    protected function cacheId(\DibiFluent $fluent) {

        // get the complete query from the Dibi's test() call

        // use the output buffer to capture the output
        ob_start();

        // output the query
        $fluent->test();

        // get the buffer contents
        $query = ob_get_contents();
        // clean the captured output from the buffer
        ob_end_clean();

        // get the fluent's database connection settings
        $config = $fluent->getConnection()->getConfig();

        // serialize the config and query together
        $unique = serialize(array($config, $query));

        // hash them to produce a unique ID string
        $id = md5($unique);

        return $id;

    }

}