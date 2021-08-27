<?php
/**
 * @author    : Death-Satan
 * @date      : 2021/8/27
 * @createTime: 22:42
 * @company   : Death撒旦
 * @link      https://www.cnblogs.com/death-satan
 */


namespace SaTan\Think;

/**
 * 优化服务提供者
 * Class BaseService
 * @package SaTan\Think
 */
class BaseService extends \think\Service
{

    public static array $publishes = [];
    public static array $publishTags =[];

    /**
     * 合并配置
     * @param string $path
     * @param string $key
     */
    protected function mergeConfigFrom(string $path,string $key)
    {
        $config = $this->app->config;
        if (is_file($path)){
            $config->set(require $path,$key);
        }
    }

    /**
     * 发布资源目录
     * @param array      $path
     * @param string|null $tags
     */
    protected function publishes(array $path,?string $tags=null)
    {
        $this->ensurePublishArrayInitialized($class = static::class);

        static::$publishes[$class] = array_merge(static::$publishes[$class], $path);

        foreach ((array) $tags as $group) {
            $this->addPublishTags($group, $path);
        }
    }

    /**
     * 向服务提供者添加发布tags标记。
     *
     * @param string $tags
     * @param array  $paths
     *
     * @return void
     */
    protected function addPublishTags(string $tags, array $paths)
    {
        if (! array_key_exists($tags, static::$publishTags)) {
            static::$publishTags[$tags] = [];
        }

        static::$publishTags[$tags] = array_merge(
            static::$publishTags[$tags], $paths
        );
    }

    /**
     * 获取提供程序或组（或两者）的路径。
     *
     * @param string|null $provider
     * @param string|null $tags
     *
     * @return array
     */
    protected static function pathsForProviderOrGroup(?string $provider, ?string $tags): ?array
    {
        if ($provider && $tags) {
            return static::pathsForProviderAndTags($provider, $tags);
        } elseif ($tags && array_key_exists($tags, static::$publishTags)) {
            return static::$publishTags[$tags];
        } elseif ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        } elseif ($tags || $provider) {
            return [];
        }else{
            return collect(static::$publishes)->reduce(function ($p,$paths){
                return $paths;
            });
        }
    }
    /**
     * 获取要发布的路径
     *
     * @param  string|null  $provider
     * @param  string|null  $tags
     * @return array
     */
    public static function pathsToPublish($provider = null, $tags = null): ?array
    {
        if (! is_null($paths = static::pathsForProviderOrGroup($provider, $tags))) {
            return $paths;
        }

        return collect(static::$publishes)->reduce(function ($paths, $p) {
            return array_merge($paths, $p);
        }, []);
    }

    /**
     * 获取提供程序和tags的路径。
     *
     * @param string $provider
     * @param string $tags
     *
     * @return array
     */
    protected static function pathsForProviderAndTags(string $provider, string $tags): array
    {
        if (! empty(static::$publishes[$provider]) && ! empty(static::$publishTags[$tags])) {
            return array_intersect_key(static::$publishes[$provider], static::$publishTags[$tags]);
        }

        return [];
    }

    /**
     * 确保是初始化的服务发布者数组
     *
     * @param string $class
     *
     * @return void
     */
    protected function ensurePublishArrayInitialized(string $class)
    {
        if (! array_key_exists($class, static::$publishes)) {
            static::$publishes[$class] = [];
        }
    }
}