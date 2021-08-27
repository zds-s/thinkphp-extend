<?php
/**
 * @author    : Death-Satan
 * @date      : 2021/8/27
 * @createTime: 22:43
 * @company   : Death撒旦
 * @link      https://www.cnblogs.com/death-satan
 */
namespace SaTan\Think;

use SaTan\Think\Commands\VendorPublish;

class ThinkService extends BaseService
{
    /**
     * 命令类
     * @var array|string[]
     */
    protected array $commands = [
        VendorPublish::class
    ];
    /**
     * 事件类
     * @var array|string[]
     */
    protected array $event = [
        'VendorPublish'=>\SaTan\Think\Event\VendorPublish::class
    ];

    public function register (): void
    {
        //注册命令
        $this->registerCommands();
        //注册事件
        $this->registerEvent();
    }

    /**
     * 绑定事件
     */
    protected function registerEvent()
    {
        $this->app->event->bind($this->event);
    }
    /**
     * 注册命令
     */
    protected function registerCommands()
    {
        $this->commands($this->commands);
    }
}