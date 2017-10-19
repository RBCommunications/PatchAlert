<?php

/**
 * Feed.
 * Pulls the Magento notification feed.
 * 
 * @package  RBCommunications_PatchAlert
 * @author   Nate Cornell <natec@rb-com.com>
 */
class RBCommunications_PatchAlert_Model_Feed extends Mage_AdminNotification_Model_Feed
{
    const PATCH_REGEX = '/SUPEE-\d+/'; 
    const PST = 'America/Los_Angeles';
    const UTC = 'UTC';

    /**
     * Returns an array of patch objects from the Magento RSS feed.
     *
     * @param DateTime $start  The start date to search from.
     * @param DateTime $end    The end date to search up to.
     *
     * @return array RBCommunications_PatchAlert_Model_Patch
     */
    public function getPatchNotifications($start, $end = null) {

        $patches = [];

        $feedXml = $this->getFeedData();

        if (! $feedXml || ! $feedXml->channel || ! $feedXml->channel->item) {
            return [];
        }

        foreach ($feedXml->channel->item as $item) {

            $notification = [
                'severity'      => (int)$item->severity,
                'date_added'    => $this->getDate((string)$item->pubDate),
                'title'         => (string)$item->title,
                'description'   => (string)$item->description,
                'url'           => (string)$item->link
            ];

            $addDate = new DateTime($notification['date_added']);

            # Filter out non-security or outdated notificatons:
            if ($notification['severity'] > 2 || $addDate < $start) {
                echo "Too old or not severe enough: {$notification['title']}\n";
                continue; 
            }

            # Filter out 'too new' notifications:
            echo $end->format('Y-m-d');
            if ($end && $addDate >= $end) {
                echo "Too new: {$notification['title']}\n";
                continue;
            }

            $patch = $this->notificationToPatch($notification);

            if ($patch) {
                $patches[] = $patch;
            }
        }

        # Mage::log(print_r($patches, true));
        return $patches;

    }

    /**
     * Converts an array of adminnotification rows to Patch objects.
     *
     * @param array $details  The adminnotification rows.
     *
     * @return RBCommunications_PatchAlert_Model_Patch
     */
    public static function notificationToPatch($details) {
        # Mage::log(print_r($details, true));

        $patchIDMatches = [];
        preg_match(self::PATCH_REGEX, $details['title'], $patchIDMatches);
        $patchID = $patchIDMatches[0];

        # If the patch isn't in the title (happens sometimes) check the body:
        if (! $patchID || $patchID == '') {
            $patchIDMatches = [];
            preg_match(self::PATCH_REGEX, $details['description'], $patchIDMatches);
            $patchID = $patchIDMatches[0];
        }

        if (! $patchID || $patchID == '') { return null; }

        $patch = new RBCommunications_PatchAlert_Model_Patch(
            $patchID, $details['date_added'], $details['title'], $details['description']
        );

        return $patch;

    }

}
