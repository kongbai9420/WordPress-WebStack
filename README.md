# WebStack-2026.8.5
WordPress 版 WebStack-2026.8.5 导航主题。<a href="https://dh.kongbaige.net/">前往演示站</a>
本主题基于<a href="https://github.com/owen0o0/WebStack" target="_blank">owen0o0/WebStack</a>修改
使用AI优化UI风格，偏向于苹果风（不知道算不算）

本主题<a href="https://t.me/WebStack_kong" target="_blank">TG频道</a>
本主题<a href="https://t.me/+zIx2kbaAaWxkNzU9" target="_blank">TG群组</a>

### 更新日志
- **WebStack-2026.8.5** 修复远程站点实时在线人数不更新问题，兼容访客统计开关返回类型，并更新静态资源版本号以避免缓存旧脚本
- **WebStack-2026.8.4** 新增页脚访客统计、实时在线人数、访问量防刷新限流，优化 Apple 风格 UI、公告栏显示和首页分类跳转定位
- **v1.2026.1** 安全加固：修复 CVE-2026-1555 任意文件上传漏洞，添加 Nonce CSRF 防护，修复页面持续加载问题  

### 首页截图
<br/>

![首页白.png](https://www.helloimg.com/i/2026/07/28/6a67a1e461c0a.png)

![首页黑.png](https://www.helloimg.com/i/2026/07/28/6a67a1e449af2.png)
<br/>


### 后台截图
<br/>

![Thumbnail_index](https://picui.ogmua.cn/s1/2026/08/06/6a74a8a69161e.webp)

<br/>

#### 推荐环境

- WordPress：6.0 及以上，建议使用最新稳定版本
- PHP：8.1 或 8.2
- 数据库：MySQL 5.7/8.0 或 MariaDB 10.4 及以上
- Web 服务器：Nginx 或 Apache

<br/>

### 使用本主题的站点

| 站点名称 | 网站地址 | 网站简介 | 备注 |
| :--- | :--- | :--- | :--- |
| 我的导航站 | [https://dh.kongbaige.net](https://dh.kongbaige.net) | 二次元网站导航 | 个人站点 |



### 以下为owen0o0/WebStack原声明及其教程
当你使用 WebStack 主题发布文章、文字、图片、视频等内容均属于你自己的行为，你的这些行为所带来的安全或法律风险均需自行承担。

### 安装指南
+ 安装 WordPress ，教程百度
+ 设置伪静态（下方规则按自己服务器环境二选一）
```
# Nginx规则
location /
{
    try_files $uri $uri/ /index.php?$args;
}
rewrite /wp-admin$ $scheme://$host$uri/ permanent;

# Apache 规则
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```
+ WordPress 后台「主题」栏目 -> 上传主题 -> 启用主题，或者在 /wp-content/themes 文件夹新建webstack文件夹，并上传所有文件
+ 果然点击地址出现404，请到WordPress 后台「设置」栏目 -> 固定链接 -> 保存更改

<br/>

### 主题使用
+ 在 WordPress 后台“网址”文章类型下添加内容
+ 分类最多两级，且父级不要添加内容
+ 可以不添加网址图片，主题会自动获取目标网址的 favicon 图标
+ 导航菜单栏标题前面的图标请在分类图像描述中填入（参考下图），图标样式请参考fontawesome
![Thumbnail_index](https://owen0o0.github.io/ioStaticResources/webstack/02.png)
+ 增加分类快速添加图标的方法
![Thumbnail_index](https://owen0o0.github.io/ioStaticResources/webstack/07.png)
+ 导航菜单栏下方可以添加自定义菜单，在后台的外观-->菜单里设置，在菜单的css类添加图标（参考下图），图标样式请参考fontawesome
![Thumbnail_index](https://owen0o0.github.io/ioStaticResources/webstack/03.png)
+ 如果菜单里没有css类，请按下图添加
![Thumbnail_index](https://owen0o0.github.io/ioStaticResources/webstack/04.jpg)
+ <a href="https://www.iotheme.cn/store/onenav.html" target="_blank">如果你有更多功能需求，点我-></a>
<br/>

### 感谢
感谢 <a href="https://github.com/WebStackPage/WebStackPage.github.io" target="_blank">Viggo</a> 的前台设计
感谢 <a href="https://github.com/owen0o0/WebStack" target="_blank">一为忆</a> 的设计
<br/>

### 更新
<a href="https://github.com/kongbai9420/WordPress-WebStack/releases" target="_blank">更新日志</a>
更新方法为替换源文件，或者在wordpress后台删除主题，然后重新安装主题
