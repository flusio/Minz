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
     */
    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function createTmpFolder(): void
    {
        @mkdir(\Minz\Configuration::$tmp_path, 0777, true);
    }

    /**
     * Copy a file in a temporary folder and return its path.
     */
    public function tmpCopyFile(string $filepath): string
    {
        $tmp_path = \Minz\Configuration::$tmp_path;
        $tmp_filepath = $tmp_path . '/' . bin2hex(random_bytes(10));
        copy($filepath, $tmp_filepath);
        return $tmp_filepath;
    }
}
