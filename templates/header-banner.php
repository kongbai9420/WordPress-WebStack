<?php
/*
 * @Theme Name:WebStack
 * @Theme URI:https://www.iotheme.cn/
 * @Author: iowen
 * @Author URI: https://www.iowen.cn/
 * @Date: 2019-02-22 21:26:02
 * @LastEditors: iowen
 * @LastEditTime: 2024-07-30 17:31:38
 * @FilePath: /WebStack/templates/header-banner.php
 * @Description: 
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }  ?>
<nav class="navbar user-info-navbar" role="navigation">
    <div class="navbar-content">
      <ul class="user-info-menu list-inline list-unstyled">
        <li class="hidden-xs">
            <a href="#" data-toggle="sidebar">
                <i class="fa fa-bars"></i>
            </a>
        </li>
        <!-- 天气 -->
        <li>
          <div id="he-plugin-simple" class="weather-slot"></div>
        </li>
        <!-- 天气 end -->
      </ul>
      <ul class="user-info-menu list-inline list-unstyled">
        <li>
            <a href="javascript:;" class="theme-toggle" id="theme-toggle" title="切换显示模式" aria-label="切换显示模式">
                <i class="fa fa-moon-o theme-toggle-icon" aria-hidden="true"></i>
                <span class="theme-toggle-text hidden-sm hidden-xs">暗色</span>
            </a>
        </li>
        <li class="hidden-sm hidden-xs">
            <a href="hhttps://github.com/kongbai9420/WordPress-WebStack" target="_blank"><i class="fa fa-github"></i> GitHub</a>
        </li>
      </ul>
    </div>
</nav>
<script>
(function () {
    var STORAGE_KEY = 'webstack_theme_mode';
    var body = document.body;
    var toggle = document.getElementById('theme-toggle');
    if (!body || !toggle) return;

    var icon = toggle.querySelector('.theme-toggle-icon');
    var text = toggle.querySelector('.theme-toggle-text');

    function getCurrentMode() {
        if (body.classList.contains('black')) return 'black';
        return 'white';
    }

    function setMode(mode, persist) {
        body.classList.remove('black', 'white');
        body.classList.add(mode);

        if (icon) {
            icon.className = mode === 'black' ? 'fa fa-sun-o theme-toggle-icon' : 'fa fa-moon-o theme-toggle-icon';
        }

        if (text) {
            text.textContent = mode === 'black' ? '亮色' : '暗色';
        }

        if (persist) {
            try {
                localStorage.setItem(STORAGE_KEY, mode);
            } catch (e) {}
        }
    }

    try {
        var savedMode = localStorage.getItem(STORAGE_KEY);
        if (savedMode === 'black' || savedMode === 'white') {
            setMode(savedMode, false);
        } else {
            setMode(getCurrentMode(), false);
        }
    } catch (e) {
        setMode(getCurrentMode(), false);
    }

    toggle.addEventListener('click', function () {
        var nextMode = getCurrentMode() === 'black' ? 'white' : 'black';
        setMode(nextMode, true);
    });
})();

(function () {
    var weatherContainer = document.getElementById('he-plugin-simple');
    if (!weatherContainer) return;

    var saveData = false;
    var reducedMotion = false;

    try {
        saveData = !!(navigator.connection && navigator.connection.saveData);
    } catch (e) {}

    try {
        reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    } catch (e) {}

    if (saveData) {
        weatherContainer.innerHTML = '<span class="weather-fallback">天气已延迟加载</span>';
        return;
    }

    function initWeatherWidget() {
        if (window.__webstackWeatherLoaded) return;
        window.__webstackWeatherLoaded = true;

        (function (T, h, i, n, k, P, a, g) {
            g = function () {
                P = h.createElement(i);
                a = h.getElementsByTagName(i)[0];
                P.src = k;
                P.charset = 'utf-8';
                P.async = 1;
                a.parentNode.insertBefore(P, a);
            };
            T['ThinkPageWeatherWidgetObject'] = n;
            T[n] || (T[n] = function () {
                (T[n].q = T[n].q || []).push(arguments);
            });
            T[n].l = +new Date();
            if (T.attachEvent) {
                T.attachEvent('onload', g);
            } else {
                T.addEventListener('load', g, false);
            }
        })(window, document, 'script', 'tpwidget', '//widget.seniverse.com/widget/chameleon.js');

        var startWidget = function () {
            if (typeof window.tpwidget !== 'function') {
                window.setTimeout(startWidget, 120);
                return;
            }
            window.tpwidget('init', {
                flavor: 'slim',
                location: 'WX4FBXXFKE4F',
                geolocation: 'enabled',
                language: 'zh-chs',
                unit: 'c',
                theme: 'chameleon',
                container: 'he-plugin-simple',
                bubble: 'enabled',
                alarmType: 'badge',
                color: '#999999',
                uid: 'UD5EFC1165',
                hash: '2ee497836a31c599f67099ec09b0ef62'
            });
            window.tpwidget('show');
        };

        window.setTimeout(startWidget, reducedMotion ? 1200 : 600);
    }

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(initWeatherWidget, { timeout: 2000 });
    } else {
        window.setTimeout(initWeatherWidget, 1200);
    }
})();
</script>
