<?php
include_once APPPATH . 'views/_common/header.php';
include_once APPPATH . 'views/_common/top.php';
$option = '';
foreach ($data['menu'] as $key => $val) {
	$option .= <<<HTML
<option value="{$val['product_cd']}">{$val['product_nm']}</option>\n
HTML;
}
$order = 0;
if (!empty($data['order'])) {
	$order = 1;
}
?>
	<style>
		.custom-combobox-toggle {
			top: 9%;
		}

		.custom-combobox-input {
			margin: 0;
			padding: 5px 10px;
		}

		.ui-autocomplete {
			max-height: 500px;
			overflow-y: auto;
			/* prevent horizontal scrollbar */
			overflow-x: hidden;
		}

		* html .ui-autocomplete {
			height: 100px;
		}

		input {
			background: white !important;
		}

		#menu_nm {
			width: 380px !important;
			padding-right: 40px;
		}

		img {
			width: 150px;
			height: 150px;
		}

	</style>
	<script>
		const countDownTimer = function (id, data) {
			var _vDate = new Date(data); // 전달 받은 일자
			var _second = 1000;
			var _minute = _second * 60;
			var _hour = _minute * 60;
			var _day = _hour * 60;
			var _timer;

			function showRemaining() {
				var now = new Date();
				var disDt = _vDate - now;
				if (disDt < 0) {
					clearInterval(_timer);
					document.getElementById(id).textContent = '주문 시간이 종료 되었습니다.';
					return;
				}

				var days = Math.floor(disDt / _day);
				var hours = Math.floor((disDt % _day) / _hour);
				var minutes = Math.floor((disDt % _hour) / _minute);
				var seconds = Math.floor((disDt % _minute) / _second);

				document.getElementById(id).textContent = days > 0 ? days + '일 ' : '';
				document.getElementById(id).textContent += hours > 0 ? hours + '시간 ' : '';
				document.getElementById(id).textContent += minutes > 0 ? minutes + '분 ' : '';
				document.getElementById(id).textContent += seconds > 0 ? seconds + '초' : '';
			}

			_timer = setInterval(showRemaining, 1000);
		}

		countDownTimer('sample02', '<?=$data['timer']?>'); // countDownTimer('sample02', '02/24/2021 23:59');

		$(function () {
			$.widget("custom.combobox", {
				_create: function () {
					this.wrapper = $("<span>")
							.attr("style", "position: relative; display: inline-block;")
							.addClass("custom-combobox")
							.insertAfter(this.element);

					this.element.hide();
					this._createAutocomplete();
					this._createShowAllButton();
				},

				_createAutocomplete: function () {
					var selected = this.element.children(":selected"),
							value = selected.val() ? selected.text() : "";

					this.input = $("<input id='menu_nm'>")
							.appendTo(this.wrapper)
							.val(value)
							.attr("placeholder", "메뉴를 입력해주세요.")
							.addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left form-control")
							.autocomplete({
								delay: 0,
								minLength: 0,
								source: $.proxy(this, "_source")
							});

					this._on(this.input, {
						autocompleteselect: function (event, ui) {
							ui.item.option.selected = true;
							this._trigger("select", event, {
								item: ui.item.option
							});
							$("#code").val(ui.item.code);
							$.ajax({
								type: 'post',
								dataType: 'json',
								url: '/order/menu',
								data: {
									'code': ui.item.code
								},
								success: function (request) {
									$("#thumbnail").attr("src", request.menu.product_img);
									$("#content").text(request.menu.content);
									$("#drink_view").attr("href", "https://www.starbucks.co.kr/menu/drink_view.do?product_cd=" + request.menu.product_cd)
								},
								error: function (request, status, error) {
									console.log('code: ' + request.status + "\n" + 'message: ' + JSON.parse(request.responseText) + "\n" + 'error: ' + error);
								}
							});


						},

						autocompletechange: "_removeIfInvalid"
					});
				},

				_createShowAllButton: function () {
					var input = this.input,
							wasOpen = false;

					$("<a>")
							.attr("tabIndex", -1)
							.attr("title", "전체 메뉴 보기")
							.attr("style", "position: absolute; top: 0; right: 0")
							.text("▼")
							.tooltip()
							.appendTo(this.wrapper)
							.removeClass("ui-corner-all")
							.addClass("custom-combobox-toggle text-right btn btn-success")
							.on("mousedown", function () {
								wasOpen = input.autocomplete("widget").is(":visible");
							})
							.on("click", function () {
								input.trigger("focus");

								// Close if already visible
								if (wasOpen) {
									return;
								}

								// Pass empty string as value to search for, displaying all results
								input.autocomplete("search", "");
							});
				},

				_source: function (request, response) {
					var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
					response(this.element.children("option").map(function () {
						var text = $(this).text();
						var code = $(this).val();
						if (this.value && (!request.term || matcher.test(text))) {
							return {
								label: text,
								value: text,
								code: code,
								option: this
							};
						}
					}));
				},

				_removeIfInvalid: function (event, ui) {

					// Selected an item, nothing to do
					if (ui.item) {
						return;
					}

					// Search for a match (case-insensitive)

					var value = this.input.val(),
							valueLowerCase = value.toLowerCase(),
							valid = false;

					this.element.children("option").each(function () {
						// console.log($(this).text().toLowerCase());
						if ($(this).text().toLowerCase() === valueLowerCase) {
							this.selected = valid = true;
							return false;
						}
					});

					// Found a match, nothing to do
					if (valid) {
						return $("#code").val("");
					}

					// Remove invalid value
					alert("일치하는 메뉴가 없습니다.");
					this.input.val("");
					this.element.val("");
					// this._delay(function () {
					// 	this.input.tooltip("close").attr("title", "");
					// }, 2500);
					this.input.autocomplete("instance").term = "";
				},

				_destroy: function () {
					this.wrapper.remove();
					this.element.show();
				}
			});

			$("#combobox").combobox();

		});

		$(document).ready(function () {
			var ordnum = '<?= $data['buyer']['ordnum'] ?>';
			$("#menu_nm").val('<?=isset($data['order']['product_nm']) ? $data['order']['product_nm'] : ''?>');
			$("#code").val('<?=isset($data['order']['product_cd']) ? $data['order']['product_cd'] : ''?>');
			$("#size").val('<?=isset($data['order']['product_size']) ? $data['order']['product_size'] : 'tall'?>');
			$("#cnt").val('<?=isset($data['order']['product_cnt']) ? $data['order']['product_cnt'] : '1'?>');
			$("#comment").val('<?=isset($data['order']['comment']) ? $data['order']['comment'] : ''?>');


			$("#logout").click(function () {
				window.location.href = "/member/logout";
			});

			$("#print").click(function () {
				window.location.href = "/order/mprnt?ordnum=" + ordnum;
			});

			$('#myModal').on('shown.bs.modal', function () {
				var ord_date;
				var style;
				var button;

				$('.modal-body').html('');

				$.ajax({
					type: 'post',
					dataType: 'json',
					url: '/order/get',
					data: {
						'ordnum': ordnum
					},
					success: function (request) {
						var list = [];
						var table = $('<table />', {"class": "table table-sm table-bordered"}).prepend(
								$('<thead/>').prepend(
										$('<tr/>').prepend(
												$('<th/>').text('주문일'),
												$('<th/>').text('메뉴'),
												$('<th/>').text('사이즈'),
												$('<th/>').text('수량'),
												$('<th/>').text('재주문')
										)
								), $('<tbody/>'));

						for (var i in request.order) {
							ord_date = request.order[i].regdate.split(' ')[0];
							style = '';
							button = $('<td/>').prepend($('<button />', {
								"class": "btn btn-success btn-sm",
								"data-code": request.order[i].product_cd,
								"data-name": request.order[i].product_nm,
								"data-size": request.order[i].product_size,
								"data-cnt": request.order[i].product_cnt,
								"data-dismiss": "modal",
							}).text('입력').click(function () {
								$('#code').val($(this).attr('data-code'));
								$('#menu_nm').val($(this).attr('data-name'));
								$('#size').val($(this).attr('data-size'));
								$('#cnt').val($(this).attr('data-cnt'));

								alert('주문이 입력 되었습니다. 주문하기를 다시 눌러주세요.');
								$('#myModal').modal("hide"); //닫기
								$('#order').focus();
							}));

							if (ordnum === request.order[i].ordnum) {
								ord_date = '오늘의 주문';
								style = 'table-danger';
								button = $('<td />').text('');
							}
							list.push(
									$('<tr />', {"class": style}).prepend(
											$('<td />').text(ord_date),
											$('<td />').text(request.order[i].product_nm),
											$('<td />').text(request.order[i].product_size),
											$('<td />').text(request.order[i].product_cnt + '개'),
											button)
							);
						}
						$('.modal-body').prepend(table.prepend(list));
					},
					error: function (request, status, error) {
						$('#guide').append(
								$('<div />').text('주문서 불러오기 실패입니다.')
						);
						console.log('code: ' + request.status + "\n" + 'message: ' + JSON.parse(request.responseText) + "\n" + 'error: ' + error);
					}
				});

			});

			$("#order").click(function () {
				var menu_code = $("#code").val();
				var menu_nm = $("#menu_nm").val();
				var size = $("#size").val();
				var cnt = $("#cnt").val();
				var comment = $("#comment").val();

				if (menu_nm === '' && menu_code === '') {
					return alert('메뉴를 입력해 주세요.');
				}

				if (size === '') {
					return alert('사이즈를 입력해 주세요.');
				}

				if (cnt === '') {
					return alert('수량을 입력해 주세요.');
				}

				var str = '주문 하시겠습니까? \n' + menu_nm + ' / ' + size + ' / ' + cnt + '개'
				if (!confirm(str)) {
					return 0;
				}

				$.ajax({
					type: 'post',
					dataType: 'json',
					url: '/order/set',
					data: {
						'menu_code': menu_code,
						'menu_nm': menu_nm,
						'size': size,
						'cnt': cnt,
						'comment': comment,
						'ordnum': ordnum
					},
					success: function (request) {
						$('#myorder').trigger('click');

					},
					error: function (request, status, error) {
						alert(JSON.parse(request.responseText));
						console.log('code: ' + request.status + "\n" + 'message: ' + JSON.parse(request.responseText) + "\n" + 'error: ' + error);
					}
				});
			});

		});

	</script>
	<body>
	<div class="container bd-content">
		<div class="bd-callout bd-callout-warning" style="text-align: center;">
			<h5><?= '주문대상 : ' . $data['buyer']['member_name'] ?></h5>
			<p><?= $data['buyer']['comment'] ?></p>
			<div class="alert alert-success" role="alert">
				<h5><strong><span class="" id="sample02">0시 00분 00초</span></strong></h5>
			</div>
		</div>
	</div>
	<div class="container" style="max-width: 1000px; margin-top: 20px;">
		<br>
		<div class="image" style="text-align: center; margin-bottom: 10px">
			<a id="drink_view" href="https://www.starbucks.co.kr/menu/drink_list.do" target="_blank">
			<img src="https://image.istarbucks.co.kr/common/img/main/rewards-logo.png" id="thumbnail" class="ttip" data-bs-toggle="tooltip" data-bs-placement="right" title="스타벅스 홈페이지로 이동"
			style="margin-bottom: 10px">
			</a><br>
			<span id="content" style="margin: 10px"></span>
		</div>
		<div class="row g-<?=$data['buyer']['option'] === '1' ? '1' : '3'?> 1" style="text-align: center">
			<div class="col-auto">
				<input type="hidden" id="code">
				<select id="combobox">
					<option value>메뉴를 선택하세요.</option>
					<?= $option ?>
				</select>
			</div>
			<div class="col-auto">
				<select id="size" class="form-select ttip" data-bs-toggle="tooltip" data-bs-placement="top" title="사이즈">
					<option value="tall">Tall</option>
					<option value="grande">Grande</option>
					<option value="venti">Venti</option>
				</select>
			</div>
			<div class="col-auto">
				<select id="cnt" class="form-select ttip" title="수량" data-bs-toggle="tooltip" data-bs-placement="top">
					<option value="1">1개</option>
					<!--<option value="2">2개</option>-->
					<!--<option value="3">3개</option>-->
					<!--<option value="4">4개</option>-->
					<!--<option value="5">5개</option>-->
				</select>
			</div>
			<?php
			if ($data['buyer']['option'] === '1') {
				?>
				<div class="col-auto">
					<input type="text" class="form-control ttip" data-bs-toggle="tooltip" data-bs-placement="top" id="comment" placeholder="comment" maxlength='20' title="옵션 추가">
				</div>
				<?php
			}
			?>

			<div class="col-auto">
				<button id="order" class="btn btn-outline-success">주문하기
				</button>
				<button type="button" id="myorder" class="btn btn-warning ttip" data-bs-toggle="modal" data-bs-target="#myModal" title="내 주문 기록">
					<span><i class="bi bi-cart4"></i></span>
				</button>
				<button id="print" class="btn btn-secondary ttip" aria-label="Print" data-bs-toggle="tooltip" data-bs-placement="top" title="주문서 출력">
					<span><i class="bi bi-printer"></i></span>
				</button>
			</div>
			<p class="text-danger" style="margin: 10px 0 10px;">다시 주문하시면 주문이 수정됩니다.</p>
		</div>
		<!-- Modal -->
		<div class="modal" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="myModalLabel">내 주문 목록</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body"></div>
					<div class="modal-footer">
						<span id="guide">입력 선택 후 다시 주문하시면 주문이 수정됩니다.</span>
						<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">닫기</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	</body>

<?php
include_once APPPATH . 'views/_common/footer.php';
