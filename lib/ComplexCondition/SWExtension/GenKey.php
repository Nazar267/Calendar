<?php
namespace ComplexCondition\SWExtension;

/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 29.12.13 20:19
 * You must not use this file without permission.
 *
 * Version 1.1
 *  [add] function getState
 * Version 1.2
 *  [add] function getLicenseFor
 * Version 1.3
 *  [add] function hasLicenseKey
 *  [fix] License Check 
 */
    class GenKey {
        const serverHTTP = "aHR0cDovL3NlcmlhbC5zaG9wLnN0ZWZhbndhcm5hdC5kZS9zZXJpYWwv";
        const serverHTTPS = "aHR0cHM6Ly9zaG9wLnN0ZWZhbndhcm5hdC5kZS9zZXJpYWwv";

        const LICENSE_FOR = 4;
        
        private $module = "";
        private $moduleVersion = 0;
        private $licenseDir = "";

        private $functions = array();

        public function __construct($moduleName, $moduleVersion, $licenseDirectory = false) {
            $this->module = $moduleName;
            $this->moduleVersion = $moduleVersion;

            global $root_directory;
            $this->licenseDir = $licenseDirectory!==false?$licenseDirectory:$root_directory."/modules/".$this->module."/";

            #$this->functions = array(
            #    sha1($moduleName."-setLicense") => "setLicense",
            #    sha1($moduleName."-checkLicense") => "checkLicence"
            #    sha1($moduleName."-getState") => "getState"
            #);
        }

		public function hasLicenseKey() {
            global $site_URL;
            if(file_exists($this->licenseDir.sha1($site_URL).".dat")) {
                $content = gzfile($this->licenseDir.sha1($site_URL).".dat");
                $licenseCode = trim($content[1]);
				
				if(!empty($licenseCode)) {
					return true;
				}
            }
			
			return false;
		}
		
        public function checkLicense($again = false) {
            $doRevalidate = true;
            global $site_URL, $vtiger_current_version;

            if(!file_exists($this->licenseDir.sha1($site_URL).".dat")) {
                if(file_exists($this->licenseDir.'/MARKETPLACE.module')) {
                    $this->setLicense();
                    if($again == false) {
                        return $this->checkLicense();
                    }
                }
                return false;
            }

            /*e5zrdthdrht4vr6rd
            r5zrdzrdz5rd5
            +#äör#hafgndtsd6öd5sr5v
            r5zrdujrthrdthökäk6bjft#6jtbfbä#b
            drbdasdru5#dä*/
            $content = gzfile($this->licenseDir.sha1($site_URL).".dat");

            /*e5z4vr6rd
                    r5zrdzrdzrstrn5rd5
                    +#äör#h6öd5sr5v
                    srthr#6jtbfbä#b
                    drbdrurthrdth5#dä*/

            $files = @unserialize(base64_decode(trim($content[2])));
            $checksum = "";
            foreach($files as $index => $fileCheck) {
                $checksum .= sha1_file($fileCheck);
            }

            $lastHash = sha1(trim($content[0])."a./b.-".md5($site_URL)."#asd".preg_replace("/[^a-zA-Z0-9]/", "", $vtiger_current_version)."#asd".sha1($checksum)."#".sha1(__FILE__));

            if(trim($content[0]) == md5(md5($site_URL)."#~#".trim($content[1])."#~#".$this->moduleVersion)) {

                set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR);
                require_once(dirname(__FILE__)."/Crypt/Blowfish.php");

                /*e5z4vr6rd
                r5zrdzrdz5rd5
                +#äör#h6öd5sr5v
                r5zrdu6bjft#6jtbfbä#b
                drbdru5#dä*/
                $bf = \Crypt_Blowfish::factory('cbc');$iv = 'abc123+=';
                $bf->setKey($lastHash, $iv);
                try {
                    $config = unserialize($bf->decrypt(base64_decode($content[3])));
                /*e5z4vr6rd
                r5zrdzrdz5rd5
                +#äör#h6öd5sr5v
                r5zrdu6bjft#6jtbfbä#b
                drbdru5#dä*/
                } catch (\Exception $exp) {
                    $config = array();
                }

                /*e5z4vr6rd
                r5zrdzrdz5rd5
                +#äör#h6öd5sr5vs
                r5zrdu6bjft#6jtbfbä#b
                drbdru5#dä*/
                if(isset($config["ok"]) && $config["ok"] == "true" && $config["hash"] == trim($content[0])) {
                    return true;
                }

            }

            if($again === false) {
                $this->setLicense();
            }

			if($again == true) {
				return false;
			}
			
            if($this->checkLicense(true) == true) {
                return true;
            }

            return false;
        }

        public function getLicenseHash() {

        }
        public function setLicense($licenseCode = "", $additionalFilesChecks = array()) {
            global $site_URL;
            require_once(dirname(__FILE__)."/nusoap/nusoap.php");

            if(!is_writeable($this->licenseDir)) {
                throw new \Exception('Module Directory not writable to store license!<br/><br/>'.$this->licenseDir.'');
            }

            if(extension_loaded("curl")) {
                $url = self::serverHTTPS;
            } else {
                $url = self::serverHTTP;
            }

            $additionalFilesChecks[] = __FILE__;

            if(file_exists($this->licenseDir.'/MARKETPLACE.module')) {
                $content = file_get_contents($this->licenseDir.'/MARKETPLACE.module');
                $licenseCode = trim($content);
                $fkt = "h90ftmgtu84";
                $isMarketplace = 'y';
            } else {
                $fkt = "Unsrz7089";
                $isMarketplace = 'n';

                if(file_exists($this->licenseDir.sha1($site_URL).".dat")) {
                    $content = gzfile($this->licenseDir.sha1($site_URL).".dat");
                    $lastHash = trim($content[0]);

                    if(!empty($licenseCode)) {
                        $licenseCode = md5($licenseCode);
                    }

                    if(!empty($content[1]) && empty($licenseCode)) {
                        $licenseCode = trim($content[1]);
                    }

                    if(empty($licenseCode)) {
                        $licenseCode = md5("free");
                    }

                } else {
                    if(empty($licenseCode)) {
                        $licenseCode = md5("free");
                    } else {
                        $licenseCode = md5($licenseCode);
                    }
                }
            }

            $function = "bas"."e64"."_dec"."ode";
            $url = $function($url);

            $client = new \wf_nusoap_client($url, false);
            $err = $client->getError();

            global $site_URL, $vtiger_current_version;
            $checksum = "";
            foreach($additionalFilesChecks as $index => $fileCheck) {
                $additionalFilesChecks[$index] = realpath($fileCheck);
                $checksum .= sha1_file($fileCheck);
            }

            $client->soap_defencoding = 'UTF-8';
            $client->decode_utf8 = false;
            $result=$client->call($fkt,array(
                md5($site_URL),
                $licenseCode,
                $lastHash,
                $this->module."#".$this->moduleVersion."#".$vtiger_current_version.'#'.$isMarketplace,
                basename(__FILE__),
                filesize(__FILE__),
                sha1($checksum),
                sha1(__FILE__)
                )
            );
            if(!empty($_REQUEST["stefanDebug"])) {
                echo "<pre>";var_dump($client->debug_str);
            }

            $result = @unserialize($result);

            if($isMarketplace == 'y') {
                if($result == false || $result["result"] != "ok") {
                    throw new \Exception("Your Marketplace-License could not be verified.", 2);
                    return true;
                }
            } else {
                if($result == false || $result["result"] != "ok") {
                    throw new \Exception("Your license could not be verified.", 2);
                    return true;
                }
            }

            if($result == false) {
                $result = array('hash' => '', 'config' => '');
                //@unlink($this->licenseDir.sha1($site_URL).".dat");

                //throw new Exception("License could not be verified.", 2);
                //return false;
            } elseif($result["result"] != "ok") {
                $result = array('hash' => '', 'config' => '');
                #throw new Exception($result["error"], 3);
                #return;
            }

            $content = $result["hash"]."\n";
            $content .= trim($licenseCode)."\n";
            $content .= base64_encode(serialize($additionalFilesChecks))."\n";
            $content .= $result["config"]."\n";
            $content .= $result["license_for"]."\n";

            $gz = gzopen($this->licenseDir.sha1($site_URL).".dat",'w9');
            gzwrite($gz, $content);
            gzclose($gz);
        }
        public function isMarketplace() {
            if(file_exists($this->licenseDir.'/MARKETPLACE.module')) {
                return true;
            }
            return false;
        }
        public function removeLicense() {
            global $site_URL, $vtiger_current_version;
            @unlink($this->licenseDir.sha1($site_URL).".dat");
        }

        public function getLicenseFor() {
            $doRevalidate = true;
            global $site_URL, $vtiger_current_version;

            if(!file_exists($this->licenseDir.sha1($site_URL).".dat")) {
                return 'Demo License';
            }

            $content = gzfile($this->licenseDir.sha1($site_URL).".dat");

            return $content[self::LICENSE_FOR];
        }
        public function getState() {
            $doRevalidate = true;
            global $site_URL, $vtiger_current_version;

            if(!file_exists($this->licenseDir.sha1($site_URL).".dat")) {
                return 'free';
            }

            /*e5z4vr6rd
            r5zrdzrdz5rd5
            +#äör#h6öd5sr5v
            r5zrdu6bjft#6jtbfbä#b
            drbdru5#dä*/
            $content = gzfile($this->licenseDir.sha1($site_URL).".dat");
            /*e5z4vr6rd
                    r5zrdzrdz5rd5
                    +#äör#h6öd5sr5v
                    r5zrdu6bjft#6jtbfbä#b
                    drbdru5#dä*/

            $files = @unserialize(base64_decode(trim($content[2])));
            $checksum = "";
            foreach($files as $index => $fileCheck) {
                $checksum .= sha1_file($fileCheck);
            }

            $lastHash = sha1(trim($content[0])."a./b.-".md5($site_URL)."#asd".preg_replace("/[^a-zA-Z0-9]/", "", $vtiger_current_version)."#asd".sha1($checksum)."#".sha1(__FILE__));

            if(trim($content[0]) == md5(md5($site_URL)."#~#".trim($content[1])."#~#".$this->moduleVersion)) {
                set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__));
                require_once(dirname(__FILE__)."/Crypt/Blowfish.php");

                /*e5z4vr6rd
                r5zrdzrdz5rd5
                +#äör#h6öd5sr5v
                r5zrdu6bjft#6jtbfbä#b
                drbdru5#dä*/
                $bf = \Crypt_Blowfish::factory('cbc');$iv = 'abc123+=';
                $bf->setKey($lastHash, $iv);
                try {
                    $config = unserialize($bf->decrypt(base64_decode($content[3])));
                /*e5z4vr6rd
                r5zrdzrdz5rd5
                +#äör#h6öd5sr5v
                r5zrdu6bjft#6jtbfbä#b
                drbdru5#dä*/
                } catch (\Exception $exp) {
                    return 'free';
                }
                /*e5z4vr6rd
                r5zrdzrdz5rd5
                +#äör#h6öd5sr5v
                r5zrdu6bjft#6jtbfbä#b
                drbdru5#dä*/

                if(isset($config["ok"]) && $config["ok"] == "true" && $config["hash"] == trim($content[0])) {
                    return $config['stage'];
                }

                $this->setLicense();
                if($this->checkLicense(true) == true) {
                    return $this->getState();
                }
            }

            return false;
        }

        function __call($methodname, $args) {
            if($methodname == "g".sha1($this->module."_setLicense") || $methodname == "g".md5($this->module."_setLicense")) {
                return call_user_func_array(array($this, "setLicense"), $args);
            }
            if($methodname == "g".sha1($this->module."_checkLicense") || $methodname == "g".md5($this->module."_checkLicense")) {
                return call_user_func_array(array($this, "checkLicense"), $args);
            }
            if($methodname == "g".sha1($this->module."_getState") || $methodname == "g".md5($this->module."_getState")) {
                return call_user_func_array(array($this, "getState"), $args);
            }
            if($methodname == "g".sha1($this->module."_getLicenseHash") || $methodname == "g".md5($this->module."_getLicenseHash")) {
                return call_user_func_array(array($this, "getLicenseHash"), $args);
            }
            if($methodname == "g".sha1($this->module."_getLicenseFor") || $methodname == "g".md5($this->module."_getLicenseFor")) {
                return call_user_func_array(array($this, "getLicenseFor"), $args);
            }
            if($methodname == "g".sha1($this->module."_isMarketplace") || $methodname == "g".md5($this->module."_isMarketplace")) {
                return call_user_func_array(array($this, "isMarketplace"), $args);
            }

            throw new \Exception("GenKey Function not found!");
        }

    }
