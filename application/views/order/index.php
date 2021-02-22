<?php
include_once APPPATH . 'views/_common/header.php';
$option = '';
foreach ($data['menu'] as $key => $val) {
	$option .= <<<HTML
<option value="{$val['product_cd']}">{$val['product_nm']}</option>\n
HTML;
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
			width: 300px !important;
		}

		img {
			width: 100px;
			height: 100px;
		}

	</style>
	<script>
		$(function () {
			$.widget("custom.combobox", {
				_create: function () {
					this.wrapper = $("<span>")
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
							.text("▼")
							.tooltip()
							.appendTo(this.wrapper)
							.removeClass("ui-corner-all")
							.addClass("custom-combobox-toggle text-right btn btn-default")
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
					this.input
							.val("")
							.attr("title", value + " 일치하는 메뉴가 없습니다.")
							.tooltip("open");
					this.element.val("");
					this._delay(function () {
						this.input.tooltip("close").attr("title", "");
					}, 2500);
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
			$("#logout").click(function () {
				window.location.href = "/member/logout";
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

				alert(menu_code + '/' + menu_nm + '/' + size + '/' + cnt);

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
					},
					success: function (request) {
						console.log(request);
					},
					error: function (request, status, error) {
						console.log('code: ' + request.status + "\n" + 'message: ' + JSON.parse(request.responseText) + "\n" + 'error: ' + error);
					}
				});
			});

		});

	</script>
	</head>

	<div class="user_info">
		<div><button type="button" class="btn btn-success" id="logout">logout</button></div>
		<h5><?= $data['buyer'][0]['member_name'] . '님이 쏘십니다. "' . $data['buyer'][0]['comment'] . '"'?></h5>
		<h5><?= $data['user']['name'] . ' ' . $data['user']['pos'] . '님 환영 합니다. 메뉴를 선택해 주세요.' ?></h5>
	</div>
	<div class="form-inline">
		<div class="ui-widget form-group">
			<input type="hidden" id="code">
			<select id="combobox">
				<option value>메뉴를 선택하세요.</option>
				<?= $option ?>
			</select>
		</div>
		<div class="form-group">
			<select id="size" class="form-control" title="사이즈" data-original-title="사이즈">
				<option value="tall">Tall</option>
				<option value="grande">Grande</option>
				<option value="venti">Venti</option>
			</select>
		</div>
		<div class="form-group">
			<select id="cnt" class="form-control" title="수량" data-original-title="수량">
				<option value="1">1개</option>
				<option value="2">2개</option>
				<option value="3">3개</option>
				<option value="4">4개</option>
				<option value="5">5개</option>
			</select>
		</div>
		<div class="form-group">
			<input type="text" class="form-control" id="comment" placeholder="품절인 경우 대체 주문할 음료 입력">
		</div>
		<div class="form-group">
			<button id="order" class="btn btn-info">주문하기</button>
		</div>
	</div>
	<br>
	<div class="image"><img
				src="https://www.istarbucks.co.kr/upload/store/skuimg/2015/07/[106509]_20150724164325806.jpg"
				id="thumbnail"><span id="content"></span></div>
	</body>

<?php
include_once APPPATH . 'views/_common/footer.php';
