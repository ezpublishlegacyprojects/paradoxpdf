<?php
/**
 * ParadoxPDF
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   ParadoxPDF
 * @author    Mohamed Karnichi <www.tricinty.com>
 * @copyright 2009 Mohamed Karnichi
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License V2
 * @version   $Id$
 * @link      http://projects.ez.no/paradoxpdf
 */

// This program is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.

//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//


class ParadoxPDF
{

    private $paradoxPDFExec ;
    private $paradoxPDFExtensionDir;
    private $debugEnabled;
    private $javaExec;
    private $tmpDir;
    private $fileSep;
    private $cacheTTL;


    function ParadoxPDF()
    {
        $paradoxPDFINI = eZINI::instance('paradoxpdf.ini');
        $this->debugEnabled = ($paradoxPDFINI->variable('DebugSettings', 'DebugPDF') == 'enabled');
        $this->javaExec =  $paradoxPDFINI->variable('BinarySettings', 'JavaExecutable');
        $this->cacheTTL =  $paradoxPDFINI->variable('CacheSettings','TTL');
        $fileSep = eZSys::fileSeparator();
        $this->fileSep = $fileSep;
        $this->paradoxPDFExtensionDir = eZSys::rootDir().$fileSep.eZExtension::baseDirectory().$fileSep.'paradoxpdf';
        $this->paradoxPDFExec = $this->paradoxPDFExtensionDir.$fileSep.'bin'.$fileSep.'paradoxpdf.jar';
        $this->tmpDir = eZSys::rootDir().$fileSep.'var'.$fileSep.'paradoxpdf';
    }

    /**
     * Performs PDF content generation and caching
     *
     * @param $xhtml                 String    XHTML content
     * @param $pdf_file_name         String    Name that will be used when serving the PDF file (not for storage)
     * @param $keys                  Mixed     Keys for Cache key(s) - either as a string or an array of strings
     * @param $subtree_expiry        Mixed     The parameter $subtreeExpiryParameter is expiry value is usually taken
     *                                         from the template operator and can be one of:
     *                                           - A numerical value which represents the node ID (the fastest approach)
     *                                           - A string containing 'content/view/full/xxx' where xx is the node ID number,
     *                                             the number will be extracted.
     *                                           - A string containing a nice url which will be decoded into a node ID using
     *                                             the database (slowest approach).
     * @param $expiry                Integer   The number of seconds that the pdf cache should be allowed to live.A value of
     *                                         zero will produce a cache block that will never expire
     * @param $ignore_content_expiry Boolean   Disables cache expiry when new content is published.
     * @return void
     */

    public function exportPDF($xhtml = '', $pdf_file_name = '', $keys, $subtree_expiry, $expiry, $ignore_content_expiry = false)
    {
        if($pdf_file_name == '')
        {
            $pdf_file_name = 'file';
        }

        $use_global_expiry = !$ignore_content_expiry;

        $keys = self::getCacheKeysArray($keys);

        $expiry = (is_numeric($expiry) ) ? $expiry : $this->cacheTTL;

        list($handler, $data) = eZTemplateCacheBlock::retrieve($keys, $subtree_expiry, $expiry, $use_global_expiry);

        if ($data instanceof eZClusterFileFailure)
        {
            $data = $this->generatePDF($xhtml);

            // check if error occurred during pdf generation
            if($data === false)
            {
                return;
            }
            $handler->storeCache(array(  'scope'      => 'template-block',
                                         'binarydata' => $data));
        }

        $size  = $handler->size();
        $mtime = $handler->mtime();

        $this->flushPDF($data, $pdf_file_name, $size, $mtime, $expiry);
        return;
    }

