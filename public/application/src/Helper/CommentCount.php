<?php 

   namespace Application\Helper;
   use Database;
 
   class CommentCount {
    
      public function comment_count_string($collectionObject) {
            $res = $this->comment_count($collectionObject);
            $count =  $res ? $res : 0;
            return $count;
      }
    
      public function comment_count($collectionObject) {

         $db = \Loader::db();
         $sql = 'SELECT cnvMessagesTotal FROM Conversations WHERE cID=? ORDER BY cnvID DESC';
         $args = Array($collectionObject->cID);
         return $db->GetOne($sql, $args);
    
      }
    
   }