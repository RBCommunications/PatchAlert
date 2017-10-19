<?php

/**
 * Observer.
 * 
 * @package  RBCommunications_PatchAlert
 * @author   Nate Cornell <natec@rb-com.com>
 */
class RBCommunications_PatchAlert_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Checks for any new (last 24 hours) patches that have not yet been
     * applied (do not appear in applied.patches.list).
     *
     * @param Varien_Event_Observer $observer  The observer (optional)
     *
     * @return boolean
     */
    public static function checkForNewPatch($observer=null) {

        if (Mage::getStoreConfig('patchalert/config/enabled') != 1) {
            return true;
        }

        Mage::log('Checking for new patches');

        $searcher = new RBCommunications_PatchAlert_Model_Searcher();
        $patches  = $searcher->findNewUnappliedPatches();

        if (!is_array($patches) || count($patches) < 1) { return true; }

        $alerter = new RBCommunications_PatchAlert_Model_Alerter();

        return $alerter->emailPatchAlert($patches, true);

    }

    /**
     * Checks for any outstanding patches that have not yet been applied 
     * (do not appear in applied.patches.list).
     *
     * @return boolean
     */
    public static function checkForUnappliedPatches($observer=null) {

        if (Mage::getStoreConfig('patchalert/config/enabled') != 1) {
            return true;
        }

        Mage::log('Checking for unapplied patches');

        $searcher = new RBCommunications_PatchAlert_Model_Searcher();
        $patches  = $searcher->findUnappliedPatches();

        if (! is_array($patches) || count($patches) < 1) { return true; }

        $alerter = new RBCommunications_PatchAlert_Model_Alerter();

        return $alerter->emailPatchAlert($patches, false);

    }

}