    /**
     * Converts xhtml to pdf
     *
     * @param $xhtml
     * @return Binary pdf content of false if error
     */
    public function generatePDF($xhtml)
    {
        //check if $tmpdir exists else try to create it
        if(!eZFileHandler::doExists($this->tmpDir))
        {
            if(!eZDir::mkdir( $this->tmpDir, eZDir::directoryPermission(), true ))
            {
                eZDebug::writeWarning("ParadoxPDF::generatePDF Error : could not create temporary directory $this->tmpDir ", 'ParadoxPDF::generatePDF');
                eZLog::write("ParadoxPDF::generatePDF Error : could not create temporary directory $this->tmpDir ",'paradoxpdf.log');
                return false;
            }
        }
        elseif(!eZFileHandler::doIsWriteable($this->tmpDir))
        {
            //check if $tmpdir is writable
            eZDebug::writeWarning("ParadoxPDF::generatePDF Error : please make $this->tmpDir writable ", 'ParadoxPDF::generatePDF');
            eZLog::write("ParadoxPDF::generatePDF Error : please make $this->tmpDir writable ",'paradoxpdf.log');
            return false;
        }

        $rand = md5('paradoxpdf'. getmypid() . mt_rand());
        $tmpXHTMLFile = $this->tmpDir.$this->fileSep.$rand.'.xhtml';
        $tmpPDFFile = $this->tmpDir.$this->fileSep.$rand.'.pdf';

        //fix relative urls to match ez root directory
        $xhtml = $this->fixURL($xhtml);

        eZFile::create($tmpXHTMLFile, false, $xhtml) ;

        $pdfConent = '';

        //run jar in headless mode
        $command = $this->javaExec." -Djava.awt.headless=true";

        if($this->debugEnabled)
        {
            $command .= " -Dxr.util-logging.loggingEnabled=true";
        }

        $command .= " -jar ".$this->paradoxPDFExec." $tmpXHTMLFile $tmpPDFFile";

        if(eZSys::osType() != 'win32')
        {
            //fix to get command output result on *nix systems
            $command .= "  2>&1";
        }

        //Enter the Matrix
        exec($command, $output, $returnCode);

        //Cant trust java return code so we test if a plain pdf file is genereated
        if (!(eZFileHandler::doExists($tmpPDFFile) && filesize($tmpPDFFile)))
        {
            $this->writeCommandLog($command, $output, false);
            return false;
        }

        $this->writeCommandLog($command, $output, true);

        $pdfContent = file_get_contents($tmpPDFFile); //thanks to Damien Pobel

        //cleanup temporary files
        //if debug enabled preseves the temporary pdf file
        //else remove all temporary files

        if(!$debugEnabled)
        {
            eZFileHandler::unlink($tmpPDFFile);
            eZFileHandler::unlink($tmpXHTMLFile);
        }

        return $pdfContent;
    }

    /**
     *  Flush PDF content to browser
     *
     * @param $data
     * @param $pdf_file_name
     * @param $size
     * @param $mtime
     * @param $expiry
     * @return void
     */
    private function flushPDF($data, $pdf_file_name='file', $size, $mtime, $expiry)
    {
        ob_clean();

        header('X-Powered-By: eZ Publish - ParadoxPDF');
        header('Expires: ' . gmdate('D, d M Y H:i:s', $mtime + $expiry) . ' GMT');
        header('Cache-Control: max-age=' . $expiry);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('Content-Type: application/pdf');
        header('Content-Length: ' . $size);
        //TODO : sanitize pdf_file_name to prevent file donwload injection attacks
        header('Content-Disposition: attachment; filename="'.$pdf_file_name.'.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Connection: Close');

        ob_end_clean();

        print($data);

        eZExecution::cleanExit();
        return;
    }


    /**
     *  Generate cache  key array based on current user roles, requested url, layout
     *
     * @param $userKeys Array
     * @return array
     */

    public function getCacheKeysArray( $userKeys )
    {
        if(!is_array($userKeys))
        {
            $userKeys = array($userKeys);
        }

        $user = eZUser::currentUser();
        $limitedAssignmentValueList = $user->limitValueList();
        $roleList = $user->roleIDList();
        $discountList = eZUserDiscountRule::fetchIDListByUserID( $user->attribute( 'contentobject_id' ) );
        $currentSiteAccess = ( isset( $GLOBALS['eZCurrentAccess']['name'] ) ) ? $GLOBALS['eZCurrentAccess']['name']:false ;
        $res = eZTemplateDesignResource::instance();
        $keys = $res->keys();
        $layout= ( isset( $keys['layout'] ) ) ? $keys['layout'] : false;
        $uri = eZURI::instance( eZSys::requestURI() );
        $actualRequestedURI = $uri->uriString();
        $userParameters = $uri->userParameters();

        $cacheKeysArray = array('paradoxpdf',
                                $currentSiteAccess,
                                $layout,
                                $actualRequestedURI,
                                implode( '.', $userParameters ),
                                implode( '.', $roleList ),
                                implode( '.', $limitedAssignmentValueList),
                                implode( '.', $discountList ),
                                implode( '.', $userKeys ));

        return $cacheKeysArray;

    }

    /**
     *  Log execution output
     *
     * @param $command String executed command
     * @param $output Array command execution output
     * @return Void
     */

    private function writeCommandLog($command, $output, $status=false)
    {

        $logMessage = implode("\n", $output);

        if(!$status)
        {
            eZDebug::writeError("An error occured during pdf generation please check var/log/paradoxpdf.log", 'ParadoxPDF::generatePDF');
            eZLog::write("Failed executing command : $command , \n Output : $logMessage",'paradoxpdf.log');
        }
        elseif($debugEnabled)
        {
            eZLog::write("ParadoxPDF : PDF conversion successful: $command , \n Output : $logMessage",'paradoxpdf.log');
        }

    }



    /**
     *  Make image and css urls relative to ezpublish root directory
     *
     * @param $html String
     * @return String html with fixed urls
     */

    private function fixURL($html)
    {
        $htmlfixed = preg_replace('#(href|src)\s*=\s*["\'](?!https?|mailto)(\/?)(.*\..{2,4})["\']#i', '$1="../../$3"', $html);
        return $htmlfixed;
    }

}
?>