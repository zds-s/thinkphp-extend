# thinkphp6 扩展包
## 扩展命令
### 重写 `php think vendor:publish`命令
#### 像laravel一样便携的迁移文件 需要继承`SaTan\Think\BaseService`类
#### 事件 `SaTan\Think\Event\VendorPublish` 资源发布事件 事件别名 `VendorPublish`