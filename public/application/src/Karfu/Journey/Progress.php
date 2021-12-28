<?php

namespace Application\Karfu\Journey;

/**
 * Progress helper class for setting, getting & killing progress session
 */
class Progress
{
    /**
     * Set the progress session
     * 
     * @param $data
     * 
     * @return void
     */
    public function setProgress($data)
    {
        session_start();
        $_SESSION['KARFU_user']['progress'] = $data;
    }

    /**
     * Kill progress session
     * 
     * @return void
     */
    public function killProgress()
    {
        unset($_SESSION['KARFU_user']['progress']);
    }

    /**
     * Get the progress from session
     * 
     * @return void
     */
    public function getProgress()
    {
        return (int) $_SESSION['KARFU_user']['progress'];
    }
}
