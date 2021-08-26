<?php

namespace Minz\Tests;

/**
 * Provide useful method to ease the tests of file uploads.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FilesHelper
{
    /**
     * Make sure the tmp folder exists.
     *
     * @beforeClass
     */
    public static function createTmpFolder()
    {
        @mkdir(\Minz\Configuration::$tmp_path, 0777, true);
    }

    /**
     * Copy a file in a temporary folder.
     *
     * @param string $filepath
     *
     * @return string Return the temporary filepath
     */
    public function tmpCopyFile($filepath)
    {
        $tmp_path = \Minz\Configuration::$tmp_path;
        $tmp_filepath = $tmp_path . '/' . md5(rand());
        copy($filepath, $tmp_filepath);
        return $tmp_filepath;
    }
}
