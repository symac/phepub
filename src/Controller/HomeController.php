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
        error_reporting(E_ALL | E_STRICT);
        ini_set('error_reporting', E_ALL | E_STRICT);
        ini_set('display_errors', 1);

		$lessons = $app["db"]->fetchAll("select * from phepub_lessons limit 0,1"); # where last_checked is null");

        $book = new Book();
        $book->setTitle("My PH");
		foreach ($lessons as $row) {
			// On va télécharger le MD
			$lesson = new Lesson($app);
			$lesson->buildFromDomain($row);
            $book->addLesson($lesson);
		}
        $book->generateAsEpub();

        return "";



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