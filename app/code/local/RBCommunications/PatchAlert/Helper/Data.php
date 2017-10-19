<?php

class RBCommunications_PatchAlert_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns the estimated launch date of the site, based on the creation
     * date of the oldest admin account (until a better source is identified).
     *
     * @return DateTime  The date time object representing the launch date.
     */
    public static function getSiteLaunchDate() {

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sql = "
            SELECT min(created)
            FROM admin_user
        ";

        $startDate = $connection->fetchOne($sql);

        if (! $startDate || $startDate == '') {
            return null;
        }

        return new DateTime($startDate, new DateTimeZone('UTC'));
    }

    /**
     * Returns an array of the applied patch IDs.
     *
     * @return array  The patch IDs
     */
    public static function appliedPatches() {

        $appliedPatches      = [];
        $appliedPatchesFile  = Mage::getRoot() . DS . 'etc/applied.patches.list';
        $patchRegex          = '/SUPEE-\d+/';
        $appliedPatchesLines = preg_grep($patchRegex, file($appliedPatchesFile));

        foreach ($appliedPatchesLines as $line) {

            $matches = [];
            preg_match($patchRegex, $line, $matches);

            if (is_array($matches) && count($matches) > 0 && isset($matches[0])) {
                $appliedPatches[] = $matches[0];
            }

        }

        return $appliedPatches;
    }
}

?>
