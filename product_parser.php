<?php
$name = $data['heading_title'];
$url = end($data['breadcrumbs'])['href'];
$useNewTemplate = false;
$registry = new Registry();
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

if (!empty($data['options']))
{


	$arExecOption = [
		'use_template' => 13,
		'prices' => 15,
		'diplomes' => 21,
		'programm' => 18,
		'advertising' => 20,
		'advertising_slogan' => 14,
		'teachers' => 19,
		'teach_plan' => 17,
		'header_bg' => 16,
		'rules' => 23,
		'diplomes_text' => 24,
		'teachers_text' => 25,
		'five_step_text' => 26,
		'adv_title' => 27,
		'ours_webinars' => 29,
		'tasks' => 30,
		'sample_youtube' => 32,
		'naznachenie' => 31,
		'clients' => 33,
		'adv_link' => 37,
		'adv_title_sec' => 34,
		'adv_text' => 36,
		'adv_img' => 35
	];

	foreach ($data['options'] as $k => $option)
	{
		$tempKey = array_search($option['option_id'], $arExecOption);
		if ($tempKey !== false && $tempKey !== "prices" && $tempKey !== "diplomes")
		{
			if ($tempKey == "programm" || $tempKey == "teach_plan" || $tempKey == "rules")
			{
				$data['docs'][$option['option_id']] = $option;
			}
			else
			{
				$data[$tempKey][] = $option;
			}
		}

		$useSort = false;
		if ($tempKey == "diplomes")
		{
			
			if (strstr($option['value'], "@") !== false)
			{
				$parts = explode("@", $option['value']);
				$option['value'] = $parts[0];
				$data[$tempKey][$parts[1]] = $option;
				$useSort = true;
			}
			else
			{
				$data[$tempKey][] = $option;
			}

		}

		/*отсортируем и обработаем блоки цен*/
		if ($tempKey == "prices")
		{
			$getX = mb_strpos($option['value'], 'promo-block-item-pnew&quot;&gt;');
			$takePrice = preg_replace("/[^0-9]/", '', mb_substr($option['value'], $getX, 65));

			$newPrice = str_replace('&lt;div class=&quot;position-knopka-zv-red btn btn-lg btn-main-zv-yell-dpo&quot; data-toggle=&quot;modal&quot; data-target=&quot;#recall_me_2&quot;&gt;&lt;p&gt;Узнать детали&lt;/p&gt;
							&lt;/div&gt;', '', $option['value']);

			$query = $db->query("SELECT ID FROM " . DB_PREFIX . "cpa_products WHERE NAME = '". $name ."' AND PRICE = '".$takePrice."'");
			if (!empty($query->rows))
			{
				$option['value'] = str_replace('&lt;div class=&quot;position-knopka-zv-red btn btn-lg btn-main-zv-yell-dpo&quot; data-toggle=&quot;modal&quot; data-target=&quot;#recall_me_2&quot;&gt;' , '<div class="position-knopka-zv-red btn btn-lg btn-main-zv-yell-dpo" data-toggle="modal" data-target="#recall_me_2" data-pid="'.$query->row['ID'].'">', $option['value']);
				//$db->query("INSERT INTO " . DB_PREFIX . "cpa_products SET NAME = '". $name ."', PRICE = '". $takePrice ."', URL = '". $url ."'");
			}

			$data[$tempKey][$takePrice] = $option;

			/*и тут же соберем массив для нижнего блока цен*/
			
			
			$data['bottom_prices'][$takePrice] = $newPrice;
		}
	}

	$data['id'] = $this->request->get['product_id'];

	ksort($data['prices']);
	ksort($data['diplomes']);
	ksort($data['docs']);
	ksort($data['bottom_prices']);
}

$data['description_coaches'] = '';
$x = mb_strpos($data['description'], '<div class="prepodavateli-dpo">');
if (is_int($x)) {
	$y = mb_strpos($data['description'], '<div class="clearr"></div>', $x);
	if ($y) {
		$data['description_coaches'] = '<div class="zag-content-dpo-prog"><h2>Преподаватели</h2></div>'.trim(mb_substr($data['description'], $x, $y - $x));
	}
}

$data['description_docs'] = array();
$x = explode('<li><i class="fa fa-file-text" aria-hidden="true"></i>', $data['description']);
if ($x) {
	unset($x[0]);
	foreach($x as $y) {
		$y = trim(mb_substr($y, 0, mb_strpos($y, '</li>')));
		if ($y) {
			$data['description_docs'][] = $y;
		}
	}
}

$data['description_diploms'] = array();
$x = explode('<div class="col-lg-3 col-md-3 col-sm-6 col-xs-6 dopstylecontdpo">', $data['description']);
if (sizeof($x) > 1) {
	unset($x[0]);
	foreach($x as $y) {
		$y = trim(mb_substr($y, 0, mb_strpos($y, '</div>')));
		if ($y) {
			$data['description_diploms'][] = $y;
		}
	}
}

$data['description_offers'] = array();
$x = explode('<div class="mba-border">', $data['description']);
if (sizeof($x) > 1) {
	unset($x[0]);
	foreach($x as $y) {
		$y = trim(mb_substr($y, 0, mb_strpos($y, '</div>')));
		if ($y) {
			$q = array();
			$z = mb_strpos($y, '<p class="mba-text-main zayvkadpodop">');
			if (is_int($z)) {
				$z += 38;
				$q['txt'] = trim(mb_substr($y, $z, mb_strpos($y, '</p>', $z) - $z));
			}
			$z = mb_strpos($y, '<p class="mba-text-ext">');
			if (is_int($z)) {
				$z += 24;
				$q['txt_plus'] = trim(mb_substr($y, $z, mb_strpos($y, '</p>', $z) - $z));
			}
			$z = mb_strpos($y, '<p class="actualprice">');
			if (is_int($z)) {
				$z += 23;
				$q['price'] = trim(mb_substr($y, $z, mb_strpos($y, '</p>', $z) - $z));

				$p = preg_replace("/[^0-9]/", '', $q['price']);
				/*$query = $db->query("SELECT NAME FROM " . DB_PREFIX . "cpa_products WHERE NAME = '". $name ."' AND PRICE = '".$p."'");
				if (empty($query->rows))
				{
					$db->query("INSERT INTO " . DB_PREFIX . "cpa_products SET NAME = '". $name ."', PRICE = '". $p ."', URL = '". $url ."'");
				}*/
			}
			$query = $db->query("SELECT ID FROM " . DB_PREFIX . "cpa_products WHERE NAME = '". $name ."' AND PRICE = '".$p."'");
			if (!empty($query->rows))
			{
				$q['pid'] = $query->row['ID'];
			}

			$z = mb_strpos($y, '<p class="oldprice">');
			if (is_int($z)) {
				$z += 20;
				$q['price_old'] = trim(mb_substr($y, $z, mb_strpos($y, '</p>', $z) - $z));
			}
			if (/*$q['txt'] && */$q['price'] && $q['price_old']) {
				$z = md5($q['txt'].' |pr:'.$q['price'].' |pro:'.$q['price_old']);
				if (!isset($data['description_offers'][$z])) {
					$data['description_offers'][$z] = $q;
				}
			}
		}
	}
}
//echo '<!--:: ';print_r($data['description_offers']);print_r($x);echo ' -->';
if ($data['description_offers']) {
	$data['description_offers'] = array_values($data['description_offers']);
}

$data['category1_name'] = '';
$data['advantages_header'] = 'Наши преимущества';
$data['step5_txt'] = 'Подготавливаем диплом о профессиональной переподготовке и высылаем вам Почтой России. Отправляем трек-номер для отслеживания посылки.';
$data['consult_txt'] = 'Программа профессиональной переподготовки рассчитана на 512 ч. и 1024 ч. Благодаря дистанционным технологиям <b>интенсивность обучения</b> студенты <b>выбирают сами</b> согласно своим предпочтениям. При Вашем желании длительность курса может быть экстерном <b>СОКРАЩЕНА В 2 РАЗА!</b> Подробности уточняйте по телефону на сайте или отправьте нам заявку для консультации.';
$data['diploms_txt'] = 'Согласно 273-ФЗ «Об Образовании в РФ» на основании п.8 статьи 108, студентам, успешно окончившим программы дополнительного образования профпереподготовки, выдаётся диплом установленного образца, приравниваемый по статусу ко второму высшему образованию.';
if (isset($this->request->get['path'])) {
	$_path = '';

	$_parts = explode('_', (string)$this->request->get['path']);

	$_category_id = (int)array_shift($_parts);

	$_category_info = $this->model_catalog_category->getCategory($_category_id);

	if ($_category_info) {
		$data['category1_name'] = trim(mb_strtolower($_category_info['name']));

		if ($data['category1_name'] === 'профессиональная переподготовка') {
			$data['advantages_header'] = 'Преимущества профессиональной<br> переподготовки';

			$initFlag = mb_strpos($data['description'], '<div class="content-flag">');
			if (!is_int($initFlag)) {
			
				if (sizeof($data['description_offers']) < 3) {
					array_unshift($data['description_offers'], array());
				}

				$data['description_offers'][0]['name'] = 'Нужен диплом и минимум усилий';
				$data['description_offers'][0]['txt'] = '<div>Объем <b>256 часов</b></div>
			        				<div>Длительность обучения <b>2 месяца</b></div>';
				if (!$data['description_offers'][0]['price'] && !$data['description_offers'][0]['price_old']) {
					$data['description_offers'][0]['price'] = '9 900 ₽';

	                                $data['description_offers'][0]['price_old'] = ' ';

				}

				if ($data['description_offers'][0]['price'] && mb_substr($data['description_offers'][0]['price'], 0, 3) != 'от ') {
					$data['description_offers'][0]['price'] = 'от '.$data['description_offers'][0]['price'];
				}

				if (sizeof($data['description_offers']) > 2) {
					$data['description_offers'][1]['name'] = 'Оптимальное обучение для работы';
					$data['description_offers'][1]['txt'] = '<div>Объем <b>512 часов</b></div>
			        				<div>Длительность обучения <br><b>5 месяцев</b> или <b>2,5</b> экстерном</div>';
					if (!$data['description_offers'][1]['txt_plus']) {
						$data['description_offers'][1]['txt_plus'] = '+ диплом mini MBA';
					}
					if (!$data['description_offers'][1]['price'] && !$data['description_offers'][0]['price_old']) {
						$data['description_offers'][1]['price'] = '35 900 ₽';
						$data['description_offers'][1]['price_old'] = '45 900 ₽';
					}
					$_key = 2;
				}
				else {
					$_key = 1;
				}

				$data['description_offers'][$_key]['name'] = 'Продвинутое обучение и максимум навыков';
				$data['description_offers'][$_key]['txt'] = '<div>Объем <b>1024 часов</b></div>
			        				<div>Длительность обучения<br><b>10 месяцев</b> или <b>5</b> экстерном</div>';
				if (!$data['description_offers'][$_key]['txt_plus']) {
					$data['description_offers'][$_key]['txt_plus'] = '+ диплом mini MBA<br>
			        				+ очные курсы в ИПО<br>
			        				+ 1 доп. курс на выбор';
				}
				if (!$data['description_offers'][$_key]['price'] && !$data['description_offers'][$_key]['price_old']) {
					$data['description_offers'][$_key]['price'] = '53 900 ₽';
					$data['description_offers'][$_key]['price_old'] = '63 900 ₽';
				}
			}
		}
		elseif ($data['category1_name'] === 'повышение квалификации дистанционно') {
			$data['category1_name'] = 'повышение квалификации';
			$data['advantages_header'] = 'Преимущества повышения<br> квалификации';
			$data['step5_txt'] = 'Подготавливаем удостоверение о повышении квалификации и высылаем вам Почтой России. Отправляем трек-номер для отслеживания посылки.';
			$data['consult_txt'] = 'Курсы повышения квалификации рассчитаны на 72 ч. Благодаря дистанционным технологиям <b>интенсивность обучения</b> студенты <b>выбирают сами</b> согласно своим предпочтениям. При Вашем желании длительность курса может быть экстерном <b>СОКРАЩЕНА В 2 РАЗА!</b> Подробности уточняйте по телефону на сайте или отправьте нам заявку для консультации.';
			$data['diploms_txt'] = '';
		}
	}
}
?>
