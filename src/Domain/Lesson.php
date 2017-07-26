<?php
namespace Phepub\Domain;

class Lesson {
	protected $app;

	protected $id = null;
	protected $filename = null;

	public function __construct($app) {
		$this->app = $app;
	}

	public function loadByFileName(String $filename) {
		$stmt = $this->app["db"]->prepare("select * from phepub_lessons where filename like ?");
		$stmt->bindValue(1, $filename, "string");
		$stmt->execute();

		$row = $stmt->fetch();
		if ($row) {
			$this->setId($row["id"]);
			$this->setFilename($row["filename"]);
			return true;
		}
		return false;
	}

	public function setFilename(String $filename) {
		$this->filename = $filename;
	}

	public function getFilename() {
		return $this->filename;
	}

	public function setId(Int $id) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	public function save() {
		if (!$this->getId()) {
			$this->app["db"]->insert("phepub_lessons", array("filename" => $this->getFilename()));
		} else {
			$this->app["db"]->update("phepub_lessons", array("filename" => $this->getFilename()), array("id" => $this->getId()));			
		}
	}
}