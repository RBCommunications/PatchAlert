<?php

/**
 * Patch object.
 * Holds date, title, and message.
 * 
 * @package  RBCommunications_PatchAlert
 * @author   Nate Cornell <natec@rb-com.com>
 */
class RBCommunications_PatchAlert_Model_Patch extends Mage_Core_Model_Abstract
{
    var $patchID;
    var $createdDate;
    var $title;
    var $details;

    /**
     * Constructor
     *
     * @param string $patchID      The "SUPEE-XXXX" patch id
     * @param string $createdDate  The date of the notification.
     * @param string $title        The title of the notification.
     * @param string $details      The notification message.
     */
    public function __construct($patchID, $createdDate, $title, $details) {

        $this->patchID     = $patchID;
        $this->createdDate = $createdDate;
        $this->title       = $title;
        $this->details     = $details;

    }

    /**
     * Returns this objects attributes as an associative array.
     *
     * @return array
     */
    public function toArray() {

        return [
            'patchID'     => $this->patchID,
            'createdDate' => $this->createdDate,
            'title'       => $this->title,
            'details'     => $this->details
        ];

    }

}
