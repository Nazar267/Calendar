<?php
namespace RedooCalendar\Updater\Settings;

use RedooCalendar\Updater\MODDBCheck;

class Field
{
    /**
     * Add a link into CRM Settings
     *
     * @param string $blockName Name of the Block, which get the link "LBL_OTHER_SETTINGS". You get values from database table "vtiger_settings_blocks"
     * @param string $label Linktext which is shown on sidebar
     * @param string $description The description of the link, which is shown, when link is pinned to dashboard
     * @param string $linkto target URL of this link
     * @param boolean $pinned Is the link pinned to dashboard
     */
    public static function add($blockName, $label, $description, $linkto, $pinned = false) {

        $sql = "SELECT * FROM vtiger_settings_field WHERE linkto = ?";
        $result = MODDBCheck::pquery($sql, array($linkto));

        $blockid = getSettingsBlockId($blockName);

        if(MODDBCheck::numRows($result) == 0) {
            $fieldid = MODDBCheck::getUniqueID('vtiger_settings_field');

            $seq_res = MODDBCheck::pquery("SELECT MAX(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid), true);
            if (MODDBCheck::numRows($seq_res) > 0) {
                $cur_seq = MODDBCheck::query_result($seq_res, 0, 'max_seq');
                if ($cur_seq != null)	$cur_seq = $cur_seq + 1;
            } else {
                $cur_seq = 1;
            }

            $seq_res = MODDBCheck::pquery("SELECT MAX(fieldid) AS max_seq FROM vtiger_settings_field WHERE fieldid >= ?", array($fieldid), true);
            if (MODDBCheck::numRows($seq_res) > 0) {
                $tmp = MODDBCheck::query_result($seq_res, 0, 'max_seq');
                if (!empty($tmp)) {
                    $fieldid = $tmp + 1;
                    $sql = 'UPDATE vtiger_settings_field_seq SET id = '.($fieldid);
                    MODDBCheck::query($sql);
                }
            }

            if(empty($fieldid)) {
                $fieldid = MODDBCheck::getUniqueID('vtiger_settings_field');
            }


            MODDBCheck::pquery(
                'INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned)
            VALUES (?,?,?,?,?,?,?,?,?)',
                array(
                    $fieldid,
                    $blockid,
                    $label,
                    'Smarty/templates/modules/Workflow2/settings.png',
                    $description,
                    $linkto,
                    $cur_seq,
                    0,
                    $pinned ? 1 : 0
                ),
                true);
        } else {
            $fieldData = MODDBCheck::fetchByAssoc($result);

            MODDBCheck::pquery(
                'UPDATE vtiger_settings_field SET blockid = ?, name = ?, iconpath = ?, description = ?
            WHERE fieldid = ?',
                array(
                    $blockid,
                    $label,
                    'Smarty/templates/modules/Workflow2/settings.png',
                    $description,
                    $fieldData['fieldid'],
                ),
                true);
        }

    }

    /**
     * Delete a link from CRM Settings
     * @param string $linkto Which is the link target of the URL
     */
    public function del($linkto) {

        $sql = "DELETE FROM vtiger_settings_field WHERE linkto = ?";
        MODDBCheck::pquery($sql, array($linkto));

    }
}