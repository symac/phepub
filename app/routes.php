<?php
	$app->get('/', "Phepub\Controller\HomeController::indexAction");
	$app->get('/download/{lessonCode}', "Phepub\Controller\HomeController::downloadAction")->bind("download")->assert('lessonCode', '^[\w\-\._/]+');;
	$app->get('/check-for-updates', "Phepub\Controller\UpdateController::checkNewLessonsAction")->bind("update-cache-lessons");
	$app->get('/rebuild-epub', "Phepub\Controller\UpdateController::lessonsNeedEpubAction")->bind("update-epub-lessons");
