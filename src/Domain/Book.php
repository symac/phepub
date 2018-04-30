<?php
namespace Phepub\Domain;

use PHPePub\Core\EPub;
use PHPePub\Core\EPubChapterSplitter;
use PHPePub\Core\Logger;
use PHPePub\Core\Structure\OPF\DublinCore;
use PHPePub\Helpers\CalibreHelper;
use PHPePub\Helpers\URLHelper;
use PHPZip\Zip\File\Zip;

use Phepub\Domain\Lesson;

class Book {
	protected $title = "Programming historian - ePub edition";
	protected $lessons = array();
	protected $filename;

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getFilename() {
		return $this->filename;
	}
	
	public function setFilename($filename) {
		$this->filename = $filename;
	}

	public function addLesson(Lesson $lesson) {
		array_push($this->lessons, $lesson);
	}

	public function generateAsEpub() {
		$epub = new EPub(); // Default is EPub::BOOK_VERSION_EPUB2
		$fileDir = './PHPePub';
		$content_start =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
		. "<head>"
		. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
		. "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/styles.css\" />\n"
		. "<title>Test Book</title>\n"
		. "</head>\n"
		. "<body>\n";

		$book_end = "</body>\n</html>\n";


		$cssData = file_get_contents(ROOT."web/assets/blitz.css");
		$epub->addCSSFile("styles/styles.css", "css1", $cssData);

		$epub->setTitle($this->title);
		$epub->setIdentifier("981447ba-200d-40ff-825c-1a7a7520b7d6", EPub::IDENTIFIER_URI); // Could also be the ISBN number, preferred for published books, or a UUID.

		$chapterCount = 1;

		foreach ($this->lessons as $lesson) {
			$chapterContent = $content_start.$lesson->getHtml().$book_end;
			$chapterFilename = "chapter".sprintf("%03d", $chapterCount).".html";
			$epub->addChapter($lesson->getTitle(), $chapterFilename, $chapterContent, true, EPub::EXTERNAL_REF_ADD);
			$chapterCount++;
		}
		$epub->buildTOC();

		date_default_timezone_set('Europe/Paris');
		$epub->finalize(); // Finalize the book, and build the archive.
		return $epub;

		########################################################
		## Details génération epub en PHP ##
		# https://github.com/soderlind/read-offline/blob/dd8881c6451eba53658116fb98ff5e9025dcee91/vendor/grandt/phpepub/tests/EPub.Example2b.php

		// $book->setLanguage("en"); // Not needed, but included for the example, Language is mandatory, but EPub defaults to "en". Use RFC3066 Language codes, such as "en", "da", "fr" etc.
		// $book->setDescription("This is a brief description\nA test ePub book as an example of building a book in PHP");
		// $book->setAuthor("John Doe Johnson", "Johnson, John Doe");
		// $book->setPublisher("John and Jane Doe Publications", "http://JohnJaneDoePublications.com/"); // I hope this is a non existent address :)
		// $book->setDate(time()); // Strictly not needed as the book date defaults to time().
		// $book->setRights("Copyright and licence information specific for the book."); // As this is generated, this _could_ contain the name or licence information of the user who purchased the book, if needed. If this is used that way, the identifier must also be made unique for the book.
		// $book->setSourceURL("http://JohnJaneDoePublications.com/books/TestBook.html");

		// $book->addDublinCoreMetadata(DublinCore::CONTRIBUTOR, "PHP");

		// $book->setSubject("Test book");
		// $book->setSubject("keywords");
		// $book->setSubject("Chapter levels");

		// // Insert custom meta data to the book, in this case, Calibre series index information.
		// CalibreHelper::setCalibreMetadata($book, "PHPePub Test books", "2");

		// This test requires you have an image, change "demo/cover-image.jpg" to match your location.
		//$book->setCoverImage("Cover.jpg", file_get_contents("demo/cover-image.jpg"), "image/jpeg");

		// A better way is to let EPub handle the image itself, as it may need resizing. Most e-books are only about 600x800
		//  pixels, adding mega-pixel images is a waste of place and spends bandwidth. setCoverImage can resize the image.
		//  When using this method, the given image path must be the absolute path from the servers Document root.

		/* $book->setCoverImage("/absolute/path/to/demo/cover-image.jpg"); */

		// setCoverImage can only be called once per book, but can be called at any point in the book creation.

		// $log->logLine("Set up parameters");

		// $cssData = file_get_contents(dirname(__FILE__)."/style.css");
		// $epub->addCSSFile("styles.css", "css1", $cssData);


		// $cover = $content_start . "<h1>Test Book</h1>\n<h2>By: John Doe Johnson</h2>\n" . $bookEnd;
		// $book->addChapter("Notices", "Cover.html", $cover);


	}

}

?>