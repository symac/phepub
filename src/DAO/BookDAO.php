<?php

namespace Phepub\DAO;

use Phepub\Domain\Book;

class BookDAO extends DAO
{
    public function findRecent($number = 1) {
        $sql = "select * from ".T_BOOK." order by build_date desc limit 0,$number";
        $row = $this->getDb()->fetchAssoc($sql);

        if ($row)
            return $this->buildDomainObject($row);
        else
            return null;
    }

    // public function loadByFileName(String $filename) {
    //     $stmt = $this->app["db"]->prepare("select * from ".T_LESSON." where filename like ? and filename != 'lessons/retired%'");
    //     $stmt->bindValue(1, $filename, "string");
    //     $stmt->execute();

    //     $row = $stmt->fetch();
    //     if ($row) {
    //         $this->buildFromDomain($row);
    //         return true;
    //     }
    //     return false;
    // }

    public function buildFromDomain(array $row) {
        $book = new Book();
        $book->setId($row["id"]);
        $book->setBuildDate($row["build_date"]);
        return $lesson;
    }

    /**
     * Saves a Instance into the database.
     *
     * @param \Bibdix\Domain\Instance $instance The instance to save
     */
    public function save(Book $book) {
        $bookData = array(
            "build_date" => $lesson->getBuildDate(),
        );

        if ($book->getId()) {
            $this->getDb()->update(T_BOOK, $bookData, array('id' => $bookData->getId()));
        } else {
            // The article has never been saved : insert it
            $this->getDb()->insert(T_BOOK, $bookData);
            $id = $this->getDb()->lastInsertId();
            $book->setId($id);
        }
    }

    /**
     * Removes a instance from the database.
     *
     * @param integer $id The instance id.
     */
    // public function delete($id) {
    //     // Delete the instance
    //     $this->getDb()->delete(T_BOOK, array('id' => $id));
    // }

}