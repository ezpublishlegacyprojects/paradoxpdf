<?php
/**
 * ParadoxPDF
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   ParadoxPDF
 * @author    Mohamed Karnichi <karnichi[@]gmail.com>
 * @copyright 2009 Mohamed Karnichi
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License V2
 * @version   $Id$
 * @link      http://svn.projects.ez.no/paradoxpdf
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


class ParadoxPDFOperators
{

    function __construct()
    {
        $this->Operators = array( 'paradoxpdf' );
    }

    function operatorList()
    {
        return $this->Operators;
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array(  'paradoxpdf' => array( 'xhtml' =>  array( 'type' => 'string',
                                                                  'required' => true
                                                                ),
                                               'pdf_file_name' => array( 'type' => 'string',
                                                               'required' => false,
                                                                   'default' => 'file'
                                                                   ),
                                               'keys' => array( 'type' => 'mixed',
                                                               'required' => false
                                                                   ),
                                               'subtree_expiry' => array( 'type' => 'string',
                                                                  'required' =>false
                                                                   ),
                                               'expiry' => array( 'type' => 'int',
                                                              'required' =>false
                                                                   ),
                                               'ignore_content_expiry' => array( 'type' => 'boolean',
                                                                       'required' =>false
                                                                   )));
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace,
    $currentNamespace, &$operatorValue, $namedParameters )
    {
        $result = '';

        switch ( $operatorName )
        {
            case 'paradoxpdf':
                {
                    $xhtml = $namedParameters['xhtml'];
                    $pdf_file_name = $namedParameters['pdf_file_name'];
                    $keys = $namedParameters['keys'];
                    $subtree_expiry = $namedParameters['subtree_expiry'];
                    $expiry = $namedParameters['expiry'];
                    $ignore_content_expiry = $namedParameters['ignore_content_expiry'];
                    $result = ParadoxPDF::exportPDF( $xhtml, $pdf_file_name,$keys, $subtree_expiry, $expiry, $ignore_content_expiry ) ;
                }break;

        }

        $operatorValue = $result;

    }

    public $Operators;
}
?>
