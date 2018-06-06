<?php
	$app->get('/', "Phepub\Controller\HomeController::indexAction")->bind("home");
	$app->get('/download/{lessonLang}/{lessonCode}', "Phepub\Controller\HomeController::downloadAction")->bind("download")->assert('lessonCode', '^[\w\-\._/]+');;
	$app->get('/check-for-updates', "Phepub\Controller\UpdateController::checkNewLessonsAction")->bind("update-cache-lessons");
	$app->get('/rebuild-epub/{lessonCode}', "Phepub\Controller\UpdateController::lessonsNeedEpubAction")->bind("update-epub-lessons")->value("lessonCode", null);
	$app->get('/merge-epub', "Phepub\Controller\UpdateController::mergeEpubFiles")->bind("admin-merge");
	$app->get('/admin-links', "Phepub\Controller\UpdateController::adminAction")->bind("admin-links");
	$app->get('/admin-download-attachments', "Phepub\Controller\UpdateController::adminDownloadAttachmentsAction")->bind("admin-download-attachments");
