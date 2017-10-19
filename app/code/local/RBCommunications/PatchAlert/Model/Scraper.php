<?php

/**
 * Scraper.
 * Scrapes the Magento patches blog
 * 
 * @package  RBCommunications_PatchAlert
 * @author   Nate Cornell <natec@rb-com.com>
 */
class RBCommunications_PatchAlert_Model_Scraper
{
    const SUPEE_REGEX = '/SUPEE-[0-9]*/';  # another regex?
    const TITLE_REGEX = '<div class="views-row.*?">.*?<div class="views-field views-field-title">'
                      . '[\s]*?<span class="field-content">[\s]*?<a href="(.*?)">(.*?)<\/a>[\s]*?'
                      . '<\/span>[\s]*?<\/div>[\s]*?';
    const DATE_REGEX = '<div class="views-field views-field-created">[\s]*?<span class="'
                     . 'field-content">(.*?)<\/span>[\s]*?<\/div>[\s]*?';
    const DESC_REGEX = '<div class="views-field views-field-field-body">[\s]*?<div class="'
                     . 'field-content">[\s]*?<p>(.*?)<\/p>[\s]*?<\/div>[\s]*?<\/div>[\s]*?<\/div>';
    const BLOG_REGEX = '/' . self::TITLE_REGEX . self::DATE_REGEX . self::DESC_REGEX . '/s';
    const MAGE_URL = 'https://magento.com';
    const URL = self::MAGE_URL . '/security/patches';
    const PST = 'America/Los_Angeles';
    const UTC = 'UTC';

    /**
     * Returns an array of patch objects from the Magento patches blog.
     *
     * @param DateTime $start  The start date to search from.
     * @param DateTime $end    The end date to search up to.
     *
     * @return array RBCommunications_PatchAlert_Model_Patch
     */
    public function getPatchNotifications($start, $end=null) {

        $patches = [];

        $blogData = $this->getBlogData();

        if (! $blogData || count($blogData) < 1) {
            return [];
        }

        foreach ($blogData as $item) {

            $post = [
                'url'         => self::MAGE_URL . (string)$item[1],
                'title'       => (string)$item[2],
                'date_added'  => gmdate('Y-m-d H:i:s', strtotime((string)$item[3] . ' 00:00:00')),
                'description' => (string)$item[4],
                'severity'    => 1
            ];

            $addDate = new DateTime($post['date_added']);

            # Filter out outdated alerts:
            if ($addDate < $start) { continue; }

            # Filter out 'too new' posts:
            if ($end && $addDate >= $end) { continue; }

            $patch = $this->postToPatch($post);

            if ($patch) {
                $patches[] = $patch;
            }
        }

        return $patches;
    }

    /**
     * Converts an array of posts to Patch objects.
     *
     * @param array $patchesDetails  The posts.
     *
     * @return RBCommunications_PatchAlert_Model_Patch
     */
    public static function postToPatch($details) {

        $patchIDMatches = [];
        preg_match(self::SUPEE_REGEX, $details['title'], $patchIDMatches);
        $patchID = $patchIDMatches[0];

        # If the patch isn't in the title (happens sometimes) check the body:
        if (! $patchID || $patchID == '') {
            $patchIDMatches = [];
            preg_match(self::SUPEE_REGEX, $details['description'], $patchIDMatches);
            $patchID = $patchIDMatches[0];
        }

        if (! $patchID || $patchID == '') { return null; }
        Mage::log($patchID);

        $description = $details['description'] . " For more details visit " . $details['url'];

        $patch = new RBCommunications_PatchAlert_Model_Patch(
            $patchID, $details['date_added'], $details['title'], $description
        );

        return $patch;

    }

    /**
     * Retrieve patch data from the website
     *
     * @return Array
     */
    public function getBlogData() {

        $curl = new Varien_Http_Adapter_Curl();

        $curl->setConfig(['timeout' => 2]);
        $curl->addOption(CURLOPT_USERAGENT, 'curl/PHP');
        $curl->write(Zend_Http_Client::GET, self::URL, '1.0');

        $data = $curl->read();

        if ($data === false) { return false; }

        $posts = [];

        preg_match_all(self::BLOG_REGEX, $data, $posts, PREG_SET_ORDER);

        $curl->close();

        return $posts;

    }

}
