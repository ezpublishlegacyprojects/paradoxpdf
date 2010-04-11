<?php
/**
 * ParadoxPDFOperators
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
        return array(  'paradoxpdf' => array('paradoxpdf_params' => array( 'type'     => 'array',
                                                                           'required' => true
                                                                         )));
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace,
    $currentNamespace, &$operatorValue, $namedParameters )
    {
        $params = $namedParameters['paradoxpdf_params'];

        // If content node, check specific access policies
        $moduleResult = $tpl->variable('module_result');

        if( isset($moduleResult['node_id']) && !ParadoxPDF::canPDFNode($moduleResult['node_id']))
        {
            $operatorValue = ezpI18n::tr( 'design/standard/error/kernel', 'Access denied!' );
            return;
        }


        switch ( $operatorName )
        {
            case 'paradoxpdf':
                {
                    $xhtml                 = isset($params['xhtml'])?$params['xhtml'] : '';
                    $pdf_file_name         = isset($params['pdf_file_name'])?$params['pdf_file_name'] : '' ;
                    $keys                  = isset($params['keys'])? $params['keys'] : array();
                    $subtree_expiry        = isset($params['subtree_expiry'])?$params['subtree_expiry'] : '';
                    $expiry                = isset($params['expiry'])?$params['expiry'] : null ;
                    $ignore_content_expiry = isset($params['ignore_content_expiry'])?$params['ignore_content_expiry'] : false;

                    $paradoxpdf = new ParadoxPDF();
                    $paradoxpdf->exportPDF( $xhtml, $pdf_file_name,$keys, $subtree_expiry, $expiry, $ignore_content_expiry ) ;

                }break;

        }
        $operatorValue = '';



    }

    public $Operators;
}
?>
