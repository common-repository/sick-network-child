<?php
class SickNetworkBackup
{
    protected static $instance = null;
    protected $zip;
    protected $zipArchiveFileCount;
    protected $zipArchiveSizeCount;
    protected $zipArchiveFileName;

    protected function __construct()
    {

    }

    public static function get()
    {
        if (self::$instance == null)
        {
            self::$instance = new SickNetworkBackup();
        }
        return self::$instance;
    }

    /**
     * Create full backup
     *
     * @return array Array consisting of timestamp and the created file path
     */
    public function createFullBackup($excludes, $filePrefix = '', $addConfig = false, $includeCoreFiles = false)
    {
        $dirs = SickNetworkHelper::getSickNetworkDir('backup');
        $backupdir = $dirs[0];
        if (!defined('PCLZIP_TEMPORARY_DIR')) define('PCLZIP_TEMPORARY_DIR', $backupdir);

        $timestamp = time();
        if ($filePrefix != '') $filePrefix .= '-';
        $filepath = $backupdir . 'backup-' . $filePrefix . $timestamp . '.zip';
        $fileurl = $dirs[1] . 'backup-' . $filePrefix . $timestamp . '.zip';

        if ($dh = opendir($backupdir))
        {
            while (($file = readdir($dh)) !== false)
            {
                if ($file != '.' && $file != '..' && preg_match('/^backup-(.*).zip/', $file))
                {
                    @unlink($backupdir . $file);
                }
            }
            closedir($dh);
        }

        if (!$addConfig)
        {
            if (!in_array(str_replace(ABSPATH, '', WP_CONTENT_DIR), $excludes) && !in_array('wp-admin', $excludes) && !in_array(WPINC, $excludes))
            {
                $addConfig = true;
                $includeCoreFiles = true;
            }
        }

        $success = false;
        if ($this->checkZipSupport() && $this->createZipFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles))
        {
            $success = true;
        }
        else if ($this->checkZipConsole() && $this->createZipConsoleFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles))
        {
            $success = true;
        }
        else if ($this->createZipPclFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles))
        {
            $success = true;
        }

        return ($success) ? array(
            'timestamp' => $timestamp,
            'file' => $fileurl,
            'filesize' => filesize($filepath)
        ) : false;
    }

    /**
     * Check for default PHP zip support
     *
     * @return bool
     */
    public function checkZipSupport()
    {
        return class_exists('ZipArchive');
    }

    /**
     * Check if we could run zip on console
     *
     * @return bool
     */
    public function checkZipConsole()
    {
        return false;
//        return function_exists('system');
    }

    /**
     * Create full backup using default PHP zip library
     *
     * @param string $filepath File path to create
     * @return bool
     */
    public function createZipFullBackup($filepath, $excludes, $addConfig = false, $includeCoreFiles = false)
    {
        $this->zip = new ZipArchive();
        $this->zipArchiveFileCount = 0;
        $this->zipArchiveSizeCount = 0;
        $this->zipArchiveFileName = $filepath;
        $zipRes = $this->zip->open($filepath, ZipArchive::CREATE);
        if ($zipRes)
        {
            if (!$addConfig)
            {
                $nodes = glob(ABSPATH . '*');
            }
            else
            {
                $nodes = array(WP_CONTENT_DIR);
                if ($includeCoreFiles)
                {
                    $nodes[] = ABSPATH . WPINC;
                    $nodes[] = ABSPATH . basename(admin_url(''));

                    $coreFiles = array('index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-app.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php', 'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-register.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
                    foreach ($coreFiles as $coreFile)
                    {
                        if (file_exists(ABSPATH . $coreFile)) $nodes[] = ABSPATH . $coreFile;
                    }
                    unset($coreFiles);
                }
            }

            $this->createBackupDB(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');
            $this->addFileToZip(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql', basename(WP_CONTENT_DIR) . '/' . 'dbBackup.sql');
            foreach ($nodes as $node)
            {
                if ($excludes == null || !in_array(str_replace(ABSPATH, '', $node), $excludes))
                {
                    if (is_dir($node))
                    {
                        $this->zipAddDir($node, $excludes);
                    }
                    else if (is_file($node))
                    {
                        $this->addFileToZip($node, str_replace(ABSPATH, '', $node));
                    }
                }
            }

            if ($addConfig)
            {
                $string = base64_encode(serialize(array('siteurl' => get_option('siteurl'),
                                                'home' => get_option('home'), 'abspath' => ABSPATH)));

                $this->addFileFromStringToZip('clone/config.txt', $string);
            }

            $return = $this->zip->close();
            @unlink(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');

            return $return;
        }
        return false;
    }

    /**
     * Create full backup using pclZip library
     *
     * @param string $filepath File path to create
     * @return bool
     */
    public function createZipPclFullBackup($filepath, $excludes, $addConfig, $includeCoreFiles)
    {
        global $classDir;
        include_once($classDir . 'pclzip.lib.php');
        $this->zip = new PclZip($filepath);
        if (!$addConfig)
        {
            $nodes = glob(ABSPATH . '*');
        }
        else
        {
            $nodes = array(WP_CONTENT_DIR);

            if ($includeCoreFiles)
            {
                $nodes[] = ABSPATH . WPINC;
                $nodes[] = ABSPATH . basename(admin_url(''));

                $coreFiles = array('index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-app.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php', 'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-register.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
                foreach ($coreFiles as $coreFile)
                {
                    if (file_exists(ABSPATH . $coreFile)) $nodes[] = ABSPATH . $coreFile;
                }
                unset($coreFiles);
            }
        }

        $this->createBackupDB(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');
        $error = false;
        if (($rslt = $this->zip->add(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql', PCLZIP_OPT_REMOVE_PATH, dirname($filepath), PCLZIP_OPT_ADD_PATH, basename(WP_CONTENT_DIR))) == 0) $error = true;

        @unlink(dirname($filepath) . DIRECTORY_SEPARATOR . 'dbBackup.sql');
        if (!$error)
        {
            foreach ($nodes as $node)
            {
                if ($excludes == null || !in_array(str_replace(ABSPATH, '', $node), $excludes))
                {
                    if (is_dir($node))
                    {
                        if (!$this->pclZipAddDir($node, $excludes))
                        {
                            $error = true;
                            break;
                        }
                    }
                    else if (is_file($node))
                    {
                        if (($rslt = $this->zip->add($node, PCLZIP_OPT_REMOVE_PATH, ABSPATH)) == 0)
                        {
                            $error = true;
                            break;
                        }
                    }
                }
            }
        }

        if ($addConfig)
        {
            $string = base64_encode(serialize(array('siteurl' => get_option('siteurl'),
                                            'home' => get_option('home'), 'abspath' => ABSPATH)));

            $this->addFileFromStringToPCLZip('clone/config.txt', $string);
        }

        if ($error)
        {
            @unlink($filepath);
            return false;
        }
        return true;
    }

    /**
     * Recursive add directory for default PHP zip library
     */
    public function zipAddDir($path, $excludes)
    {
        $this->zip->addEmptyDir(str_replace(ABSPATH, '', $path));
        $nodes = glob(rtrim($path, '/') . '/*');
        foreach ($nodes as $node)
        {
            if ($excludes == null || !in_array(str_replace(ABSPATH, '', $node), $excludes))
            {
                if (is_dir($node))
                {
                    $this->zipAddDir($node, $excludes);
                }
                else if (is_file($node))
                {
                    $this->addFileToZip($node, str_replace(ABSPATH, '', $node));
                }
            }
        }
    }

    public function pclZipAddDir($path, $excludes)
    {
        $error = false;
        $nodes = glob(rtrim($path, '/') . '/*');
        foreach ($nodes as $node)
        {
            if ($excludes == null || !in_array(str_replace(ABSPATH, '', $node), $excludes))
            {
                if (is_dir($node))
                {
                    if (!$this->pclZipAddDir($node, $excludes))
                    {
                        $error = true;
                        break;
                    }
                }
                else if (is_file($node))
                {
                    if (($rslt = $this->zip->add($node, PCLZIP_OPT_REMOVE_PATH, ABSPATH)) == 0)
                    {
                        $error = true;
                        break;
                    }
                }
            }
        }
        return !$error;
    }

    function addFileFromStringToZip($file, $string)
    {
        return $this->zip->addFromString($file, $string);
    }

    public function addFileFromStringToPCLZip($file, $string)
   	{
   		if (false === $this->openned) {
   			return false;
   		}
   		if (file_exists($this->filename) && !is_writable($this->filename)) {
   			return false;
   		}
           $file = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $file);
   		$localpath = dirname($file);
   		$tmpfilename = self::TMP_DIR . '/' . basename($file);
   		if (false !== file_put_contents($tmpfilename, $string)) {
   			$this->pclzip->delete(PCLZIP_OPT_BY_NAME, $file);
   			$add = $this->pclzip->add($tmpfilename,
   				PCLZIP_OPT_REMOVE_PATH, self::TMP_DIR,
   				PCLZIP_OPT_ADD_PATH, $localpath);
   			unlink($tmpfilename);
   			if (!empty($add)) {
   				return true;
   			}
   		}
   		return false;
   	}

    function addFileToZip($path, $zipEntryName)
    {
        // this would fail with status ZIPARCHIVE::ER_OPEN
        // after certain number of files is added since
        // ZipArchive internally stores the file descriptors of all the
        // added files and only on close writes the contents to the ZIP file
        // see: http://bugs.php.net/bug.php?id=40494
        // and: http://pecl.php.net/bugs/bug.php?id=9443
        // return $zip->addFile( $path, $zipEntryName );

        $this->zipArchiveFileCount++;
        $this->zipArchiveSizeCount += filesize($path);

        $added = $this->zip->addFile($path, $zipEntryName);
//        if (true || filesize($path) > 10485760)
//        {
//            echo 'addFile ' . $path . ' : ' . $added . '<br />';
//        }
//        else
//        {
//            $contents = file_get_contents($path);
//            if ($contents === false)
//            {
//                return false;
//            }
//            $added = $this->zip->addFromString($zipEntryName, $contents);
//        }

        //Over limits? 30 files or 30MB of files added
        if (($this->zipArchiveFileCount >= 254) || ($this->zipArchiveSizeCount >= 31457280))
        {
            $this->zip->close();
            $this->zip->open($this->zipArchiveFileName, ZipArchive::CREATE);
            $this->zipArchiveFileCount = 0;
            $this->zipArchiveSizeCount = 0;
        }

        return $added;
    }

    /**
     * Create full backup using zip on console
     *
     * @param string $filepath File path to create
     * @return bool
     */
    public function createZipConsoleFullBackup($filepath, $excludes, $addConfig)
    {
        // @TODO to work with 'zip' from system if PHP Zip library not available
        //system('zip');
        return false;
    }

    /**
     * Create full SQL backup
     *
     * @return string The SQL string
     */
    public function createBackupDB($filepath)
    {
        $fh = fopen($filepath, 'w'); //or error;

        global $wpdb;
        $maxchars = 50000;

        //Get all the tables
        $tables_db = $wpdb->get_results('SHOW TABLES FROM ' . DB_NAME, ARRAY_N);
        foreach ($tables_db as $curr_table)
        {
            $table = $curr_table[0];

            fwrite($fh, "\n" . 'DROP TABLE IF EXISTS ' . $table . ';');
            $table_create = $wpdb->get_row('SHOW CREATE TABLE ' . $table, ARRAY_N);
            fwrite($fh, "\n" . $table_create[1] . ';');

            //$rows = $wpdb->get_results('SELECT * FROM ' . $table, ARRAY_N);
            $rows = @mysql_query('SELECT * FROM ' . $table, $wpdb->dbh);
            if ($rows)
            {
                $table_columns = $wpdb->get_results('SHOW COLUMNS FROM ' . $table);
                $table_columns_insert = '';
                foreach ($table_columns as $table_column)
                {
                    if ($table_columns_insert != '')
                        $table_columns_insert .= ', ';
                    $table_columns_insert .= '`' . $table_column->Field . '`';
                }
                $table_insert = 'INSERT INTO `' . $table . '` (';
                $table_insert .= $table_columns_insert;
                $table_insert .= ') VALUES ' . "\n";


                $current_insert = $table_insert;

                $inserted = false;
                $add_insert = '';
                while (($row = @mysql_fetch_array($rows, MYSQL_ASSOC)))
                {
                    //Create new insert!
                    $add_insert = '(';
                    $add_insert_each = '';
                    foreach ($row as $value)
                    {
                        $add_insert_each .= "'" . str_replace(array("\n", "\r", "'"), array('\n', '\r', "\'"), $value) . "',";
                    }
                    $add_insert .= trim($add_insert_each, ',') . ')';

                    //If we already inserted something & the total is too long - commit previous!
                    if ($inserted && strlen($add_insert) + strlen($current_insert) >= $maxchars)
                    {
                        fwrite($fh, "\n" . $current_insert . ';');
                        $current_insert = $table_insert;
                        $current_insert .= $add_insert;
                        $inserted = false;
                    }
                    else
                    {
                        if ($inserted)
                        {
                            $current_insert .= ', ' . "\n";
                        }
                        $current_insert .= $add_insert;
                    }
                    $inserted = true;
                }
                if ($inserted)
                {
                    fwrite($fh, "\n" . $current_insert . ';');
                }
            }
        }

        fclose($fh);
        return true;
    }

}

?>
