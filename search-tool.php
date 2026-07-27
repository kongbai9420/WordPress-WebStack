

<div id="search" class="s-search">
    <div id="search-list" class="hide-type-list">
        <div class="s-type">
            <span></span>
            <div class="s-type-list animated fadeInUp">
                <label for="type-baidu"><?php _e('常用','i_theme') ?></label>
                <label for="type-search"><?php _e('搜索','i_theme') ?></label>
                <label for="type-br"><?php _e('工具','i_theme') ?></label>
                <label for="type-zhihu"><?php _e('社区','i_theme') ?></label>
                <label for="type-taobao1"><?php _e('生活','i_theme') ?></label>
                <label for="type-zhaopin"><?php _e('求职','i_theme') ?></label>
            </div>
        </div>
        <div class="search-group group-a">
            <span class="type-text"><?php _e('常用','i_theme') ?></span>
            <ul class="search-type">
                <li><input checked hidden type="radio" name="type" id="type-baidu" value="https://www.baidu.com/s?wd=" data-placeholder="<?php _e('百度一下','i_theme') ?>"><label for="type-baidu"><span style="color:#2100E0"><?php _e('百度','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-google" value="https://www.google.com/search?q=" data-placeholder="<?php _e('谷歌两下','i_theme') ?>"><label for="type-google"><span style="color:#3B83FA">G</span><span style="color:#F3442C">o</span><span style="color:#FFC300">o</span><span style="color:#4696F8">g</span><span style="color:#2CAB4E">l</span><span style="color:#F54231">e</span></label></li>
                <li><input hidden type="radio" name="type" id="type-zhannei" value="<?php bloginfo('url') ?>?s=" data-placeholder="<?php _e('站内搜索','i_theme') ?>"><label for="type-zhannei"><span style="color:#888888"><?php _e('站内','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-taobao" value="https://s.taobao.com/search?q=" data-placeholder="<?php _e('淘宝','i_theme') ?>"><label for="type-taobao"><span style="color:#f40"><?php _e('淘宝','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-bing1" value="https://cn.bing.com/search?q=" data-placeholder="<?php _e('微软Bing搜索','i_theme') ?>"><label for="type-bing1"><span style="color:#007daa">Bing</span></label></li>
            </ul>
        </div>
        <div class="search-group group-b">
            <span class="type-text"><?php _e('搜索','i_theme') ?></span>
            <ul class="search-type">
                <li><input hidden type="radio" name="type" id="type-search" value="https://www.baidu.com/s?wd=" data-placeholder="<?php _e('百度一下','i_theme') ?>"><label for="type-search"><span style="color:#2319dc"><?php _e('百度','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-google1" value="https://www.google.com/search?q=" data-placeholder="<?php _e('谷歌两下','i_theme') ?>"><label for="type-google1"><span style="color:#3B83FA">G</span><span style="color:#F3442C">o</span><span style="color:#FFC300">o</span><span style="color:#4696F8">g</span><span style="color:#2CAB4E">l</span><span style="color:#F54231">e</span></label></li>
                <li><input hidden type="radio" name="type" id="type-360" value="https://www.so.com/s?q=" data-placeholder="<?php _e('360好搜','i_theme') ?>"><label for="type-360"><span style="color:#19b955">360</span></label></li>
                <li><input hidden type="radio" name="type" id="type-sogo" value="https://www.sogou.com/web?query=" data-placeholder="<?php _e('搜狗搜索','i_theme') ?>"><label for="type-sogo"><span style="color:#ff5943"><?php _e('搜狗','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-bing" value="https://cn.bing.com/search?q=" data-placeholder="<?php _e('微软Bing搜索','i_theme') ?>"><label for="type-bing"><span style="color:#007daa">Bing</span></label></li>
                <li><input hidden type="radio" name="type" id="type-sm" value="https://yz.m.sm.cn/s?q=" data-placeholder="<?php _e('UC移动端搜索','i_theme') ?>"><label for="type-sm"><span style="color:#ff8608"><?php _e('神马','i_theme') ?></span></label></li>
            </ul>
        </div>
        <div class="search-group group-c">
            <span class="type-text"><?php _e('工具','i_theme') ?></span>
            <ul class="search-type">
                <li><input hidden type="radio" name="type" id="type-br" value="https://rank.chinaz.com/all/" data-placeholder="<?php _e('请输入网址(不带http://)','i_theme') ?>"><label for="type-br"><span style="color:#55a300"><?php _e('权重查询','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-links" value="https://link.chinaz.com/" data-placeholder="<?php _e('请输入网址(不带http://)','i_theme') ?>"><label for="type-links"><span style="color:#313439"><?php _e('友链检测','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-icp" value="https://icp.aizhan.com/" data-placeholder="<?php _e('请输入网址(不带http://)','i_theme') ?>"><label for="type-icp"><span style="color:#ffac00"><?php _e('备案查询','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-ping" value="https://ping.chinaz.com/" data-placeholder="<?php _e('请输入网址(不带http://)','i_theme') ?>"><label for="type-ping"><span style="color:#00599e"><?php _e('PING检测','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-404" value="https://tool.chinaz.com/Links/?DAddress=" data-placeholder="<?php _e('请输入网址(不带http://)','i_theme') ?>"><label for="type-404"><span style="color:#f00"><?php _e('死链检测','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-ciku" value="https://www.ciku5.com/s?wd=" data-placeholder="<?php _e('请输入关键词','i_theme') ?>"><label for="type-ciku"><span style="color:#016DBD"><?php _e('关键词挖掘','i_theme') ?></span></label></li>
            </ul>
        </div>
        <div class="search-group group-d">
            <span class="type-text"><?php _e('社区','i_theme') ?></span>
            <ul class="search-type">
                <li><input hidden type="radio" name="type" id="type-zhihu" value="https://www.zhihu.com/search?type=content&q=" data-placeholder="<?php _e('知乎','i_theme') ?>"><label for="type-zhihu"><span style="color:#0084ff"><?php _e('知乎','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-wechat" value="https://weixin.sogou.com/weixin?type=2&query=" data-placeholder="<?php _e('微信','i_theme') ?>"><label for="type-wechat"><span style="color:#00a06a"><?php _e('微信','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-weibo" value="https://s.weibo.com/weibo/" data-placeholder="<?php _e('微博','i_theme') ?>"><label for="type-weibo"><span style="color:#e6162d"><?php _e('微博','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-douban" value="https://www.douban.com/search?q=" data-placeholder="<?php _e('豆瓣','i_theme') ?>"><label for="type-douban"><span style="color:#55a300"><?php _e('豆瓣','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-why" value="https://ask.seowhy.com/search/?q=" data-placeholder="<?php _e('SEO问答社区','i_theme') ?>"><label for="type-why"><span style="color:#428bca"><?php _e('搜外问答','i_theme') ?></span></label></li>
            </ul>
            </div>
        <div class="search-group group-e">
            <span class="type-text"><?php _e('生活','i_theme') ?></span>
            <ul class="search-type">
                <li><input hidden type="radio" name="type" id="type-taobao1" value="https://s.taobao.com/search?q=" data-placeholder="<?php _e('淘宝','i_theme') ?>"><label for="type-taobao1"><span style="color:#f40"><?php _e('淘宝','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-jd" value="https://search.jd.com/Search?keyword=" data-placeholder="<?php _e('京东','i_theme') ?>"><label for="type-jd"><span style="color:#c91623"><?php _e('京东','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-xiachufang" value="https://www.xiachufang.com/search/?keyword=" data-placeholder="<?php _e('下厨房','i_theme') ?>"><label for="type-xiachufang"><span style="color:#dd3915"><?php _e('下厨房','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-xiangha" value="https://www.xiangha.com/so/?q=caipu&s=" data-placeholder="<?php _e('香哈菜谱','i_theme') ?>"><label for="type-xiangha"><span style="color:#930"><?php _e('香哈菜谱','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-12306" value="https://www.12306.cn/?" data-placeholder="12306"><label for="type-12306"><span style="color:#07f">12306</span></label></li>
                <li><input hidden type="radio" name="type" id="type-qunar" value="https://www.qunar.com/?" data-placeholder="<?php _e('去哪儿','i_theme') ?>"><label for="type-qunar"><span style="color:#00afc7"><?php _e('去哪儿','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-快递100" value="https://www.kuaidi100.com/?" data-placeholder="<?php _e('快递100','i_theme') ?>"><label for="type-快递100"><span style="color:#3278e6"><?php _e('快递100','i_theme') ?></span></label></li>
            </ul>
        </div>
        <div class="search-group group-f">
            <span class="type-text"><?php _e('求职','i_theme') ?></span>
            <ul class="search-type">
                <li><input hidden type="radio" name="type" id="type-zhaopin" value="https://sou.zhaopin.com/jobs/searchresult.ashx?kw=" data-placeholder="<?php _e('智联招聘','i_theme') ?>"><label for="type-zhaopin"><span style="color:#689fee"><?php _e('智联招聘','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-51job" value="https://search.51job.com/?" data-placeholder="<?php _e('前程无忧','i_theme') ?>"><label for="type-51job"><span style="color:#ff6000"><?php _e('前程无忧','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-lagou" value="https://www.lagou.com/jobs/list_" data-placeholder="<?php _e('拉钩网','i_theme') ?>"><label for="type-lagou"><span style="color:#00b38a"><?php _e('拉钩网','i_theme') ?></span></label></li>
                <li><input hidden type="radio" name="type" id="type-liepin" value="https://www.liepin.com/zhaopin/?key=" data-placeholder="<?php _e('猎聘网','i_theme') ?>"><label for="type-liepin"><span style="color:#303a40"><?php _e('猎聘网','i_theme') ?></span></label></li>
            </ul>
        </div>
    </div>
    <form action="?s=" method="get" target="_blank" id="super-search-fm"><input type="text" id="search-text" placeholder="输入关键字搜索" style="outline:0"><button type="submit"><i class="fa fa-search "></i></button></form>
    <div class="set-check hidden-xs">
        <input type="checkbox" id="set-search-blank" class="bubble-3" autocomplete="off">
    </div>
</div>
<script type="text/javascript">
(function () {
    var searchTypes = document.querySelectorAll('input[name="type"]');
    var form = document.querySelector('#super-search-fm');
    var searchInput = document.querySelector('#search-text');
    var newWindowToggle = document.querySelector('#set-search-blank');
    var searchGroups = document.querySelectorAll('.search-group');
    var storagePrefix = 'superSearch';

    if (!searchTypes.length || !form || !searchInput || !newWindowToggle) {
        return;
    }

    function saveSetting(key, value) {
        window.localStorage.setItem(storagePrefix + key, value);
    }

    function getSetting(key) {
        return window.localStorage.getItem(storagePrefix + key);
    }

    function getSelectedValue() {
        var selected = document.querySelector('input[name="type"]:checked');
        return selected ? selected.value : searchTypes[0].value;
    }

    function getSelectedPlaceholder() {
        var selected = document.querySelector('input[name="type"]:checked');
        return selected ? selected.getAttribute('data-placeholder') : '';
    }

    function setPlaceholder(value) {
        searchInput.setAttribute('placeholder', value || '');
    }

    function setFormAction(value) {
        form.action = value || '';
    }

    function isNewWindow() {
        var setting = getSetting('newWindow');
        return setting ? setting == 1 : true;
    }

    function setTarget(openInNewWindow) {
        if (openInNewWindow) {
            form.target = '_blank';
        } else {
            form.removeAttribute('target');
        }
    }

    function setActiveGroup(input) {
        for (var i = 0; i < searchGroups.length; i++) {
            searchGroups[i].classList.remove('s-current');
        }

        var group = input.parentNode && input.parentNode.parentNode && input.parentNode.parentNode.parentNode;
        if (group) {
            group.classList.add('s-current');
        }
    }

    function loadSelectedType() {
        var savedType = getSetting('type');
        var selectedInput = savedType ? document.querySelector('input[name="type"][value="' + savedType + '"]') : null;
        if (!selectedInput) {
            selectedInput = searchTypes[0];
        }

        selectedInput.checked = true;
        setActiveGroup(selectedInput);
    }

    function refreshSearchState() {
        setPlaceholder(getSelectedPlaceholder());
        setFormAction(getSelectedValue());
        newWindowToggle.checked = isNewWindow();
        setTarget(isNewWindow());
    }

    function onTypeChange(event) {
        var input = event.target;
        setPlaceholder(input.getAttribute('data-placeholder'));
        setFormAction(input.value);
        saveSetting('type', input.value);
        searchInput.focus();
        setActiveGroup(input);
    }

    function onNewWindowChange(event) {
        saveSetting('newWindow', event.target.checked ? 1 : -1);
        setTarget(event.target.checked);
    }

    function onFormSubmit(event) {
        event.preventDefault();

        if (searchInput.value === '') {
            searchInput.focus();
            return false;
        }

        setFormAction(getSelectedValue() + searchInput.value);
        setTarget(isNewWindow());

        if (isNewWindow()) {
            window.open(form.action, +new Date());
        } else {
            window.location.href = form.action;
        }

        return false;
    }

    loadSelectedType();
    refreshSearchState();

    for (var i = 0; i < searchTypes.length; i++) {
        searchTypes[i].addEventListener('change', onTypeChange);
    }

    newWindowToggle.addEventListener('change', onNewWindowChange);
    form.addEventListener('submit', onFormSubmit);
})();
</script>
