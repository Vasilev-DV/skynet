<?php
header('Content-Type: text/html; charset=utf-8');

$url = "http://sknt.ru/job/frontend/data.json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$output = curl_exec($ch);
$info = curl_getinfo($ch);

/**
*  Декодируем полученные данные в PHP массив
*/
$datas = json_decode($output);

curl_close($ch);

/**
*  Если нет ошибок подключения, работаем с данными
*/
$error_message = '';
if($info['http_code']!=200){
	$error_message = '<p>Произошла ошибка. Попробуйте проверить адрес или свой код.</p>';
	return false;
}

/**
*  Если нет ошибок, то проходимся по полученному массиву
*/
$tarifs_list = [];
if($datas->result=='ok'){
	
	/**
	*  Если есть список тарифов
	*/
	if(is_array($datas->tarifs) && count($datas->tarifs)>0){
		$tarifs = $datas->tarifs;
		$index = 0;
		foreach($tarifs as $key => $values){
			$more = '';
			$text = '';
			$title_main = '';
			$speed = 0;
			
			/**
			*  Перебор подтарифов тарифа
			*/
			if(is_array($values->tarifs) && count($values->tarifs)>0){
				$sub_tarifs = $values->tarifs;
				$title_main = $values->title;
				$link = $values->link;
				$speed = $values->speed;
				
				/**
				*  Получаем минимальное и максимальное значение цены
				*/
				$prices_map = [];
				for($s=0; $s<count($sub_tarifs); $s++){
					$price = $sub_tarifs[$s]->price;
					$pay_period = intval($sub_tarifs[$s]->pay_period);
					$price_month = $price/$pay_period;
					array_push($prices_map,$price_month);
				}
				$prices = min($prices_map).' - '.max($prices_map);
				
				/**
				*  Если есть текст, то выводим его
				*/
				if(is_array($values->free_options) && count($values->free_options)>0){
					$text = '<div class="text">'.implode('<br>',$values->free_options).'</div>';
				}
				
				/**
				*  Если есть ссылка, то выводим её
				*/
				if(!empty($link)){
					$more = '<div class="more"><a target="_blank" href="'.($link).'">узнать подробнее на сайте www.sknt.ru</a></div>';
				}
			}
			array_push($tarifs_list,'<div data-id="'.$index.'" class="item">
					<div class="title">Тариф "'.$title_main.'"</div>
					<div class="body">
						<div class="speed">'.$speed.' Мбит/c</div>
						<div class="price">'.$prices.' ₽/мес</div>
						'.$text.'
					</div>
					'.$more.'
				</div>');
			$index++;
		}
	}
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="MobileOptimized" content="320" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta name="HandheldFriendly" content="True"/>
	<title>Тестовое задание SkyNet</title>
	<link type="text/css" rel="stylesheet" href="css/style.css?ver=1">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700&display=swap" rel="stylesheet">
</head>
<body>
	<div id="page">
		<?=$error_message?>
		<div id="tarifs_block">
			<div class="inner_block">
				<?
					/**
					*  Список тарифов
					*/
					if(count($tarifs_list)>0){
						echo '<div class="tarifs_list">';
						echo implode('',$tarifs_list);
						echo '</div>';
					}
				?>
				<div class="subtarifs_list"></div>
				<div class="tarif_selection">
					<div class="item" data-id="3">
						<div class="name">Выбор тарифа</div>
						<div class="block">
							<div class="title"></div>
							<div class="body">
								<div class="price"></div>
								<div class="text"></div>
								<div class="action"></div>
							</div>
							<div class="btn_block">
								<button>Выбрать</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script type="text/javascript">
		var load_json = <?=$output?>;
		$(document).ready(function(){
			var arr = load_json['tarifs'];
			/*
			*  Открытие вариантов выбранного тарифа по клику на блоке тарифа
			*/
			$('#tarifs_block .tarifs_list > .item').on('click',function(){
				var index = $('#tarifs_block .tarifs_list > .item').index(this);
				var tarifts = [];
				var t_name = '';
				var price_month = 0;
				
				/*
				*  Получаем цену за 1 месяц для сравнения скидки
				*/
				for(var m=0; m<arr[index]['tarifs'].length; m++){
					if(arr[index]['tarifs'][m]['pay_period']==1){
						price_month = arr[index]['tarifs'][m]['price'];
						break;
					}
				}
				
				/*
				*  Перебираем список вариантов тарифа
				*/
				for(var t=0; t<arr[index]['tarifs'].length; t++){
					var id = arr[index]['tarifs'][t]['ID'];
					t_name = arr[index]['title'];
					var pay_period = arr[index]['tarifs'][t]['pay_period'];
					var price = arr[index]['tarifs'][t]['price'];
					var full_price = price_month * pay_period;
					var discount_price = full_price - price;
					var pay_period_name = pay_period+' месяц';
					var discount = '';
					if(pay_period>1 && pay_period<5){
						pay_period_name = pay_period+' месяца';
						discount = ' <br>Скидка &mdash; '+discount_price+' ₽';
					}
					else if(pay_period>4){
						pay_period_name = pay_period+' месяцев';
						discount = ' <br>Скидка &mdash; '+discount_price+' ₽';
					}
					
					var price_month_this = price / pay_period;

					tarifts.push('<div class="block" data-sub_id="'+id+'"><div class="title">'+pay_period_name+'</div><div class="body"><div class="price">'+price_month_this+' ₽/мес</div><div class="text">Разовый платеж &mdash; '+price+' ₽'+discount+'</div></div></div>');
				}
				$('.subtarifs_list').html('<div class="item"><div class="name">Тариф "'+t_name+'"</div><div class="wrap">'+tarifts.join('')+'</div></div>');
				
				/*
				*  Показываем окно вариантов выбранного тарифа
				*/
				$('#tarifs_block .tarifs_list').hide();
				$('#tarifs_block .subtarifs_list').show();
				
				/*
				*  Возвращаем окно с тарифами по клику на заголовок
				*/
				$('#tarifs_block .subtarifs_list .item .name').on('click',function(){
					$('#tarifs_block .subtarifs_list').hide();
					$('#tarifs_block .tarifs_list').show();
				});
				
				/*
				*  Выбор варианта тарифа
				*/
				$('#tarifs_block .subtarifs_list .item .block').on('click',function(){
					var sub_id = parseInt($(this).data('sub_id'));
					$('#tarifs_block .subtarifs_list').hide();
					$('#tarifs_block .tarif_selection').show();
					for(var t=0; t<arr[index]['tarifs'].length; t++){
						
						/* 
						*  Если ID выбранного варианта совпадает с ID из массава, подставляем данные
						*/
						if(arr[index]['tarifs'][t]['ID']==sub_id){
							price = arr[index]['tarifs'][t]['price'];
							pay_period = arr[index]['tarifs'][t]['pay_period'];
							
							/*
							*  переформатируем дату
							*/
							var new_payday = arr[index]['tarifs'][t]['new_payday'].toString();
							var time_split = new_payday.split('+');
							var timestampInMilliSeconds = time_split[0]*1000;
							var date = new Date(timestampInMilliSeconds);
							var month = date.getMonth()+1;
							var day = date.getDate();
							var year = date.getFullYear();
							var d = day+'.'+(month<10?'0'+month:month)+'.'+year;
						}
					}
					
					/*
					*  Подставляем блок новыми параметрами выбранного тарифа
					*/
					var pay_period_name = pay_period+' месяц';
					var discount = '';
					if(pay_period>1 && pay_period<5){
						pay_period_name = pay_period+' месяца';
						discount = ' <br>Скидка &mdash; '+discount_price+' ₽';
					}
					else if(pay_period>4){
						pay_period_name = pay_period+' месяцев';
						discount = ' <br>Скидка &mdash; '+discount_price+' ₽';
					}
					var price_month_this = price / pay_period;
					$('#tarifs_block .tarif_selection .item .title').html('Тариф "'+t_name+'"');
					$('#tarifs_block .tarif_selection .item .price').html('<div class="price">Период оплаты - '+pay_period_name+' <br>'+price_month_this+' ₽/мес</div>');
					$('#tarifs_block .tarif_selection .item .text').html('<div class="text">разовый платеж - '+price+' ₽ <br>со счёта спишется - '+price+' ₽</div>');
					$('#tarifs_block .tarif_selection .item .body .action').html('<div class="action">вступит в силу - сегодня <br>активно до - '+d+'</div>');
					
					/*
					*  Возвращаем окно с вариантами тарифа по клику на заголовок
					*/
					$('#tarifs_block .tarif_selection .item .name').on('click',function(){
						$('#tarifs_block .tarif_selection').hide();
						$('#tarifs_block .subtarifs_list').show();
					});
				});
			});
		});
	</script>
</body>
</html>