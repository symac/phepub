<?php

namespace Phepub\DAO;

use Phepub\Domain\Lesson;

class LessonDAO extends DAO
{
    public function findAllLessonsNeedingEpub() {
      return $this->findAll(false, true);
    }

    public function findAllLessonsNeedingUpdate() {
        return $this->findAll(true);
    }

    public function findAll($onlyNullUpdate = false, $onlyEpubUpdate = false) {
        $sql = "select * from ".T_LESSON." where published = 1";
        if ($onlyNullUpdate) {
          $sql .= " AND last_checked is null";
        } elseif ($onlyEpubUpdate) {
          $sql .= " AND epub_need_rebuild = 1 order by last_checked asc";
        }

        $result = $this->getDb()->fetchAll($sql);

        // Convert query result to an array of domain objects
        $lessons = array();
        foreach ($result as $row) {
            $lessonId = $row['id'];
            $lessons[$lessonId] = $this->buildFromDomain($row);
        }
        return $lessons;
    }

    public function loadByFileName(String $filename) {
        $stmt = $this->getDb()->prepare("select * from ".T_LESSON." where filename like ? and filename != 'lessons/retired%'");
        $stmt->bindValue(1, $filename, "string");
        $stmt->execute();

        $row = $stmt->fetch();
        if ($row) {
            return $this->buildFromDomain($row);
        }
        return false;
    }

    public function buildFromDomain(array $row) {
        $lesson = new Lesson();
        $lesson->setId($row["id"]);
        $lesson->setFilename($row["filename"]);
        $lesson->setLang($row["lang"]);
        $lesson->setLastChecked($row["last_checked"]);
        $lesson->setPublished($row["published"]);
        return $lesson;
    }

    /**
     * Saves a Instance into the database.
     *
     * @param \Bibdix\Domain\Instance $instance The instance to save
     */
    public function save(Lesson $lesson) {
        $lessonData = array(
            "filename" => $lesson->getFilename(),
            "lang" => $lesson->getLang(),
            "last_checked" => $lesson->getLastChecked(),
            "published" => $lesson->getPublished()
        );

        print "Save as ".$lesson->getLastChecked()."<br/>";

        if ($lesson->getId()) {
            $this->getDb()->update(T_LESSON, $lessonData, array('id' => $lesson->getId()));
        } else {
            // The article has never been saved : insert it
            $this->getDb()->insert(T_LESSON, $lessonData);
            $id = $this->getDb()->lastInsertId();
            $lesson->setId($id);
        }
    }

    /**
     * Removes a instance from the database.
     *
     * @param integer $id The instance id.
     */
    public function delete($id) {
        // Delete the instance
        print "TODO";
        return "";
        $this->getDb()->delete('bibdix_instance', array('id' => $id));
    }
}
