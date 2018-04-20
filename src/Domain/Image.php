<?php
namespace Phepub\Domain;

use Phepub\Domain\Lesson;

class Image {

	private $lesson;

	private $localFilePath;

	private $filename;

	public function __construct(Lesson $lesson, String $filename) {
		$this->lesson = $lesson;
		$this->filename = $filename;
		# On télécharge l'image si on ne l'a pas déjà
	}

	public function download() {
		if (!file_exists($this->lesson->getImagePath()."/".$this->filename)) {
			file_put_contents($this->getLocalPath(), file_get_contents("https://github.com/programminghistorian/jekyll/raw/gh-pages/images/".$this->lesson->getLessonSlug()."/".$this->filename));
		}
	}

	public function getLocalPath() {
		return $this->lesson->getImagePath()."/".$this->filename;
	}
}

?>