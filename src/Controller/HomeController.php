<?php

namespace Phepub\Controller;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Phepub\Domain\Book;
use Phepub\Domain\Image;
use Phepub\Domain\Lesson;


class HomeController {
	// Displaying homepage
	public function indexAction( Request $request, Application $app ) {
        $lessons = $app["dao.lesson"]->findAll();
        $lastBook = $app["dao.book"]->findRecent();

        return $app['twig']->render('index.html.twig', array(
            'lessons' => $lessons,
            'lastBook' => $lastBook
        ));
    }

		public function downloadAction( Request $request, Application $app, String $lessonCode) {
			$lesson = $app["dao.lesson"]->loadByFileName("lessons/".$lessonCode.".md");
			if (!$lesson) {
				return "Download error, sorry";
			}

			$app["db"]->insert("phepub_download",
				array(
					"id_lesson" => $lesson->getId(),
					"ip" => $request->getClientIp(),
					"user_agent" => $request->headers->get('User-Agent')
				)
			);

			$path = ROOT."/web/epub/".$lesson->getEpubFilename();
			return $app
				->sendFile($path)
				->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($path));
		}

    public function OLDDDDbuildEpubAction( Request $request, Application $app , String $lessonCode) {
        $start = time();
        error_reporting(E_ALL | E_STRICT);
        ini_set('error_reporting', E_ALL | E_STRICT);
        ini_set('display_errors', 1);

        if ($lessonCode == "all") {
            $lessons = $app["dao.lesson"]->findAll(); # where last_checked is null");
        } else {
            $lessons = [];
            $lesson = $app["dao.lesson"]->loadByFileName("lessons/".$lessonCode.".md");
            $lessonId = $lesson->getId();
            array_push($lessons, $lesson);
        }

        $book = new Book();
        $book->setTitle("Programming Historian");
				$i = 1;
				foreach ($lessons as $lesson) {
					print "$i - Adding ".$lesson->getFilename()."<br/>";
					$book->addLesson($lesson);
					$i++;
				}

        $epub = $book->generateAsEpub();


        if ($lessonCode == "all") {
            $filename_full = "epub/programminghistorian_".date("Ymd").".epub";
        } else {
            $filename_full = "epub/programminghistorian_".date("Ymd")."_lesson_".$lessonId.".epub";
        }

        print "Save as <a href='".$request->getBaseUrl()."/".$filename_full."'>$filename_full</a> [time : ".(time() - $start)."]\n";
        $epub->saveBook($filename_full);

        if ($lessonCode == "all") {
					// We save the information about the newly created ebook
					$app["dao.book"]->save($book);
				}


//        $zipData = $epub->sendBook("ExampleBook2");
        return "Generation ok";

// This is not really a part of the EPub class, but IF you have errors and want to know about them,
//  they would have been written to the output buffer, preventing the book from being sent.
//  This behaviour is desired as the book will then most likely be corrupt.
//  However you might want to dump the output to a log, this example section can do that:
/*
if (ob_get_contents() !== false && ob_get_contents() != '') {
    $f = fopen ('./log.txt', 'a') or die("Unable to open log.txt.");
    fwrite($f, "\r\n" . date("D, d M Y H:i:s T") . ": Error in " . __FILE__ . ": \r\n");
    fwrite($f, ob_get_contents() . "\r\n");
    fclose($f);
}
 * or just:
    $bufferData = ob_get_contents();
    ob_end_clean();
 */

// Save book as a file relative to your script (for local ePub generation)
// Notice that the extension .epub will be added by the script.
// The second parameter is a directory name which is '.' by default. Don't use trailing slash!
//$book->saveBook('epub-filename', '.');

// Send the book to the client. ".epub" will be appended if missing.

// After this point your script should call exit. If anything is written to the output,
// it'll be appended to the end of the book, causing the epub file to become corrupt.



	}
}
