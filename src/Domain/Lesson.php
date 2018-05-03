<?php
namespace Phepub\Domain;

use Phepub\Domain\Image;
use Symfony\Component\Yaml\Yaml;
use Parsedown;

class Lesson {
	protected $id = null;
	protected $filename = null;
	protected $last_checked = null;
	protected $metadata = null;
	protected $lessonContent = null;


	public function __construct() {
	}

	protected function dirsize($dir) {
	    if(is_file($dir)) return array('size'=>filesize($dir),'howmany'=>0);
	    if($dh=opendir($dir)) {
	        $size=0;
	        $n = 0;
	        while(($file=readdir($dh))!==false) {
	            if($file=='.' || $file=='..') continue;
	            $n++;
	            $data = $this->dirsize($dir.'/'.$file);
	            $size += $data['size'];
	            $n += $data['howmany'];
	        }
	        closedir($dh);
	        $fsizebyte = $size;
			if ($fsizebyte < 1024) {
		        $fsize = $fsizebyte." bytes";
		    }elseif (($fsizebyte >= 1024) && ($fsizebyte < 1048576)) {
		        $fsize = round(($fsizebyte/1024), 2);
		        $fsize = $fsize." KB";
		    }elseif (($fsizebyte >= 1048576) && ($fsizebyte < 1073741824)) {
		        $fsize = round(($fsizebyte/1048576), 2);
		        $fsize = $fsize." MB";
		    }elseif ($fsizebyte >= 1073741824) {
		        $fsize = round(($fsizebyte/1073741824), 2);
		        $fsize = $fsize." GB";
		    };


	        return array('size'=>$fsize,'howmany'=>$n);
	    } 
	    return array('size'=>0,'howmany'=>0);
	}

	public function setFilename(String $filename) {
		$this->filename = $filename;
	}

	public function getFilename() {
		return $this->filename;
	}

	public function getSize() {
		return $this->dirsize($this->getImagePath())["size"];
		return "120";
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

		$mon_html = "<div  class='lesson_title'><h1>".$this->getTitle()."</h1></div>\n".$mon_html;
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