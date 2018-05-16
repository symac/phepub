<?php
	$app->get('/', "Phepub\Controller\HomeController::indexAction");
	$app->get('/build-epub/{lessonCode}', "Phepub\Controller\HomeController::buildEpubAction")->bind("generate")->value("lessonCode", "all")->assert('lessonCode', '^[\w\-\._/]+');;
	$app->get('/check-for-updates', "Phepub\Controller\UpdateController::checkNewLessonsAction")->bind("update-cache-lessons");
