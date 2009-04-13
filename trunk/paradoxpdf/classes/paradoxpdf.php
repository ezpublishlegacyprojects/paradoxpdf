<?php
/**
 * ParadoxPDF
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   ParadoxPDF
 * @author    Mohamed Karnichi
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
    function _construct()
    {

    }
    /**
     * Performs PDF content generation and caching
     *
     * @param $xhtml                XHTML content
     * @param $pdf_file_name        name that will be used when serving the PDF file
     * @param $keys                 keys for Cache key(s) - either as a string or an array of strings
     * @param $subtree_expiry       A subtree that expires the pdf file.
     * @param $expiry               The number of seconds that the cache should be allowed to live.
     * @param $ignore_content_expiry Disables cache expiry when new content is published.
     * @return void
     */

    static function exportPDF($xhtml, $pdf_file_name = 'file', $keys = array(), $subtree_expiry, $expiry, $ignore_content_expiry)
    {
        if($pdf_file_name == '')
        {
            $pdf_file_name = 'file';
        }

        $use_global_expiry = !$ignore_content_expiry;

        $keys = self::getCacheKeysArray($keys);

        $ini = eZINI::instance();
        $paradoxPDFINI = eZINI::instance('paradoxpdf.ini');

        //TODO : check if viewcache is enabled else serve the generated pdf on the fly

        $expiry = ($expiry) ? $expiry : $paradoxPDFINI->variable('CacheSettings','TTL');

        list($handler, $data) = eZTemplateCacheBlock::retrieve($keys, $subtree_expiry, $expiry, $use_global_expiry);

        if ($data instanceof eZClusterFileFailure)
        {
            $data = self::generatePDF($xhtml);

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

        self::flushPDF($data, $pdf_file_name, $size, $mtime, $expiry);
        return;
    }

    /**
     * Converts xhtml to pdf
     *
     * @param $xhtml
     * @return Binary pdf content of false if error
     */
    static function generatePDF($xhtml)
    {
        $ini = eZINI::instance();
        $paradoxPDFINI = eZINI::instance('paradoxpdf.ini');
        $sep = eZSys::fileSeparator();
        $paradoxPDFExtensionDir = eZSys::rootDir().$sep.eZExtension::baseDirectory().$sep.'paradoxpdf';
        $debugEnabled = ($paradoxPDFINI->variable('DebugSettings', 'DebugPDF') == 'enabled');

        $javaExec = $paradoxPDFINI->variable('BinarySettings', 'JavaExecutable');
        $paradoxPDFExec =$paradoxPDFExtensionDir.$sep.'bin'.$sep.'paradoxpdf.jar';

        // temporary files for conversion
        $tmpDir = $paradoxPDFExtensionDir.$sep.'tmp';
        $rand = md5('paradoxpdf'. getmypid() . mt_rand());
        $tmpXHTMLFile = $tmpDir.$sep.$rand.'.xhtml';
        $tmpPDFFile = $tmpDir.$sep.$rand.'.pdf';

        //check if $tmpdir is writable
        if(!eZFileHandler::doIsWriteable($tmpDir))
        {
            eZDebug::writeWarning("ParadoxPDF::generatePDF Error : please make $tmpDir writable ", 'ParadoxPDF::generatePDF');
            eZLog::write("ParadoxPDF::generatePDF Error : please make $tmpDir writable ",'paradoxpdf.log');

            return false;
        }

        //fix relative urls to match ez root directory
        $xhtml = self::fixURL($xhtml);

        eZFile::create($tmpXHTMLFile, false, $xhtml) ;

        $pdfConent = '';

        //run jar in headless mode
        $command = "$javaExec -Djava.awt.headless=true -jar $paradoxPDFExec $tmpXHTMLFile $tmpPDFFile";



        if(eZSys::osType() == 'win32')
        {
            $command = "\"$systemString\"";
        }
        else
        {
            if($debugEnabled)
            {
                //fix to get command output result on *unix systems
                $command .= '  2>&1';
            }

        }

        //Enter the Matrix
        exec($command, $output, $returnCode);

        //Cant trust java return code so we test if a plain pdf file is genereated
        if (!(eZFileHandler::doExists($tmpPDFFile) && filesize($tmpPDFFile)))
        {
            eZDebug::writeWarning("Failed executing: $command, Error code: $returnCode", 'ParadoxPDF::generatePDF');
            eZLog::write("Failed executing command: $command, Error code: $returnCode",'paradoxpdf.log');

            //log paradoxpdf.jar output if debug mode enabled

            if($debugEnabled)
            {
                $logMessage = implode("\n",$output);
                eZLog::write("Output : $logMessage",'paradoxpdf.log');
            }

            return false;
        }
        else
        {
            $pdfContent = eZFile::getContents($tmpPDFFile);
        }

        eZLog::write("Failed executing command: $command, Error code: $returnCode",'paradoxpdf.log');


        $logMessage = implode("\n",$output);
        eZLog::write("Output : $logMessage",'paradoxpdf.log');

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
    static function flushPDF($data, $pdf_file_name='file', $size, $mtime, $expiry)
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
     * @param $userKeys
     * @return array
     */

    static function getCacheKeysArray( $userKeys )
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
     *  Make image and css urls relative to ezpublish root directory
     *
     * @param $html
     * @return String html with fixed urls
     */

    static function fixURL($html)
    {
        $htmlfixed = preg_replace('#(href|src)=("|\')(.*\.(css|img|js))("|\')#', '$1="../../..$3"', $html);
        return $htmlfixed;
    }

}
?>