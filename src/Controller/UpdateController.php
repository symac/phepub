<?php

namespace Phepub\Controller;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Phepub\Domain\Book;
use Phepub\Domain\Lesson;
use Phepub\Domain\Image;

use SimplePie;


class UpdateController {

	public function lessonsNeedEpubAction( Request $request, Application $app, String $code = null) {
		if ($code) {
			$lesson = $app["dao.lesson"]->loadByFileName("lessons/".$code.".md");
			$lessons = [$lesson];
		} else {
			$lessons = $app["dao.lesson"]->findAllLessonsNeedingEpub();
			// We only build 5 epubs at a time
			$lessons = array_slice($lessons, 0, 5);
		}
		if (sizeof($lessons) == 0) {
			return "No lesson to rebuild";
		}
		print "<ul>";
		foreach ($lessons as $lesson) {
			$start = time();
			$book = new Book();
			$book->setTitle("PH - ".$lesson->getTitle());
			$book->addLesson($lesson);
			$epub = $book->generateAsEpub();
			$filename_full = "epub/ph_".basename($lesson->getFilename(), ".md").".epub";
			$epub->saveBook($filename_full);

			print "<li>";
			print $lesson->getTitle()." => <a href='".$request->getBaseUrl()."/".$filename_full."'>$filename_full</a> [time : ".(time() - $start)."]<br/>\n";
			print "</li>";
			$app["db"]->update("phepub_lessons", array("epub_need_rebuild" => 0), array("id" => $lesson->getId()));
		}
		print "</ul>";
		return "";
	}

	public function checkNewLessonsAction( Request $request, Application $app ) {

		$feed = new SimplePie();
		$feed->set_feed_url("https://github.com/programminghistorian/jekyll/commits/gh-pages.atom");

		$feed->enable_cache(true);
		$feed->set_cache_location("/tmp");
		$feed->init();

		print "<h1>Chargement du fichier ATOM</h1>";
		// On enregistre tous les items de l'atom qu'on n'a pas encore dans la base
		$items = $feed->get_items();
		foreach ($items as $item)
		{
			$commit_id = $item->get_id();

			$sql = "select * from phepub_commits where id=?";
			$stmt = $app["db"]->prepare($sql);
			$stmt->execute(array($commit_id));
			$exists = $stmt->fetch();
			if(!$exists) {
	        	$updatedOn = new \DateTime();
			    $updatedOn->setTimestamp($item->get_date('U'));
	        	$app["db"]->insert("phepub_commits", array("id" => $commit_id, "date_updated" => $item->get_date("Y-m-d H:i:s")));

			}
		}

		print "<h1>Chargement des entrées</h1>";
		// On parcourt tous les éléments du plus ancien au plus récent
		$commits = $app["db"]->fetchAll("select * from phepub_commits where date_checked is null order by date_updated asc");
		foreach ($commits as $commit) {
			$commit_id = $commit["id"];
			$basic_commit_id = preg_replace("#^.*Commit/(.*)#", "$1", $commit_id);
			print "<h2>".$commit_id." => ".$basic_commit_id."</h2>";

			// On va télécharger la page
			$dom = new \DOMDocument;
			libxml_use_internal_errors(true);
			$url = "https://github.com/programminghistorian/jekyll/commit/".$basic_commit_id;
			print "Chargement $url<br/>";
			$htmlContent = file_get_contents($url);

			$dom->loadHTML($htmlContent);
			libxml_use_internal_errors(false);
			$xpath = new \DOMXPath($dom);
			$nodes = $xpath->query('//div[@id="toc"]/ol/li/a');
			foreach($nodes as $href) {
				$filename = $href->nodeValue;
				if (preg_match("#^lessons/#", $filename)) {
					# On regarde le cas où la leçon est renommée
					if (preg_match("# → #", $filename)) {
						print "Leçon renommée, à traiter a posteriori";
					} else {
						$lesson_exist = $app["dao.lesson"]->loadByFileName($filename);
						if ($lesson_exist) {
							// On la passe à null pour qu'elle soit de nouveau téléchargée
							$app["db"]->update("phepub_lessons", array("last_checked" => null), array("id" => $lesson_exist->getId()));
						} else {
							print "SAVE";
							$lesson = new Lesson($app);
							$lesson->setFilename($filename);
							$app["dao.lesson"]->save($lesson);
						}
					}
				}

			    echo "&nbsp;&nbsp;&nbsp;&nbsp;".$href->nodeValue."<br/>";                       // echo current attribute value
			}
			$now = new \DateTime("now");
			$now = $now->format('Y-m-d H:i:s');

			$app["db"]->update("phepub_commits", array("date_checked" => $now), array("id" => $commit_id));
			sleep(0.1);
		}

		print "<h1>Analyse des leçons</h1>";
		$lessons = $app["dao.lesson"]->findAllLessonsNeedingUpdate();

		foreach ($lessons as $lesson) {
			// On va télécharger le MD
			print $lesson->getFilename()."<br/>";
			$lesson->setLastChecked(null);
			$lesson->downloadFromGithub();
			$lesson->downloadAttachments();

			// We need to check if the lesson has been published
			if ( (isset($lesson->getLessonMetadata()["published"])) and (!$lesson->getLessonMetadata()["published"]) ) {
				$lesson->setPublished(false);
			}

			$app["db"]->update("phepub_lessons", array("epub_need_rebuild" => 1), array("id" => $lesson->getId()));

			// We are going to rebuild the epub of $this
			$app["dao.lesson"]->save($lesson);
		}
		return "";
	}
}
