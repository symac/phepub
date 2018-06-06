<?php
namespace Phepub\Domain;

use Phepub\Domain\Image;
use Symfony\Component\Yaml\Yaml;
use Parsedown;

use Rych\ByteSize\ByteSize;

class Lesson {
	protected $id = null;
	protected $filename = null;
	protected $last_checked = null;
	protected $metadata = null;
	protected $lessonContent = null;
	protected $published = true; // default to true, false only when published: false in the properties of the Markdown file
	protected $lang = null;

	public function __construct() {
	}

	public function setFilename(String $filename) {
		$this->filename = $filename;
	}

	public function getFilename() {
		return $this->filename;
	}

	public function getFilenameBase() {
		return basename($this->getFilename());
	}

	public function setLang(String $lang) {
		$this->lang = $lang;
	}

	public function getLang() {
		return $this->lang;
	}

	public function getEpubFilename() {
			return "ph_".basename($this->getFilename(), ".md").".epub";
	}

	public function getSize() {
		$bytesize = new \Rych\ByteSize\ByteSize;
		return $bytesize->format(filesize(__DIR__."/../../web/epub/".$this->getEpubFilename()));
	}

	public function getLessonSlug() {
		return preg_replace("#^.{2}/lessons/(.*).md$#", "$1", $this->filename);
	}

	public function getImagePath() {
		$imagePath = __DIR__."/../../cache/images/".$this->getLang()."/".$this->getLessonSlug();
		if (!is_dir($imagePath)) {
			print "Building $imagePath";
			mkdir($imagePath);
		}
		return $imagePath;
	}

	public function setId(Int $id) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	public function setLastChecked($last_checked) {
		$this->last_checked = $last_checked;
	}

	public function getLastChecked() {
		if ( (is_null($this->last_checked)) or ($this->last_checked == "") ) {
			return null;
		}
		return $this->last_checked;
	}

	public function getMarkdownLocalPath() {
		return __DIR__."/../../cache/md/".$this->getFilename();
	}

	public function getMarkdownContent() {
		if (is_null($this->getLastChecked())) {
			$content = $this->downloadFromGithub();
			$this->setLastChecked(date("Y-m-d H:i:s"));
		} else {
			$content = file_get_contents($this->getMarkdownLocalPath());
		}
		return $content;
	}

	protected function analyseMarkdown() {
		$markDown = $this->getMarkdownContent();

        $quote = function ($str) {
            return preg_quote($str, "~");
        };

        $regex = '~^('
            .implode('|', array_map($quote, array('---'))) # $matches[1] start separator
            ."){1}[\r\n|\n]*(.*?)[\r\n|\n]+("                       # $matches[2] between separators
            .implode('|', array_map($quote, array('---')))   # $matches[3] end separator
            ."){1}[\r\n|\n]*(.*)$~s";                               # $matches[4] document content

        if (preg_match($regex, $markDown, $matches) === 1) { // There is a Front matter
            $yaml = trim($matches[2]) !== '' ? Yaml::parse(trim($matches[2])) : null;
            $str = ltrim($matches[4]);
			$this->metadata = $yaml;
			$this->lessonContent = $str;
			return $yaml;
        }
	}
	public function getLessonContent() {
		if (is_null($this->lessonContent)) {
			$this->analyseMarkdown();
		}
		return $this->lessonContent;
	}

	public function getLessonMetadata() {
		if (is_null($this->metadata)) {
			$this->analyseMarkdown();
		}
		return $this->metadata;
	}

	public function getTitle() {
		$metadata = $this->getLessonMetadata();
		return $metadata["title"];
	}

	public function setPublished($published) {
		$this->published = $published;
	}

	public function getPublished() {
		return $this->published;
	}

	public function getHtml() {
		$markDown = $this->getLessonContent();

		// Managing includes
		preg_match_all("#{% include ([^ ]*) [^}]*}#", $markDown, $includes);
		foreach ($includes[0] as $id => $include) {
			if ($includes[1][$id] == "toc.html") {
				# On s'occupe pas de la TOC
				$markDown = str_replace($include, "", $markDown);
			} elseif ($includes[1][$id] == "figure.html") {
				$figureFile = preg_replace('#^.*filename="([^"]*)".*$#', "$1", $include);
				$image = new Image($this, $figureFile);
				$markDown = str_replace($include, "<img src='".$image->getLocalPath()."'/>", $markDown);
			}
		}

		// Managing top metadata
		$markDown = trim($markDown);
		// $mon_html = Markdown::defaultTransform($markDown);
		$parser = new ParseDown;
		$mon_html = $parser->text($markDown);

		$html_header = "<div  class='lesson_title'><h1>".$this->getTitle()."</h1>";
		if (!isset($this->getLessonMetadata()["authors"])) {
			print "Author not set in ".$this->getFilename()."<br/>";
		} else {
			$author_s = join(", ", $this->getLessonMetadata()["authors"]);
			$html_header .= "<ul><li class='md_author'>Author(s) : $author_s</li>";
		}

		$html_header .= "<li class='md_date'>Date : ".date("Y-m-d", $this->getLessonMetadata()["date"])."</li>";

		$url_original = "https://programminghistorian.org/".preg_replace("#\.md$#", "", $this->getFilename());
		$html_header .= "<li class='md_original_link'>Link : <a href='$url_original'>$url_original</a></li></ul>";
		$html_header .= "</div>";

		$mon_html = $html_header.$mon_html;

		return $mon_html;
	}

	public function downloadFromGithub() {
		$url = "https://raw.githubusercontent.com/programminghistorian/jekyll/gh-pages/".$this->getFilename();
		$content = file_get_contents($url);
		file_put_contents($this->getMarkdownLocalPath(), $content);
		return $content;
	}

	public function downloadAttachments() {
		$markDown = $this->getMarkdownContent();
		preg_match_all("#{% include ([^ ]*) [^}]*}#", $markDown, $includes);
		foreach ($includes[0] as $id => $include) {
			if ($includes[1][$id] == "toc.html") {
				# On s'occupe pas de la TOC
				$markDown = str_replace($include, "", $markDown);
			} elseif ($includes[1][$id] == "figure.html") {
				$figureFile = preg_replace('#^.*filename="([^"]*)".*$#', "$1", $include);
				$image = new Image($this, $figureFile);
				$image->download();
			}
		}
	}
}
