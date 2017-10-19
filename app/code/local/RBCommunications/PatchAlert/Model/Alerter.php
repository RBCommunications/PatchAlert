<?php

/**
 * Patch Alerter.
 * Sends alert messages about patches
 * 
 * @package  RBCommunications_PatchAlert
 * @author   Nate Cornell <natec@rb-com.com>
 */
class RBCommunications_PatchAlert_Model_Alerter extends Mage_Core_Model_Abstract
{

    private $plural = false;
    private $new;

    /**
     * Generates and sends an alert email concerning the given patches.
     *
     * @param array $patches  The unapplied patches to alert about.
     * @param boolean $new    Whether or not the patch is new.
     * 
     * @return boolean  The result of the email attempt.
     */
    public function emailPatchAlert($patches, $new = true) {

        $this->new = $new;
        $patchCount = count($patches);

        if (!is_array($patches) || $patchCount < 0) {
            return false;
        }

        if ($patchCount > 1) {
            $this->plural = true;
        }

        $recipient = Mage::getStoreConfig('patchalert/config/to_email');
        $subject   = $this->createSubject($patches);
        $body      = $this->createBody($patches);

        Mage::log("Sending message $subject to $recipient");

        return $this->send($subject, $body);

    }

    /**
     * Generates the body of the alert email, containing the notifications.
     *
     * @param array $patches  The patch objects.
     *
     * @return string  The generated message body.
     */
    private function createBody($patches) {

        $patchWord = ($this->plural) ? "patches" : "patch";
        $body = "Notifications related to the $patchWord:\n\n";

        foreach ($patches as $patch) {

            $dateOnly = substr($patch->createdDate, 0, 10);
            $body .= "*{$patch->patchID}*                                     $dateOnly\n";

            if ($patch->patchID != $patch->title) {
                $body .= "-{$patch->title}-\n";
            }
            $body .= "{$patch->details}\n\n";

        }

        $body .= "Please apply the $patchWord as soon as possible to mitigate security risks.\n";

        # make good for plain text email
        $body = chunk_split($body, 72, "\n");

        return $body;

    }

    /**
     * Generates the subject of the alert email, containing the patch ids.
     *
     * @param array $patches  The patch objects.
     *
     * @return string  The generated message subject.
     */
    private function createSubject($patches) {

        if ($this->new) {
            $subject = ($this->plural) ? "New patches " : "New patch ";
        } else {
            $subject = ($this->plural) ? "Patches "     : "Patch ";
        }

        $iteration = 0;

        $patchCount = count($patches);

        foreach ($patches as $patch) {

            $subject .= "$patch->patchID";

            if ($this->plural && $patchCount > 2 && $iteration < $patchCount - 1 ) {
                $subject .= ', ';
            }

            if ($iteration == $patchCount - 2) {
                $subject .= " and ";
            }

            $iteration++;

        }

        if ($this->new) {
            $subject .= " ready to apply.";
        } else {
            $subject = "*URGENT* " . $subject . " still awaiting application!";
        }

        return $subject;

    }

    /**
     * Sends the generated email to the configured recipient.
     *
     * @param string $subject  The subject line of the email.
     * @param string $message  The message body.
     *
     * @return boolean
     */
    protected function send($subject, $message) {

        $toAddress = Mage::getStoreConfig('patchalert/config/to_email');
        $toName    = Mage::getStoreConfig('patchalert/config/to_name');
        $storeId   = Mage::app()->getDefaultStoreView()->getStoreId();

        if (!$toAddress) { return false; }

        $template = Mage::getModel('core/email_template')->loadDefault('patchalert_email_template');

        $template->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name', $storeId))
            ->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email', $storeId))
            ->setTemplateType(Mage_Core_Model_Template::TYPE_TEXT)
            ->setTemplateSubject($subject)
            ->setTemplateText($message);

        return $template->send($toAddress, $toName);

    }

}
