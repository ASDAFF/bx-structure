<?php
return array(
	// отсюдова и начинается сбор данных
	'=keywords'=>'keyword0, keyword-next0',
	'=description'=>'description0',
	// это просто страница. Должна содержать массив.
	'item'=>array(
		// это должно перекрывать предыдущее значение
		'=keywords'=>'keywords1',
		// этот атрибут должен просто появиться
		'=title'=>'title1',
		// вот что должно получиться
		'@expected'=>array(
			'=keywords'=>'keywords1',
			'=description'=>'description0',
		)
	),
	// повторяемые слова надо удалять
	'item-repeat'=>array(
		'=keywords'=>'keywords1, keywords1',
		'@expected'=>array(
			'=keywords'=>'keyword0, keyword-next0, keywords1',
			'=description'=>'description0',
		)
	),
	// проверка спецсимволов
	'item-special'=>array(
		// слова должны прибавиться
		'+keywords'=>'keyword1',
		// удаление слова из набора
		'-keywords'=>' keyword-next0, keyword0 ',
		'@expected'=>array(
			'=keywords'=>'keyword1',
			'=description'=>'description0',
		)
	),
	// TODO отработка просто строк - какое значение приоритетно, как остальные отрабатывать
	'item-string'=>'string0',
	'item-special-string'=>'+special-string0',
);