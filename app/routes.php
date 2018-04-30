<?php
	$app->get('/', "Phepub\Controller\HomeController::indexAction");
	$app->get('/build-epub', "Phepub\Controller\HomeController::buildEpubAction")->bind("generate");
	$app->get('/check-for-updates', "Phepub\Controller\UpdateController::checkNewLessonsAction");
