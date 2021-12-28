<?php
namespace Application\Job;
use Job as AbstractJob;
use Concrete\Core\Page\PageList;

class RemoveVehicles extends \Concrete\Core\Job\QueueableJob {

    public function getJobName()
    {
        return t("Remove vehicles");
    }
    public function getJobDescription()
    {
        return t("Deletes all vehicles");
    }


    public function start(\ZendQueue\Queue $q)
    {
        $list = new \Concrete\Core\Page\PageList();
        $list->ignorePermissions();
        $list->filterByPageTypeHandle(['vehicle', 'Vehicle']);
        $results = $list->executeGetResults();
        foreach($results as $i => $queryRow) {
            $q->send($queryRow['cID']);
        }
    }

    public function processQueueItem(\ZendQueue\Message $msg)
    {
        $page = \Page::getByID($msg->body);
        $page->delete();
    }

    public function finish(\ZendQueue\Queue $q)
    {
        return t('Vehicles removed');
    }

}