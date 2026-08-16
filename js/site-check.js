/**
 * WebStack 后台「网址」列表页：一键测活 + 批量删除
 * 依赖 ioSiteCheck（inc/site-check.php 中 wp_localize_script 注入）
 * UI：常驻卡片面板（不挤占 WP 原生工具条，测活时卡片内展开进度）
 */
(function ($) {
	'use strict';

	if (typeof ioSiteCheck === 'undefined') {
		return;
	}

	var cfg  = ioSiteCheck,
		i18n = cfg.i18n,
		state = { total: 0, checked: 0, alive: 0, dead: 0, unknown: 0, busy: false };

	var $panel, $btnStart, $btnSelect, $btnDelete, $statsLine, $progressWrap, $progressInner, $progressText;

	function ajax(action, data) {
		return $.ajax({
			url: cfg.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: $.extend({ action: action, nonce: cfg.nonce }, data || {})
		});
	}

	function showError(msg) {
		$progressWrap.show();
		$progressText.text(msg);
		window.alert(msg);
	}

	function updateProgress() {
		var pct = state.total ? Math.floor((state.checked / state.total) * 100) : 0;
		$progressInner.css('width', pct + '%');
		$progressText.text(i18n.checked + ' ' + state.checked + ' ' + i18n.of + ' ' + state.total +
			'（' + i18n.normal + ' ' + state.alive + ' · ' + i18n.dead + ' ' + state.dead + ' · ' + i18n.unknown + ' ' + state.unknown + '）');
	}

	function setBusy(b) {
		state.busy = b;
		$btnStart.prop('disabled', b);
		$btnSelect.prop('disabled', b);
		$btnDelete.prop('disabled', b);
		$btnStart.text(b ? i18n.checking : i18n.start);
		if (b) {
			$progressWrap.show();
		}
	}

	function collect(results) {
		$.each(results || [], function () {
			state.checked++;
			if (this.status === 'alive') { state.alive++; }
			else if (this.status === 'dead') { state.dead++; }
			else { state.unknown++; }
		});
		updateProgress();
	}

	function finish(stats) {
		setBusy(false);
		if (stats) {
			$progressText.text(i18n.done + '：' + i18n.normal + ' ' + stats.alive + ' · ' + i18n.dead + ' ' + stats.dead + ' · ' + i18n.unknown + ' ' + stats.unknown);
		} else {
			$progressText.text(i18n.done);
		}
		// 结果已写入数据库，刷新列表页展示最新状态列
		setTimeout(function () { window.location.reload(); }, 1500);
	}

	function handleError(res) {
		return res && res.data && res.data.msg ? res.data.msg : i18n.error;
	}

	function runCheck() {
		function next() {
			// 每批并行检测数量由主题设置「网址测活 → 并行检测数量」决定
			ajax('io_site_check', { batch: cfg.batch || 20 })
				.done(function (res) {
					if (!res || !res.success) {
						showError(handleError(res));
						setBusy(false);
						return;
					}
					var d = res.data;
					collect(d.checked);
					if (d.done) {
						finish(d.stats);
					} else {
						next();
					}
				})
				.fail(function () {
					showError(i18n.error);
					setBusy(false);
				});
		}
		next();
	}

	function startCheck() {
		if (state.busy) { return; }

		ajax('io_site_check_init', { restart: 0 })
			.done(function (res) {
				if (!res || !res.success) {
					showError(handleError(res));
					return;
				}
				var d = res.data;

				if (d.resuming) {
					var doneSoFar = d.total - d.remaining;
					state.checked = doneSoFar;
					if (d.stats) {
						state.alive = d.stats.alive;
						state.dead = d.stats.dead;
						state.unknown = d.stats.unknown;
					}
					state.total = d.total;
					updateProgress();

					// 取消 = 重新开始检测
					if (!window.confirm(i18n.confirmResume.replace('%d', doneSoFar).replace('%d', d.total))) {
						ajax('io_site_check_init', { restart: 1 })
							.done(function (r2) {
								if (!r2 || !r2.success) {
									showError(handleError(r2));
									return;
								}
								state.total = r2.data.total;
								state.checked = 0;
								// 重新开始 = 全新运行，从 0 累计（避免与历史 meta 重复计数）
								state.alive = 0;
								state.dead = 0;
								state.unknown = 0;
								updateProgress();
								if (state.total === 0) {
									window.alert(i18n.noSites);
									return;
								}
								setBusy(true);
								runCheck();
							})
							.fail(function () { showError(i18n.error); });
						return;
					}

					if (state.total === 0) { window.alert(i18n.noSites); return; }
					setBusy(true);
					runCheck();
					return;
				}

				state.total = d.total;
				state.checked = 0;
				// 全新运行：从 0 开始累计本次检测结果（避免与历史 meta 重复计数）
				state.alive = 0;
				state.dead = 0;
				state.unknown = 0;
				updateProgress();

				if (state.total === 0) {
					window.alert(i18n.noSites);
					return;
				}
				if (!window.confirm(i18n.confirmStart.replace('%d', state.total))) {
					return;
				}
				setBusy(true);
				runCheck();
			})
			.fail(function () { showError(i18n.error); });
	}

	function selectDead() {
		var n = 0;
		$('tr.type-sites').each(function () {
			if ($(this).find('.io-check-badge.io-dead').length) {
				// trigger('change') 让 WP 同步行高亮与批量计数
				$(this).find('input[name="post[]"]').prop('checked', true).trigger('change');
				n++;
			}
		});
		window.alert(n ? i18n.selectDead + '：' + n : i18n.noDead);
	}

	function deleteAllDead() {
		if (state.busy) { return; }

		ajax('io_site_check_init', { restart: 0 })
			.done(function (res) {
				if (!res || !res.success) {
					showError(handleError(res));
					return;
				}
				var deadCount = (res.data.stats && res.data.stats.dead) || 0;
				if (deadCount === 0) {
					window.alert(i18n.noDead);
					return;
				}
				if (!window.confirm(i18n.confirmDelete.replace('%d', deadCount))) {
					return;
				}

				ajax('io_site_delete_all_dead')
					.done(function (r2) {
						if (!r2 || !r2.success) {
							showError(handleError(r2));
							return;
						}
						window.alert(i18n.deleted.replace('%d', r2.data.count));
						window.location.reload();
					})
					.fail(function () { showError(i18n.error); });
			})
			.fail(function () { showError(i18n.error); });
	}

	function loadStats() {
		ajax('io_site_check_stats')
			.done(function (res) {
				if (!res || !res.success) { return; }
				var s = res.data;
				if (!s || typeof s.total === 'undefined') { return; }
				var html = i18n.total + ' <b>' + s.total + '</b> · ' +
					i18n.normal + ' <b>' + s.alive + '</b> · ' +
					i18n.dead + ' <b>' + s.dead + '</b> · ' +
					i18n.unknown + ' <b>' + s.unknown + '</b>';
				if (s.unchecked > 0) {
					html += ' <span class="io-tip">(' + s.unchecked + ' ' + i18n.uncheckedTip + ')</span>';
				}
				$statsLine.html(html);
			});
	}

	function buildPanel() {
		$panel = $('<div id="io-site-check-panel"></div>');

		// 第一行：按钮 + 统计（常驻，不随测活状态变化）
		var $toolbar = $('<div class="io-toolbar"></div>');
		$btnStart = $('<button type="button" class="button button-primary io-btn-check">' + i18n.start + '</button>');
		$btnSelect = $('<button type="button" class="button io-btn-mid">' + i18n.selectDead + '</button>');
		$btnDelete = $('<button type="button" class="button io-btn-danger">' + i18n.deleteAllDead + '</button>');
		$statsLine = $('<span class="io-stats"></span>');
		$toolbar.append($btnStart, $btnSelect, $btnDelete, $statsLine);

		// 第二行：进度 + 汇总（测活时展开）
		$progressWrap = $('<div class="io-progress-wrap"></div>');
		$progressInner = $('<div class="io-progress-inner"></div>');
		$progressText = $('<div class="io-progress-text"></div>');
		$progressWrap
			.append($('<div class="io-progress"></div>').append($progressInner))
			.append($progressText);

		$panel.append($toolbar, $progressWrap);

		$btnStart.on('click', startCheck);
		$btnSelect.on('click', selectDead);
		$btnDelete.on('click', deleteAllDead);
	}

	$(function () {
		buildPanel();

		// 面板放在表格正上方（原生 subsubsub 与 tablenav 保持原位，不被卡片挤压）
		var $table = $('#posts-filter table.wp-list-table').first();
		if ($table.length) {
			$table.before($panel);
		} else {
			$('#posts-filter').before($panel); // 兜底
		}

		// 修复：WP 列表筛选表单无 action，提交会保留当前 URL 的分页参数 paged，
		// 导致停在非第 1 页时筛选结果为空（时有时无）。提交时清除 paged，始终从第 1 页展示。
		$('#posts-filter').on('submit', function () {
			var url = new URL(window.location.href);
			url.searchParams.delete('paged');
			var clean = url.pathname + url.search;
			if (clean !== window.location.pathname + window.location.search) {
				$(this).attr('action', clean);
			}
		});

		loadStats();
	});
})(jQuery);
