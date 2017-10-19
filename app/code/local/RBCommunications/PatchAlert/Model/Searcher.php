<?php

/**
 * Patch Searcher.
 * Finds patches in notification messages.
 * 
 * @package  RBCommunications_PatchAlert
 * @author   Nate Cornell <natec@rb-com.com>
 */
class RBCommunications_PatchAlert_Model_Searcher extends Mage_Core_Model_Abstract
{

    const UTC = 'UTC';
    const PST = 'America/Los_Angeles';
    const DEFAULT_NEW_INTERVAL = 'PT36H';

    /**
     * Returns an array of new patches that are not yet applied to this
     * instance.
     * Assumes that the check runs once a day, so checks the last 24 hours
     * unless an alternate DateInterval is specified.
     *
     * @param DateInterval $interval  The interval from now to the start of
     *                                the query range. Defaults to 36 hrs.
     *
     * @return array  The patch objects for the unapplied patches.
     */
    public function findNewUnappliedPatches($interval) {

        if (!$interval || get_class($interval) != 'DateInterval') {
            $interval = new DateInterval(self::DEFAULT_NEW_INTERVAL);
        }

        $startDate = new DateTime('now', new DateTimeZone(self::PST));
        $startDate->setTimezone(new DateTimeZone(self::UTC));
        $startDate->sub($interval);

        $endDate = new DateTime('now', new DateTimeZone(self::PST));
        $endDate->setTimezone(new DateTimeZone(self::UTC));

        return $this->findUnappliedPatches($startDate, $endDate);

    }

    /**
     * Returns an array of unnapplied patches.
     * If no start date defined, defaults to the sites launch date.
     *
     * @param DateTime $startDate  The start of the query range. Defaults
     *                             to the site launch date.
     * @param DateTime $endDate    The end of the query range. Defaults
     *                             to 24hrs earlier.
     *
     * @return array  The patch objects for the unapplied patches.
     */
    public function findUnappliedPatches($startDate = null, $endDate = null) {

        if (!$startDate || get_class($startDate) != 'DateTime') {
            $startDate = Mage::helper('patchalert')->getSiteLaunchDate();
        }

        if (!$endDate || get_class($endDate) != 'DateTime') {
            # Exclude any that the daily check might find:
            $endDate = new DateTime('now', new DateTimeZone(self::PST));
            $endDate->setTimezone(new DateTimeZone(self::UTC));
            $endDate->sub(new DateInterval(self::DEFAULT_NEW_INTERVAL));
        }

        $notifications = $this->findPatches($startDate, $endDate);

        if (!is_array($notifications) || count($notifications) < 1) {
            return;
        }

        $unappliedPatches = $this->getUnapplied($notifications);

        if (!is_array($unappliedPatches) || count($unappliedPatches) < 1) {
            return;
        }

        return $unappliedPatches;

    }

    /**
     * Finds patches in notification messages.
     *
     * @param DateTime $startDate  The start of the search period.
     * @param DateTime $endDate  The end of the search period.
     *
     * @return array  The Patches to check.
     */
    private function findPatches($startDate, $endDate) {

        $feed    = new RBCommunications_PatchAlert_Model_Scraper();
        $patches = $feed->getPatchNotifications($startDate, $endDate);
        return $patches;

    }

    /**
     * Given a list of patches, returns those that have not yet been applied.
     *
     * @param array $patches  The array of patches to check.
     *
     * @return array  The unapplied patches.
     */
    private function getUnapplied($patches=[]) {

        if (count($patches) < 1) { return; }

        $unappliedPatches = [];
        $appliedPatches   = Mage::helper('patchalert')->appliedPatches();

        foreach ($patches as $patch) {

            $patchID = $patch->patchID;

            if (! in_array($patchID, $appliedPatches)) {
                $unappliedPatches[] = $patch;
            }

        }

        return $unappliedPatches;

    }

}
