<?php

class SickNetworkClone
{
    public static function init()
    {
        self::init_ajax();

        add_action('check_admin_referer', array('SickNetworkClone', 'permalinkChanged'));
        if (get_option('_sicknetwork_clone_permalink')) add_action('admin_notices', array('SickNetworkClone', 'permalinkAdminNotice'));
    }

    public static function renderHeader()
    {
        self::renderStyle();
        ?>
        <div class="sicknetwork-container">
        <?php
    }

    public static function renderFooter()
    {
        ?>
        </div>
        <?php
    }

    public static function render()
    {
        $uploadError = false;
        $uploadFile = false;
        if (isset($_REQUEST['upload']))
        {
            if (isset($_FILES['file']))
            {
                if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploadedfile = $_FILES['file'];
                $upload_overrides = array('test_form' => false);
                $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
                if ($movefile)
                {
                    $uploadFile = str_replace(ABSPATH, '', $movefile['file']);
                }
                else
                {
                    $uploadError = 'File could not be uploaded.';
                }
            }
            else
            {
                $uploadError = __('File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.');
            }
        }

        $sitesToClone = get_option('sicknetwork_clone_sites');
        $uploadSizeInBytes = min(SickNetworkHelper::return_bytes(ini_get('upload_max_filesize')), SickNetworkHelper::return_bytes(ini_get('post_max_size')));
        $uploadSize = SickNetworkHelper::human_filesize($uploadSizeInBytes);
        self::renderHeader();

        ?><div id="icon-options-general" class="icon32"><br></div><h2>Clone or Restore</h2><?php

        if ($sitesToClone == '0')
        {
            echo '<div class="sicknetwork_info-box-red"><strong>Cloning is currently off - To turn on return to your main dashboard and turn cloning on on the Migrate/Clone page.</strong></div>';
            return;
        }

        if (!is_writable(WP_CONTENT_DIR))
        {
            echo '<div class="sicknetwork_info-box-red"><strong>Your content directory is not writable. Please set 0755 permission to ' . basename(WP_CONTENT_DIR) . '. (' . WP_CONTENT_DIR . ')</strong></div>';
            $error = true;
        }
        ?>
    <div class="sicknetwork_info-box-green" style="display: none;">Cloning process completed successfully! You will now need to click <a href="<?php echo admin_url('options-permalink.php'); ?>">here</a> to re-login to the admin and re-save permalinks.</div>

    <?php
        if ($uploadFile)
        {
            ?>Upload successful. <a href="#" id="sicknetwork_uploadclonebutton" class="button-primary" file="<?php echo $uploadFile; ?>">Clone/Restore Website</a><?php
        }
        else
        {
            if ($uploadError)
            {
                ?><div class="sicknetwork_info-box-red"><?php echo $uploadError; ?></div><?php
            }

            if (empty($sitesToClone))
            {
                echo '<div class="sicknetwork_info-box-yellow"><strong>Cloning is currently on but no sites have been allowed, to allow sites return to your main dashboard and turn cloning on on the Migrate/Clone page.</strong></div>';
            }
            else
            {
?>
    <form method="post" action="">
        <div class="sicknetwork_select_sites_box">
            <div class="postbox">
                <div class="sicknetwork_displayby">Display by: <a class="sicknetwork_action left sicknetwork_action_down" href="#" id="sicknetwork_displayby_sitename">Site Name</a><a class="sicknetwork_action right" href="#" id="sicknetwork_displayby_url">URL</a></div><h2>Clone Options</h2>
                <div class="inside sicknetwork_inside">
                    <div id="sicknetwork_clonesite_select_site">
                        <?php
                        foreach ($sitesToClone as $siteId => $siteToClone)
                        {
                            ?>
                            <div class="clonesite_select_site_item" id="<?php echo $siteId; ?>" rand="<?php echo SickNetworkHelper::randString(5); ?>">
                                <div class="sicknetwork_size_label" size="<?php echo $siteToClone['size']; ?>"><?php echo $siteToClone['size']; ?> MB</div>
                                <div class="sicknetwork_name_label"><?php echo $siteToClone['name']; ?></div>
                                <div class="sicknetwork_url_label"><?php echo SickNetworkHelper::getNiceURL($siteToClone['url']); ?></div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="sicknetwork_clonebutton_container"><?php if (!$error) { ?><a href="#" id="sicknetwork_clonebutton" class="button-primary">Clone Website</a><?php } ?></div>
                <div style="clear:both"></div>
            </div>
        </div>
    </form>
    <br />
            <?php
            }
?>
    <div id="icon-options-general" class="icon32"><br></div><h2>Restore/Clone From Backup</h2>
        <br />
    Upload backup in .zip format (Maximum filesize for your server settings: <?php echo $uploadSize; ?>)<br/>
    <i>If you have a FULL backup created by your Network dashboard you may restore it by uploading here.<br />
    A database only backup will not work.</i><br /><br />
    <form action="<?php echo admin_url('options-general.php?page=SickNetworkClone&upload=yes'); ?>" method="post" enctype="multipart/form-data"><input type="file" name="file" id="file" /> <input type="submit" name="submit" id="filesubmit" disabled="disabled" value="Clone/Restore Website" /></form>
        <?php
        }
?>
    <div id="sicknetwork_clone_status" title="Clone process"></div>
    <script language="javascript">
        jQuery(document).on('change', '#file', function()
        {
            var maxSize = <?php echo $uploadSizeInBytes; ?>;
            var humanSize = '<?php echo $uploadSize; ?>';

            if (this.files[0].size > maxSize)
            {
                jQuery('#filesubmit').attr('disabled', 'disabled');
                alert('The selected file is bigger than your maximum allowed filesize. (Maximum: '+humanSize+')');
            }
            else
            {
                jQuery('#filesubmit').removeAttr('disabled');
            }
        });
        jQuery(document).on('click', '#sicknetwork_displayby_sitename', function() {
            jQuery('#sicknetwork_displayby_url').removeClass('sicknetwork_action_down');
            jQuery(this).addClass('sicknetwork_action_down');
            jQuery('.sicknetwork_url_label').hide();
            jQuery('.sicknetwork_name_label').show();
            return false;
        });
        jQuery(document).on('click', '#sicknetwork_displayby_url', function() {
            jQuery('#sicknetwork_displayby_sitename').removeClass('sicknetwork_action_down');
            jQuery(this).addClass('sicknetwork_action_down');
            jQuery('.sicknetwork_name_label').hide();
            jQuery('.sicknetwork_url_label').show();
            return false;
        });
        jQuery(document).on('click', '.clonesite_select_site_item', function() {
            jQuery('.clonesite_select_site_item').removeClass('selected');
            jQuery(this).addClass('selected');
        });

        var pollingCreation = undefined;
        var backupCreationFinished = false;

        var pollingDownloading = undefined;
        var backupDownloadFinished = false;

        handleCloneError = function(resp)
        {
            updateClonePopup(resp.error, true, 'red');
        };

        updateClonePopup = function(pText, pShowDate, pColor)
        {
            if (pShowDate == undefined) pShowDate = true;

            var theDiv = jQuery('#sicknetwork_clone_status');
            theDiv.append('<br /><span style="color: ' + pColor + ';">' + (pShowDate ? cloneDateToHMS(new Date()) + ' ' : '') + pText + '</span>');
            theDiv.animate({scrollTop: theDiv.height() * 2}, 100);
        };

        cloneDateToHMS = function(date) {
            var h = date.getHours();
            var m = date.getMinutes();
            var s = date.getSeconds();
            return '' + (h <= 9 ? '0' + h : h) + ':' + (m<=9 ? '0' + m : m) + ':' + (s <= 9 ? '0' + s : s);
        };

        cloneInitiateBackupCreation = function(siteId, siteName, size, rand, continueAnyway)
        {
            if ((continueAnyway == undefined) && (size > 256))
            {
                updateClonePopup('This is a large site ('+size+'MB), the clone process will more than likely fail. <a href="#" class="button continueCloneButton" onClick="cloneInitiateBackupCreation('+"'"+siteId+"'"+', '+"'"+siteName+"'"+', '+size+', '+"'"+rand+"'"+', true); return false;">Continue anyway</a>');
                return;
            }
            else
            {
                jQuery('.continueCloneButton').hide();
            }

            size = size / 2.4; //Guessing how large the zip will be

            //5 mb every 10 seconds
            updateClonePopup('Creating backup on '+siteName+' expected size: '+size.toFixed(2) + 'MB (estimated time: '+(size / 5 * 3).toFixed(2) + 'seconds)');

            updateClonePopup('<div id="sick-clone-create-progress" style="height: 10px !important;"></div>', false);
            jQuery('#sick-clone-create-progress').progressbar({value: 0, max: (size * 1024)});

            var data = {
                action:'sicknetwork_clone_backupcreate',
                siteId: siteId,
                rand: rand
            };

            jQuery.post(ajaxurl, data, function(pSiteId, pSiteName) { return function(resp) {
                backupCreationFinished = true;
                clearTimeout(pollingCreation);

                var progressBar = jQuery('#sick-clone-create-progress');
                progressBar.progressbar('value', progressBar.progressbar('option', 'max'));

                if (resp.error)
                {
                    handleCloneError(resp);
                    return;
                }
                updateClonePopup('Backup created on '+pSiteName+' total size to download: '+(resp.size / 1024).toFixed(2) + 'MB');
                //update view;
                cloneInitiateBackupDownload(pSiteId, resp.url, resp.size);
            } }(siteId, siteName), 'json');

            //Poll for filesize 'till it's complete
            pollingCreation = setTimeout(function() { cloneBackupCreationPolling(siteId, rand); }, 1000);
        };

        cloneBackupCreationPolling = function(siteId, rand)
        {
            if (backupCreationFinished) return;

            var data = {
                action:'sicknetwork_clone_backupcreatepoll',
                siteId: siteId,
                rand: rand
            };

            jQuery.post(ajaxurl, data, function(pSiteId, pRand) { return function(resp) {
                if (backupCreationFinished) return;
                if (resp.size)
                {
                    var progressBar = jQuery('#sick-clone-create-progress');
                    if (progressBar.progressbar('option', 'value') < progressBar.progressbar('option', 'max'))
                    {
                        progressBar.progressbar('value', resp.size);
                    }

                    //Also update estimated time?? ETA??
                }
                pollingCreation = setTimeout(function() { cloneBackupCreationPolling(pSiteId, pRand); }, 1000);
            } }(siteId, rand), 'json');
        };

        cloneInitiateBackupDownload = function(pSiteId, pUrl, pSize)
        {
            updateClonePopup('Downloading backup');

            updateClonePopup('<div id="sick-clone-download-progress" style="height: 10px !important;"></div>', false);
            jQuery('#sick-clone-download-progress').progressbar({value: 0, max: pSize});

            var data = {
                action:'sicknetwork_clone_backupdownload',
                siteId: pSiteId,
                url: pUrl
            };

            jQuery.post(ajaxurl, data, function(resp) {
                backupDownloadFinished = true;
                clearTimeout(pollingDownloading);

                var progressBar = jQuery('#sick-clone-download-progress');
                progressBar.progressbar('value', progressBar.progressbar('option', 'max'));

                if (resp.error)
                {
                    handleCloneError(resp);
                    return;
                }
                updateClonePopup('Backup downloaded');

                //update view;
                cloneInitiateExtractBackup();
            }, 'json');

            //Poll for filesize 'till it's complete
            pollingDownloading = setTimeout(function() { cloneBackupDownloadPolling(pSiteId, pUrl); }, 1000);
        };

        cloneBackupDownloadPolling = function(siteId, url)
        {
            if (backupDownloadFinished) return;

            var data = {
                action:'sicknetwork_clone_backupdownloadpoll',
                siteId: siteId,
                url: url
            };

            jQuery.post(ajaxurl, data, function(pSiteId) { return function(resp) {
                if (backupDownloadFinished) return;
                if (resp.size)
                {
                    var progressBar = jQuery('#sick-clone-download-progress');
                    if (progressBar.progressbar('option', 'value') < progressBar.progressbar('option', 'max'))
                    {
                        progressBar.progressbar('value', resp.size);
                    }
                }

                pollingDownloading = setTimeout(function() { cloneBackupDownloadPolling(pSiteId); }, 1000);
            } }(siteId), 'json');
        };

        cloneInitiateExtractBackup = function(file)
        {
            if (file == undefined) file = '';

            updateClonePopup('Extracting backup and updating your database, this might take a while. Please be patient.');
            //Extract & install SQL
            var data = {
                action:'sicknetwork_clone_backupextract',
                file: file
            };

            jQuery.post(ajaxurl, data, function(resp) {
                if (resp.error)
                {
                    handleCloneError(resp);
                    return;
                }

                updateClonePopup('Cloning process completed successfully!');

                setTimeout(function() {
                jQuery('#sicknetwork_clone_status').dialog('close');
                jQuery('.sicknetwork_select_sites_box').hide();
                jQuery('.sicknetwork_info-box-green').show();
                jQuery('#sicknetwork_uploadclonebutton').hide();
                jQuery('#sicknetwork_clonebutton').hide();
                }, 1000);
            }, 'json');
        };


        jQuery(document).on('click', '#sicknetwork_uploadclonebutton', function()
        {
            var file = jQuery(this).attr('file');
            jQuery('#sicknetwork_clone_status').dialog({
                resizable: false,
                height: 400,
                width: 750,
                modal: true,
                close: function(event, ui) {bulkTaskRunning = false; jQuery('#sicknetwork_clone_status').dialog('destroy'); }});

            cloneInitiateExtractBackup(file);
            return false;
        });

        jQuery(document).on('click', '#sicknetwork_clonebutton', function() {
            jQuery('#sicknetwork_clone_status').dialog({
                resizable: false,
                height: 400,
                width: 750,
                modal: true,
                close: function(event, ui) {bulkTaskRunning = false; jQuery('#sicknetwork_clone_status').dialog('destroy'); }});

            //Initiate backup creation on other child
            var siteElement = jQuery('.clonesite_select_site_item.selected');
            var siteId = siteElement.attr('id');
            var siteName = siteElement.find('.sicknetwork_name_label').html();
            var siteSize = siteElement.find('.sicknetwork_size_label').attr('size');
            var siteRand = siteElement.attr('rand');
            cloneInitiateBackupCreation(siteId, siteName, siteSize, siteRand);

            return false;
        })
    </script>
    <?php
    self::renderFooter();
    }

    public static function renderStyle()
    {
        ?>
    <style>
        #sicknetwork_clone_status {
            display: none;
        }
        .sicknetwork-container {
            padding-right: 10px;
            padding-top: 20px;
        }
        .sicknetwork_info-box-yellow {
            margin: 5px 0 15px;
            padding: .6em;
            background: #ffffe0;
            border: 1px solid #e6db55;
            border-radius: 3px ;
            -moz-border-radius: 3px ;
            -webkit-border-radius: 3px ;
            clear: both ;
        }
        .sicknetwork_info-box-red {
            margin: 5px 0 15px;
            padding: .6em;
            background: #ffebe8;
            border: 1px solid #c00;
            border-radius: 3px ;
            -moz-border-radius: 3px ;
            -webkit-border-radius: 3px ;
            clear: both ;
        }
        .sicknetwork_info-box-green {
            margin: 5px 0 15px;
            padding: .6em;
            background: rgba(127, 177, 0, 0.3);
            border: 1px solid #7fb100;
            border-radius: 3px ;
            -moz-border-radius: 3px ;
            -webkit-border-radius: 3px ;
            clear: both ;
        }
        .sicknetwork_select_sites_box {
            width: 505px;
        }
        #sicknetwork_clonesite_select_site {
            max-height: 585px !important ;
            overflow: auto;
            background: #fff ;
            width: 480px;
            border: 1px solid #DDDDDD;
            height: 300px;
            overflow-y: scroll;
            margin-top: 10px;
        }
        .clonesite_select_site_item {
            padding: 5px;
        }

