<?php
namespace Phepub\Domain;

use Michelf\Markdown;
use Phepub\Domain\Image;
use Symfony\Component\Yaml\Yaml;

class Lesson {
	protected $app;

	protected $id = null;
	protected $filename = null;
	protected $last_checked = null;
	protected $metadata = null;

	public function __construct($app) {
		$this->app = $app;
	}

	public function loadByFileName(String $filename) {
		$stmt = $this->app["db"]->prepare("select * from phepub_lessons where filename like ?");
		$stmt->bindValue(1, $filename, "string");
		$stmt->execute();

		$row = $stmt->fetch();
		if ($row) {
			$this->buildFromDomain($row);
			return true;
		}
		return false;
	}

	public function buildFromDomain($row) {
		$this->setId($row["id"]);
		$this->setFilename($row["filename"]);
		$this->setLastChecked($row["last_checked"]);
	}

	public function setFilename(String $filename) {
		$this->filename = $filename;
	}

	public function getFilename() {
		return $this->filename;
	}

	public function getLessonSlug() {
		return preg_replace("#^lessons/(.*).md$#", "$1", $this->filename);
	}

	public function getImagePath() {
		$imagePath = __DIR__."/../../cache/images/".$this->getLessonSlug();
		if (!is_dir($imagePath)) {
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

	public function getMarkdown() {
		if (is_null($this->getLastChecked())) {
			$content = $this->downloadFromGithub();
			$this->setLastChecked(date("Y-m-d H:i:s"));
		} else {
			$content = file_get_contents($this->getMarkdownLocalPath());
		}
		return $content;
	}

	public function getMetadata() {
		if (!is_null($this->metadata)) {
			return $this->metadata;
		}
		$markDown = $this->getMarkdown();
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
			return $yaml;
        }

        print "Unable to parse metadata for this lesson";
        exit;
	}

	public function getTitle() {
		$metadata = $this->getMetadata();
		return $metadata["title"];
	}

	public function getHtml() {
		$markDown = $this->getMarkdown();

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
				# $markDown = str_replace($include, "FIGURRRRRRRRRRR", $markDown);				
			}
		}

		// Managing top metadata
		$mon_html = Markdown::defaultTransform($markDown);
		return $mon_html;
	}

	public function downloadFromGithub() {
		$url = "https://raw.githubusercontent.com/programminghistorian/jekyll/gh-pages/".$this->getFilename();
		$content = file_get_contents($url);
		file_put_contents($this->getMarkdownLocalPath(), $content);
		return $content;
	}

	public function downloadAttachments() {
		$markDown = $this->getMarkdown();
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

	public function save() {
		$lessonData = [
			"filename" => $this->getFilename(),
			"last_checked" => $this->getLastChecked()
		];
		if (!$this->getId()) {
			$this->app["db"]->insert("phepub_lessons", $lessonData);
		} else {
			$this->app["db"]->update("phepub_lessons", $lessonData, array("id" => $this->getId()));			
		}
	}
}