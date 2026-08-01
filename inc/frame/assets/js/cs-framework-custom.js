/**
 * ============================================================
 * WebStack 主题 - CS Framework 深度重构脚本
 * Version: 1.0.0
 * 说明: 增强 CS Framework 交互体验
 * ============================================================
 */
;(function ($, window, document, undefined) {
  'use strict';

  // 延迟到 CS Framework 初始化完成后运行
  $(document).ready(function () {

    var $framework = $('.cs-framework.cs-option-framework');

    if (!$framework.length) {
      return;
    }

    /**
     * 1. 搜索过滤功能
     * 在侧边栏添加搜索框，实时过滤设置选项卡
     */
    function initSearchFilter() {
      var $nav = $framework.find('.cs-nav');
      var $navList = $nav.find('ul:first');
      var $sections = $framework.find('.cs-sections > .cs-section');

      // 如果只有一个选项则不需要搜索
      if ($nav.find('ul:first > li').length <= 1) {
        return;
      }

      // 构建搜索框
      var $searchBox = $(
        '<div class="cs-nav-search">' +
          '<i class="fa fa-search cs-search-icon"></i>' +
          '<input type="text" class="cs-nav-search-input" placeholder="搜索设置项..." />' +
          '<span class="cs-search-clear"><i class="fa fa-times"></i></span>' +
        '</div>'
      );

      // 构建空结果提示
      var $emptyHint = $(
        '<div class="cs-nav-search-empty">' +
          '<i class="fa fa-search"></i>' +
          '未找到相关设置项' +
        '</div>'
      );

      $nav.prepend($searchBox);
      $navList.after($emptyHint);

      var $searchInput = $searchBox.find('.cs-nav-search-input');
      var $clearBtn = $searchBox.find('.cs-search-clear');

      // 为每个导航项添加徽标和搜索数据
      $nav.find('ul:first > li').each(function () {
        var $li = $(this);
        var $link = $li.find('> a');
        var text = $link.text().trim();
        var sectionName = $link.data('section') || '';

        // 计算该项的字段数量
        var fieldCount = 0;
        if (sectionName) {
          var $section = $('#cs-tab-' + sectionName);
          if ($section.length) {
            fieldCount = $section.find('.cs-element').length;
          }
        }

        // 添加搜索数据属性
        $li.attr('data-search-text', text.toLowerCase());
        $li.attr('data-section-name', sectionName);

        // 添加字段数量徽标（如果该项会切换内容）
        if (fieldCount > 0 && !$li.hasClass('cs-sub')) {
          $link.append('<span class="cs-badge">' + fieldCount + '</span>');
        }

        // 处理子菜单项的搜索数据
        $li.find('ul li').each(function () {
          var $subLink = $(this).find('a');
          var subText = $subLink.text().trim();
          var subSection = $subLink.data('section') || '';
          var subCount = 0;

          if (subSection) {
            var $subSection = $('#cs-tab-' + subSection);
            if ($subSection.length) {
              subCount = $subSection.find('.cs-element').length;
            }
          }

          $(this).attr('data-search-text', (text + ' ' + subText).toLowerCase());
          $(this).attr('data-section-name', subSection);

          if (subCount > 0) {
            $subLink.append('<span class="cs-badge">' + subCount + '</span>');
          }
        });
      });

      // 搜索输入事件
      $searchInput.on('input', function () {
        var keyword = $(this).val().trim().toLowerCase();
        var hasResult = false;

        if (keyword === '') {
          // 清空搜索时显示全部
          $navList.find('> li').removeClass('cs-nav-hidden');
          $emptyHint.hide();
          $clearBtn.hide();
          return;
        }

        $clearBtn.show();

        // 过滤顶级菜单项
        $navList.find('> li').each(function () {
          var $li = $(this);
          var searchText = $li.attr('data-search-text') || '';
          var isMatch = searchText.indexOf(keyword) !== -1;

          // 子菜单项也参与匹配
          var subMatch = false;
          $li.find('ul li').each(function () {
            var subText = $(this).attr('data-search-text') || '';
            if (subText.indexOf(keyword) !== -1) {
              subMatch = true;
              $(this).show();
            } else {
              $(this).hide();
            }
          });

          if (isMatch || subMatch) {
            $li.removeClass('cs-nav-hidden');
            hasResult = true;

            // 如果有匹配的子项但顶级不匹配，展开子菜单
            if (subMatch && !isMatch) {
              $li.find('ul').show();
              $li.addClass('cs-tab-active');
            }
          } else {
            $li.addClass('cs-nav-hidden');
          }
        });

        // 显示空结果提示
        if (!hasResult) {
          $emptyHint.show();
        } else {
          $emptyHint.hide();
        }
      });

      // 清除搜索按钮
      $clearBtn.on('click', function () {
        $searchInput.val('').trigger('input');
        $searchInput.focus();
      });

      // 输入框 ESC 清除
      $searchInput.on('keydown', function (e) {
        if (e.keyCode === 27) {
          $searchInput.val('').trigger('input');
        }
      });
    }

    /**
     * 2. Toast 通知功能（带去重）
     * 替代原有的文字闪现保存提示
     */
    function initToast() {
      // 创建 Toast 容器
      if (!$('#cs-toast-container').length) {
        $('body').append('<div id="cs-toast-container"></div>');
      }

      var $container = $('#cs-toast-container');
      var activeToasts = {}; // 按 title 去重

      // 暴露到全局
      window.csShowToast = function (type, title, message, duration) {
        var icons = {
          success: '\uf00c',
          error: '\uf00d',
          info: '\uf129'
        };
        var icon = icons[type] || icons.info;
        var time = duration || 3000;

        // 如果相同标题的 toast 已经存在，先移除它
        if (activeToasts[title]) {
          activeToasts[title].remove();
          delete activeToasts[title];
        }

        var $toast = $(
          '<div class="cs-toast cs-toast-' + (type || 'info') + '">' +
            '<div class="cs-toast-icon"><i class="fa ' + icon + '"></i></div>' +
            '<div class="cs-toast-content">' +
              '<div class="cs-toast-title">' + (title || '') + '</div>' +
              (message ? '<div class="cs-toast-message">' + message + '</div>' : '') +
            '</div>' +
            '<button type="button" class="cs-toast-close">&times;</button>' +
          '</div>'
        );

        $container.append($toast);
        activeToasts[title] = $toast;

        // 关闭按钮
        $toast.find('.cs-toast-close').on('click', function () {
          hideToast($toast, title);
        });

        // 自动消失
        if (time > 0) {
          setTimeout(function () {
            hideToast($toast, title);
          }, time);
        }

        return $toast;
      };

      function hideToast($toast, title) {
        if (title && activeToasts[title] && activeToasts[title][0] === $toast[0]) {
          delete activeToasts[title];
        }
        $toast.addClass('cs-toast-hiding');
        setTimeout(function () {
          $toast.remove();
        }, 400);
      }
    }

    /**
     * 3. 保存进度增强
     * 替换原生保存提示为 Toast（去重防连发）
     */
    function initSaveEnhance() {
      var $saveAjax = $('#cs-save-ajax');

      // 阻止原始的文字提示显示
      if ($saveAjax.length) {
        $saveAjax.hide();
      }

      // 定时轮询替代 MutationObserver，避免 fadeIn/fadeOut 动画造成重复触发
      var $saveBtn = $framework.find('.cs-save');
      var lastShown = 0;
      var checkInterval = null;

      var checkAjaxVisible = function () {
        if (!$saveAjax.length) {
          return;
        }

        // 原生保存成功后 $ajax 会 fadeIn(), jQuery fadeIn 会设置 display 为 block/inline
        var isVisible = $saveAjax.is(':visible');
        var now = Date.now();

        if (isVisible && (now - lastShown) > 1500) {
          lastShown = now;
          window.csShowToast('success', '设置已保存', '所有更改已成功保存');

          // 立即隐藏原始提示，避免也显示文字
          $saveAjax.hide();
        }
      };

      // 保存按钮点击后启动检测，一定时间后停止
      $saveBtn.on('click', function () {
        if (!checkInterval) {
          checkInterval = setInterval(checkAjaxVisible, 200);
          // 10 秒后自动停止检测
          setTimeout(function () {
            if (checkInterval) {
              clearInterval(checkInterval);
              checkInterval = null;
            }
          }, 10000);
        }
      });
    }

    /**
     * 4. 选项卡切换动画增强
     */
    function initTabAnimation() {
      var $nav = $framework.find('.cs-nav');

      // 拦截默认的选项卡切换
      $nav.find('ul:first a').on('click', function (e) {
        var $el = $(this);
        var $next = $el.next();
        var $target = $el.data('section');

        // 如果不是子菜单切换
        if (!$next.is('ul') && $target) {
          var $targetSection = $('#cs-tab-' + $target);

          // 确保有动画效果（CSS 中已有 csFadeIn 动画）
          if ($targetSection.length) {
            $targetSection.css('animation', 'none');
            void $targetSection[0].offsetHeight; // 触发重绘以重新播放动画
            $targetSection.css('animation', '');
          }
        }
      });
    }

    /**
     * 5. 增加显示的当前选项卡标识
     */
    function initCurrentSectionIndicator() {
      var $nav = $framework.find('.cs-nav');

      $nav.find('ul:first a[data-section]').on('click', function () {
        var sectionName = $(this).data('section');

        // 在内容区顶部显示当前分区名称
        var $activeSection = $('#cs-tab-' + sectionName);
        var $contentArea = $framework.find('.cs-content');

        // 移除旧的描述
        $contentArea.find('.cs-section-description').remove();

        // 获取当前分区标题
        var $activeLink = $nav.find('a[data-section="' + sectionName + '"]');
        var tabTitle = $activeLink.text().trim().replace(/\d+$/, '').trim();

        if (tabTitle && sectionName) {
          var $desc = $('<div class="cs-section-description">' + tabTitle + '</div>');
          $contentArea.prepend($desc);
        }
      });
    }

    /**
     * 6. 初始化所有功能
     */
    function init() {
      initSearchFilter();
      initToast();
      initSaveEnhance();
      initTabAnimation();
      initCurrentSectionIndicator();
    }

    init();
  });

})(jQuery, window, document);