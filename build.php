<?php
/**
 * User: lufei
 * Date: 2021/5/11
 * Email: lufei@php.net
 */

// 扩展目录
$dir = 'C:\tools\cygwin\lib\php\20180731';
$files = scandir($dir);
unset($files[0], $files[1]); // . ..

$data = [];

$dll_path = [];
// 获取扩展dll
foreach ($files as $file) {
    $tmp_path = $dir . "\\" . $file;
    $dll_path[] = $tmp_path;
    exec("C:\\tools\\php\\deplister.exe {$tmp_path}", $output);
}
get_dll_path(array_unique($output), $data);

$bin_dll = array_unique($data);
var_dump($bin_dll);

// mkdir
exec("mkdir .\\bin\\");
exec("mkdir .\\etc\\php.d\\");
exec("mkdir .\\lib\\php\\20180731\\");

// pack

// php.exe
var_dump(copy("C:\\tools\\cygwin\\bin\\php.exe", ".\\bin\\php.exe"));

foreach ($bin_dll as $dll) {
    exec("cp {$dll} .\\bin\\");
}

// php.ini
var_dump(copy("C:\\tools\\cygwin\\etc\\php.ini", ".\\etc\\php.ini"));

dir_copy("C:\\tools\\cygwin\\etc\\php.d", ".\\etc\\php.d");
dir_copy("C:\\tools\\cygwin\\lib\\php\\20180731", ".\\lib\\php\\20180731");
dir_copy("C:\\tools\\cygwin\\usr\\share\\zoneinfo", ".\\usr\\share\\zoneinfo");

exec("ls .\\etc\\php.d\\", $etc);
var_dump($etc);
exec("ls .\\lib\\php\\20180731\\", $lib);
var_dump($lib);

$version = getenv('VERSION');
// pack
$pack_name = "openswoole-cygwin-{$version}";
HZip::zipDir(__DIR__, ".\\{$pack_name}.zip");
var_dump(filesize(".\\{$pack_name}.zip"));

/**
 * Class HZip
 * @see https://www.php.net/manual/en/class.ziparchive.php#110719
 */
class HZip
{
    /**
     * Add files and sub-directories in a folder to zip file.
     * @param string $folder
     * @param ZipArchive $zipFile
     * @param int $exclusiveLength Number of text to be exclusived from the file path.
     */
    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder\\$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Zip a folder (include itself).
     * Usage:
     *   HZip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     *
     * @param string $sourcePath Path of directory to be zip.
     * @param string $outZipPath Path of output zip file.
     */
    public static function zipDir($sourcePath, $outZipPath)
    {
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];

        $z = new ZipArchive();
        $z->open($outZipPath, ZIPARCHIVE::CREATE);
        $z->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $z, strlen("$parentPath\\"));
        $z->close();
    }
}

function get_dll_path($dll_array, &$array, $path = 'C:\tools\cygwin\bin\\')
{
    $output = [];
    foreach ($dll_array as $dll) {
        [$dll_name, $status] = explode(',', $dll);
        var_dump($dll_name, $status);
        $dll_path = $path . $dll_name;
        if (is_file($dll_path)) {
            $array[] = $dll_path;
            exec("C:\\tools\\php\\deplister.exe {$dll_path}", $output);
        } else {
            var_dump('Not found: ' . $dll_name);
        }
    }
    $output = array_unique($output);
    if (!empty($output)) {
        $output = get_dll_path($output, $array);
    }

    return array_unique($output);
}

/**
 * 文件夹文件拷贝
 *
 * @param string $src 来源文件夹
 * @param string $dst 目的地文件夹
 * @return bool
 */
function dir_copy($src = '', $dst = '')
{
    if (empty($src) || empty($dst)) {
        return false;
    }

    $dir = opendir($src);
    dir_mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '\\' . $file)) {
                dir_copy($src . '\\' . $file, $dst . '\\' . $file);
            } else {
                copy($src . '\\' . $file, $dst . '\\' . $file);
            }
        }
    }
    closedir($dir);

    return true;
}

/**
 * 创建文件夹
 *
 * @param string $path 文件夹路径
 * @param int $mode 访问权限
 * @param bool $recursive 是否递归创建
 * @return bool
 */
function dir_mkdir($path = '', $mode = 0777, $recursive = true)
{
    clearstatcache();
    if (!is_dir($path)) {
        mkdir($path, $mode, $recursive);
        return chmod($path, $mode);
    }

    return true;
}