        .clonesite_select_site_item.selected {
            background-color: rgba(127, 177, 0, 0.3);
        }

        .clonesite_select_site_item:hover {
            cursor: pointer;
            background-color: rgba(127, 177, 0, 0.3);
        }
        .sicknetwork_select_sites_box .postbox h2 {
            margin-left: 10px;
        }

        .sicknetwork_action
        {
            text-decoration: none;
            background: none repeat scroll 0 0 #FFFFFF;
            border-color: #C9CBD1 #BFC2C8 #A9ABB1;
            border-style: solid;
            color: #3A3D46;
            display: inline-block;
            font-size: 12px;
            padding: 4px 8px;
            -webkit-box-shadow: 0 1px 0 rgba(0,0,0,0.05);
            -moz-box-shadow: 0 1px 0 rgba(0,0,0,0.05);
            box-shadow: 0 1px 0 rgba(0,0,0,0.05);
        }
        .sicknetwork_action.left
        {
            border-width: 1px 0 1px 1px;
            -webkit-border-radius: 3px 0 0 3px;
            -moz-border-radius: 3px 0 0 3px;
            border-radius: 3px 0 0 3px;
        }
        .sicknetwork_action.right
        {
            border-width: 1px 1px 1px 1px;
            -webkit-border-radius: 0 3px 3px 0;
            -moz-border-radius: 0 3px 3px 0;
            border-radius: 0 3px 3px 0;
        }
        .sicknetwork_action_down
        {
            background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, rgba(220, 221, 225, 1)), color-stop(100%, rgba(234, 236, 241, 1)));
            background: -webkit-linear-gradient(top, rgba(220, 221, 225, 1) 0%, rgba(234, 236, 241, 1) 100%);
            background: -moz-linear-gradient(top, rgba(220, 221, 225, 1) 0%, rgba(234, 236, 241, 1) 100%);
            background: -o-linear-gradient(top, rgba(220, 221, 225, 1) 0%, rgba(234, 236, 241, 1) 100%);
            background: -ms-linear-gradient(top, rgba(220, 221, 225, 1) 0%, rgba(234, 236, 241, 1) 100%);
            background: linear-gradient(top, rgba(220, 221, 225, 1) 0%, rgba(234, 236, 241, 1) 100%);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr = '#dcdde1', endColorstr = '#eaecf1', GradientType = 0);
            -webkit-box-shadow: 0 1px 0 rgba(255, 255, 255, 0.59), 0 2px 0 rgba(0, 0, 0, 0.05) inset;
            -moz-box-shadow: 0 1px 0 rgba(255, 255, 255, 0.59), 0 2px 0 rgba(0, 0, 0, 0.05) inset;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.59), 0 2px 0 rgba(0, 0, 0, 0.05) inset;
            border-color: #b1b5c7 #bec2d1 #c9ccd9;
        }
        .sicknetwork_displayby {
            float: right;
            padding-top: 15px;
            padding-right: 10px;
        }
        .sicknetwork_url_label {
            display: none;
        }
        .sicknetwork_size_label {
            float: right;
            padding-right: 10px;
            font-style:italic;
            color: #8f8f8f;
        }
        .sicknetwork_clonebutton_container {
            float: right;
            padding-right: 10px;
            padding-top: 5px;
            padding-bottom: 10px;
        }
        </style>
        <?php
    }

    public static function init_ajax()
    {
        add_action('wp_ajax_sicknetwork_clone_backupcreate', array('SickNetworkClone', 'cloneBackupCreate'));
        add_action('wp_ajax_sicknetwork_clone_backupcreatepoll', array('SickNetworkClone', 'cloneBackupCreatePoll'));
        add_action('wp_ajax_sicknetwork_clone_backupdownload', array('SickNetworkClone', 'cloneBackupDownload'));
        add_action('wp_ajax_sicknetwork_clone_backupdownloadpoll', array('SickNetworkClone', 'cloneBackupDownloadPoll'));
        add_action('wp_ajax_sicknetwork_clone_backupextract', array('SickNetworkClone', 'cloneBackupExtract'));
    }

    public static function cloneBackupCreate()
    {
        try
        {
            if (!isset($_POST['siteId'])) throw new Exception('No site given');
            $siteId = $_POST['siteId'];
            $rand = $_POST['rand'];

            $sitesToClone = get_option('sicknetwork_clone_sites');
            if (!is_array($sitesToClone) || !isset($sitesToClone[$siteId])) throw new Exception('Site not found');

            $siteToClone = $sitesToClone[$siteId];
            $url = $siteToClone['url'];
            if (substr($url, -1) != '/') { $url .= '/'; }
            $url .= 'wp-admin/';

            $key = $siteToClone['extauth'];

            SickNetworkHelper::endSession();
            //Send request to the childsite!
            global $wp_version;
            $result = SickNetworkHelper::fetchUrl($url, array('cloneFunc' => 'createCloneBackup', 'key' => $key, 'file' => $rand, 'wpversion' => $wp_version));

            if (!$result['backup']) throw new Exception('Could not create backupfile on child');

            $output = array('url' => $result['backup'], 'size' => round($result['size'] / 1024, 0));
        }
        catch (Exception $e)
        {
            $output = array('error' => $e->getMessage());
        }

        die(json_encode($output));
    }

    public static function cloneBackupCreatePoll()
    {
        try
        {
            if (!isset($_POST['siteId'])) throw new Exception('No site given');
            $siteId = $_POST['siteId'];
            $rand = $_POST['rand'];

            $sitesToClone = get_option('sicknetwork_clone_sites');
            if (!is_array($sitesToClone) || !isset($sitesToClone[$siteId])) throw new Exception('Site not found');

            $siteToClone = $sitesToClone[$siteId];
            $url = $siteToClone['url'];
            if (substr($url, -1) != '/')
            {
                $url .= '/';
            }
            $url .= 'wp-admin/';

            $key = $siteToClone['extauth'];

            SickNetworkHelper::endSession();
            //Send request to the childsite!
            $result = SickNetworkHelper::fetchUrl($url, array('cloneFunc' => 'createCloneBackupPoll', 'key' => $key, 'file' => $rand));

            if (!isset($result['size'])) throw new Exception('Invalid response');

            $output = array('size' => round($result['size'] / 1024, 0));
        }
        catch (Exception $e)
        {
            $output = array('error' => $e->getMessage());
        }
        //Return size in kb
        die(json_encode($output));
    }

    public static function cloneBackupDownload()
    {
        try
        {
            if (!isset($_POST['url'])) throw new Exception('No download link given');
            $url = $_POST['url'];

            SickNetworkHelper::endSession();
            //Send request to the childsite!
            $filename = 'download-'.basename($url);
            $dirs = SickNetworkHelper::getSickNetworkDir('backup');
            $backupdir = $dirs[0];

            if ($dh = opendir($backupdir))
            {
                while (($file = readdir($dh)) !== false)
                {
                    if ($file != '.' && $file != '..' && preg_match('/^download-backup-(.*).zip/', $file))
                    {
                        @unlink($backupdir . $file);
                    }
                }
                closedir($dh);
            }

            $filename = $backupdir . $filename;

            $response = wp_remote_get($url, array( 'timeout' => 300000, 'stream' => true, 'filename' => $filename ) );

            if ( is_wp_error( $response ) ) {
           		unlink( $filename );
           		return $response;
           	}

           	if ( 200 != wp_remote_retrieve_response_code( $response ) ){
           		unlink( $filename );
           		return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
           	}

            $output = array('done' => $filename);
        }
        catch (Exception $e)
        {
            $output = array('error' => $e->getMessage());
        }

        die(json_encode($output));
    }

    public static function cloneBackupDownloadPoll()
    {
        try
        {
            SickNetworkHelper::endSession();
            $dirs = SickNetworkHelper::getSickNetworkDir('backup');
            $backupdir = $dirs[0];

            $files = glob($backupdir . 'download-backup-*.zip');

            if (count($files) == 0) throw new Exception('No download file found');
            $output = array('size' => filesize($files[0]) / 1024);
        }
        catch (Exception $e)
        {
            $output = array('error' => $e->getMessage());
        }
        //return size in kb
        die(json_encode($output));
    }

    public static function cloneBackupExtract()
    {
        try
        {
            SickNetworkHelper::endSession();

            $file = $_POST['file'];
            $testFull = false;
            if ($file == '')
            {
                $dirs = SickNetworkHelper::getSickNetworkDir('backup');
                $backupdir = $dirs[0];

                $files = glob($backupdir . 'download-backup-*.zip');

                if (count($files) == 0) throw new Exception('No download file found');
                $file = $files[0];
            }
            else
            {
                $file = ABSPATH . $file;
                if (!file_exists($file)) throw new Exception('Backup file not found');
                $testFull = true;
            }

            //return size in kb
            $cloneInstall = new SickNetworkCloneInstall($file);
            $cloneInstall->readConfigurationFile();
            if ($testFull)
            {
                $cloneInstall->testDownload();
            }
            $cloneInstall->removeConfigFile();
            $cloneInstall->extractBackup();

            $pubkey = get_option('_sicknetwork_pubkey');
            $uniqueId = get_option('_sicknetwork_uniqueId');
            $server = get_option('_sicknetwork_server');
            $nonce = get_option('_sicknetwork_nonce');
            $nossl = get_option('_sicknetwork_nossl');
            $nossl_key = get_option('_sicknetwork_nossl_key');
            $sitesToClone = get_option('sicknetwork_clone_sites');

            $cloneInstall->install();

            update_option('_sicknetwork_pubkey', $pubkey);
            update_option('_sicknetwork_uniqueId', $uniqueId);
            update_option('_sicknetwork_server', $server);
            update_option('_sicknetwork_nonce', $nonce);
            update_option('_sicknetwork_nossl', $nossl);
            update_option('_sicknetwork_nossl_key', $nossl_key);
            update_option('sicknetwork_clone_sites', $sitesToClone);
            update_option('_sicknetwork_clone_permalink', true);

            $output = array('result' => 'ok');

            wp_logout();
            wp_set_current_user(0);
        }
        catch (Exception $e)
        {
            $output = array('error' => $e->getMessage());
        }
        //return size in kb
        die(json_encode($output));
    }

    public static function permalinkChanged($action)
    {
        if ($action == 'update-permalink')
        {
            if (isset($_POST['permalink_structure']) || isset($_POST['category_base']) || isset($_POST['tag_base']))
            {
                delete_option('_sicknetwork_clone_permalink');
                //echo '<script>jQuery(".sicknetwork_info-box-green").hide();</script>';
            }
        }
    }

    public static function permalinkAdminNotice()
    {
        if (isset($_POST['permalink_structure']) || isset($_POST['category_base']) || isset($_POST['tag_base'])) return;
        ?>
        <style>
        .sicknetwork_info-box-green {
            margin: 5px 0 15px;
            padding: .6em;
            background: rgba(127, 177, 0, 0.3);
            border: 1px solid #7fb100;
            border-radius: 3px ;
            margin-right: 10px;
            -moz-border-radius: 3px ;
            -webkit-border-radius: 3px ;
            clear: both ;
        }
        </style>
        <div class="sicknetwork_info-box-green">Cloning process completed successfully! Check and re-save permalinks <a href="<?php echo admin_url('options-permalink.php'); ?>">here</a>.</div>
        <?php
    }
}