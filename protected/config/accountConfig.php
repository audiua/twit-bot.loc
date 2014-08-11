<?php


return array(

	// время начала работы в часах
	'startDay' => array( 'min' => 8, 'max' => 12 ),

	// время конца работы в часах
	'endDay' => array( 'min' => 18, 'max' => 23 ),

	// максимальная разница фолловеров и анфолловеров для прекращения фолловинга
	'maxDiffUnfollowers' => 200,

	// количество анфолловеров за день в процентах от общего количества
	'unfollowOfDay' => array( 'min' => 5, 'max' => 8 ),

	// количество фолловеров за день в процентах от общего количества
	'followOfDay' => array( 'min' => 4, 'max' => 6 ),

	// количество фолловеров за раз
	'followOnce' => array( 'min' => 0, 'max' => 4 ),

	// количество анфолловеров за раз
	'unfollowOnce' => array( 'min' => 0, 'max' => 5 ),

	// количество запусков файлов фоллов и анфоллов в одном диапазане
	'countStartFlUn' => 2,

	// post API max 10 за 15 мин
	// запусков в диапазон

	// 30% - будет запускатся только в каждом третем участке времени 1раз
	'twit' => 30,

	// 25% - будет запускатся только в каждом четвертом участке времени 1раз
	'retweet' => 15,

	// 20% 
	'link_retweet' => 25,

	// один запуск на 15 мин, переделать на самоопределение запусков
	// запоминать последнюю запись в ленте
	// если второй раз ее не получаем - отстаем от ленты - увеличить количество запусков
	// после парсера запусается message
	// 'message' => parser * 2,
	'parser' => 2,

	// мксимальное количество фолловеров
	'maxFollow' => 99999,

	// нижняя граница свободных юзеров
	'minFreeUsers' => 1000,

	// региональность 23424976 - ua, 23424936 - ru
	'WOEID' => 23424936,

	// rss
	'rss' => array(
		'http://news.rambler.ru/rss/games/',
		'http://news.rambler.ru/rss/sport/',
		'http://news.rambler.ru/rss/auto/',
		'http://news.rambler.ru/rss/kino/',
		'http://news.rambler.ru/rss/music/',
		'http://news.rambler.ru/rss/travel/',
		'http://news.rambler.ru/rss/health/',
		'http://news.rambler.ru/rss/business/',
		'http://news.rambler.ru/rss/kids/',
		'http://news.rambler.ru/rss/starlife/',
		'http://news.rambler.ru/rss/starlife/',
		'http://news.rambler.ru/rss/fashion/',
		'http://news.rambler.ru/rss/house/',
		'http://news.rambler.ru/rss/scitech/'
	),

	// язык по умолчанию
	'lang' => 'ru',

	// время ожидания ответного фолловинга в днях
	'timeAnswerFollowing' => 5,

	// аккаунты с каких будем парсить ленту для получениятвитов с ссылками, которые будем вечно ретвитить
	'accountOfSite' => array( 'web_jewel', 'cyprus_jewel'  ),

	// через сколько удалять ретвиты с ссылками - в днях
	'deleteOldRetweet' => 3,

	// количество запусков обновления данных юзеров вдиапазон
	'updateUserData' => 3,

	// ответный фолловинг в день
	'backFollowOfDay' => array( 'min' => 10, 'max' => 20 ),

	// ответный фолловинг за раз
	'backFollowOnce' => array( 'min' => 0, 'max' => 3 ),

	// отправка личных сообщений новым фолловерам за раз
	'sendMassageNewFollowersOnce' =>array( 'min' => 0, 'max' => 5 ),


);