<?php
/**
 * @author    : Death-Satan
 * @date      : 2021/8/27
 * @createTime: 22:45
 * @company   : Death撒旦
 * @link      https://www.cnblogs.com/death-satan
 */
namespace SaTan\Think\Commands;

use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\MountManager;
use SaTan\Think\BaseService as Service;
use think\console\Command;
use think\console\input\Option;
use think\Filesystem;

class VendorPublish extends Command
{
    protected Filesystem $file;
    /**
     * 要发布的服务提供者
     *
     * @var string|null
     */
    protected ?string $provider = null;

    /**
     * 要发布的标记。
     *
     * @var array
     */
    protected array $tags = [];

    public function __construct (Filesystem $file)
    {
        parent::__construct();
        $this->file = $file;
    }

    public function configure()
    {
        $this->setName('vendor:publish')
            ->addOption('tags','tags',Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY,'tags name')
            ->addOption('force', 'f', Option::VALUE_NONE, 'Overwrite any existing files')
            ->setDescription('Publish any publishable assets from vendor packages');
    }

    /**
     * 原生的处理
     */
    public function handle()
    {
        $tags = $this->input->getOption('tags')?:[null];
        $force = $this->input->getOption('force');
        foreach ($tags as $tag)
        {
            $this->publishTag($tag);
        }
        $this->output->writeln('<info>Publishing complete!</info>');
//        $this->
        //不影响原程序的逻辑
        if (is_file($path = $this->app->getRootPath() . 'vendor/composer/installed.json')) {
            $packages = json_decode(@file_get_contents($path), true);
            // Compatibility with Composer 2.0
            if (isset($packages['packages'])) {
                $packages = $packages['packages'];
            }
            foreach ($packages as $package) {
                //配置
                $configDir = $this->app->getConfigPath();

                if (!empty($package['extra']['think']['config'])) {

                    $installPath = $this->app->getRootPath() . 'vendor/' . $package['name'] . DIRECTORY_SEPARATOR;

                    foreach ((array) $package['extra']['think']['config'] as $name => $file) {

                        $target = $configDir . $name . '.php';
                        $source = $installPath . $file;

                        if (is_file($target) && !$force) {
                            $this->output->info("File {$target} exist!");
                            continue;
                        }

                        if (!is_file($source)) {
                            $this->output->info("File {$source} not exist!");
                            continue;
                        }

                        copy($source, $target);
                    }
                }
            }

            $this->output->writeln('<info>Succeed!</info>');
        }

    }

    /**
     * 获取要发布的所有路径。
     *
     * @param  string  $tag
     * @return array
     */
    protected function pathsToPublish($tag)
    {
        return Service::pathsToPublish(
            $this->provider, $tag
        );
    }
    /**
     * 发布tags资源
     * @param $tag string|null
     */
    protected function publishTag(?string $tag)
    {
        $published = false;

        $pathsToPublish = $this->pathsToPublish($tag);
        foreach ($pathsToPublish as $from => $to) {
            $this->publishItem($from, $to);

            $published = true;
        }

        if ($published === false) {
            $this->error('Unable to locate publishable resources.');
        } else {
            //触发事件
            $this->getApp()->event->trigger('VendorToPublish');
        }
    }

    /**
     * 迁移目录
     * @param $form
     * @param $to
     */
    protected function publishDirectory($from,$to)
    {
        $this->moveManagedFiles(new MountManager([
            'from' => new Flysystem(new LocalAdapter($from)),
            'to' => new Flysystem(new LocalAdapter($to)),
        ]));

        $this->status($from, $to, 'Directory');
    }

    /**
     * 移动给定MountManager中的所有文件。
     *
     * @param MountManager $manager
     *
     * @return void
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function moveManagedFiles($manager)
    {
        foreach ($manager->listContents('from://', true) as $file) {
            if ($file['type'] === 'file' && (! $manager->has('to://'.$file['path']) || $this->input->getOption('force'))) {
                $manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
            }
        }
    }

    /**
     * 迁移文件
     *
     * @param $from
     * @param $to
     */
    protected function publishFile($from,$to)
    {
        if (! is_file($to) || $this->input->getOption('force')) {
            $this->createParentDirectory(dirname($to));
            @copy($from, $to);
            $this->status($from, $to, 'File');
        }
    }

    /**
     * 创建目录
     * @param $dir
     */
    protected function createParentDirectory($dir)
    {
        if (!is_dir($dir))
        {
            mkdir($dir,0755,true);
        }
    }
    /**
     * 将状态消息写入控制台。
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->output->writeln('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }

    /**
     * 将给定项目从和发布到给定位置。
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishItem($from, $to)
    {
        if (is_file($from)) {
            return $this->publishFile($from, $to);
        } elseif (is_dir($from)) {
            return $this->publishDirectory($from, $to);
        }

        $this->error("Can't locate path: <{$from}>");
    }

    /**
     * 错误消息
     * @param string|null $message
     */
    protected function error(?string $message)
    {
        $this->output->error($message);
    }
}