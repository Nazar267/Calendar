<?php
namespace RedooCalendar\Updater\Settings;

use RedooCalendar\Updater\MODDBCheck;

class Block
{
    /**
     * Add a block to CRM Settings sidebar
     *
     * @param string $blockLabel the headline of the block. Can be translated of Vtiger module
     * @param int $sequence Define the position this block is added.
     */
    public static function add($blockLabel, $sequence) {

        $sql = "SELECT * FROM vtiger_settings_blocks WHERE label = ?";
        $result = MODDBCheck::pquery($sql, array($blockLabel));

        if(strtolower($sequence) === 'lastelement') {
            $seq_res = MODDBCheck::pquery("SELECT MAX(sequence) AS max_seq FROM vtiger_settings_blocks", array(), true);

            $sequence = MODDBCheck::query_result($seq_res, 0, 'max_seq');
            $sequence = $sequence + 1;
        }

        if(MODDBCheck::numRows($result) == 0) {
            $blockid = MODDBCheck::getUniqueID('vtiger_settings_blocks');

            $seq_res = MODDBCheck::pquery("SELECT MAX(blockid) AS max_seq FROM vtiger_settings_blocks", array(), true);
            if (MODDBCheck::numRows($seq_res) > 0) {
                $cur_seq = MODDBCheck::query_result($seq_res, 0, 'max_seq');
                if ($cur_seq != null)	$cur_seq = $cur_seq + 1;
            }

            $seq_res = MODDBCheck::pquery("SELECT MAX(blockid) AS max_seq FROM vtiger_settings_blocks WHERE blockid >= ?", array($blockid), true);
            if (MODDBCheck::numRows($seq_res) > 0) {
                $tmp = MODDBCheck::query_result($seq_res, 0, 'max_seq');
                if (!empty($tmp)) {
                    $blockid = $tmp + 1;
                    $sql = 'UPDATE vtiger_settings_blocks_seq SET id = '.($blockid);
                    MODDBCheck::query($sql);
                }
            }

            if(empty($blockid)) {
                $blockid = MODDBCheck::getUniqueID('vtiger_settings_blocks');
            }

            MODDBCheck::pquery(
                'INSERT INTO vtiger_settings_blocks(blockid, label, sequence)
            VALUES (?,?,?)',
                array(
                    $blockid,
                    $blockLabel,
                    $sequence,
                ),
                true);
        }

    }
}