<?php
/**
 * File handle file modifications within a module in a secure way
 *
 * Changelog
 * 2021-02-23 - Add file
 */

namespace RedooCalendar\Updater;

class Files
{
    /**
     * This function deletes a file within modules folder
     * @param $filePathRelativeToModule
     * @param $extension
     */
    public static function deleteModuleFile($filePathRelativeToModule, $extension) {
        $moduleName = basename(dirname(dirname(dirname(dirname(__FILE__)))));

        self::deleteFile(
            'modules' .
            DIRECTORY_SEPARATOR .
            $moduleName,
            $filePathRelativeToModule,
            $extension
        );
    }

    /**
     * This function deletes a file within module layout folder
     * @param $filePathRelativeToModule
     * @param $extension
     */
    public static function deleteLayoutFile($filePathRelativeToModule, $extension) {
        $moduleName = basename(dirname(dirname(dirname(dirname(__FILE__)))));

        self::deleteFile(
            'layouts' .
            DIRECTORY_SEPARATOR .
            'v7' .
            DIRECTORY_SEPARATOR .
            'modules' .
            DIRECTORY_SEPARATOR .
            $moduleName,
            $filePathRelativeToModule,
            $extension
        );
    }

    private static function deleteFile($basePath, $filePathRelativeToModule, $extension) {
        $moduleName = basename(dirname(dirname(dirname(dirname(__FILE__)))));

        $placeholder = '_-DIRECTORYSEPARATOR-_';
        $filePathRelativeToModule = str_replace(array('/', "\\"), $placeholder, $filePathRelativeToModule);

        $filePathRelativeToModule = preg_replace('/[^a-zA-Z0-9_-]/', '', $filePathRelativeToModule);

        $filePathRelativeToModule = str_replace($placeholder, DIRECTORY_SEPARATOR, $filePathRelativeToModule);

        $root_directory = vglobal('root_directory');
        $finalFilename = $root_directory .
            DIRECTORY_SEPARATOR .
            'layouts' .
            DIRECTORY_SEPARATOR .
            'v7' .
            DIRECTORY_SEPARATOR .
            'modules' .
            DIRECTORY_SEPARATOR .
            $moduleName .
            DIRECTORY_SEPARATOR .
            $filePathRelativeToModule . '.' .
            $extension;

        if(is_writable(dirname($finalFilename))) {
            unlink($finalFilename);
        }
    }
}
