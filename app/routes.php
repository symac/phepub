<?php
	$app->get('/', "Phepub\Controller\HomeController::indexAction");
	$app->get('/check-for-updates', "Phepub\Controller\UpdateController::checkNewLessonsAction");
