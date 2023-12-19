<?php
namespace Mapbender\CoreBundle\Component;

/**
 * The class with utility functions.
 *
 * @author Paul Schmidt
 */
class Utils
{
    /**
     * Removes a file or directory (recursive)
     *
     * @param string $path tha path of file/directory
     * @return boolean true if the file/directory is removed.
     */
    public static function deleteFileAndDir($path)
    {
        if (is_file($path)) {
            return @unlink($path);
        } elseif (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if ($file !== '.' && $file !== '..' && (is_file($path . "/" . $file) || is_dir($path . "/" . $file))) {
                    Utils::deleteFileAndDir($path . "/" . $file);
                }
            }
            return @rmdir($path);
        }
    }

    /**
     * Copies an order recursively.
     * @param string $sourceOrder path to source order
     * @param string $destinationOrder path to destination order
     */
    public static function copyOrderRecursive($sourceOrder, $destinationOrder)
    {
        $dir  = opendir($sourceOrder);
        @mkdir($destinationOrder);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($sourceOrder . '/' . $file)) {
                    Utils::copyOrderRecursive($sourceOrder . '/' . $file, $destinationOrder . '/' . $file);
                } else {
                    copy($sourceOrder . '/' . $file, $destinationOrder . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Generates an UUID.
     * @return string uuid
     */
    public static function guidv4()
    {
        $data = openssl_random_pseudo_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
