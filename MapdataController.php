<?php
/**
 * MapdataController
 *
 * Handles requests made to /my-account/mapdata.
 *
 * @requires Zend_Controller_Action
 * @author   Jd Daniel <!@clearwire.com>
 */
class MapdataController extends Zend_Controller_Action {
    /**
     * kmlGatewayAction()
     *
     * Handles requests made to /my-account/mapdata/kml-gateway. This action is an
     * exact copy of ****** kml-gateway.php, but hosted within my-account to get
     * around ****** access restriction. In addition, the following two
     * modifications have been made:
     *
     * 1. kml_gateway.php's token-based authentication is removed. This method has
     *    no access restrictions.
     * 2. kml_gateway.php didn't handle the error when the kml file in question
     *    doesn't exist. This action method raises a 404 instead.
     *
     */
    public function kmlGatewayAction() {
        $kmlRoot = APPLICATION_PATH . '/xml/******/';
        
        $this->_helper->layout->disableLayout();

        $market = $this->getRequest()->getParam('location');
        $style = $this->getRequest()->getParam('style');
        $kmlFile = null;

        if (isset($market, $style)) {
            foreach (array('market', 'style') as $toFilter) {
                ${$toFilter} = preg_replace('/[^a-z0-9\-\_]/i', '', ${$toFilter});
            }

            unset($toFilter);

            $kmlFile = $kmlRoot . $market . '/' . $style . '.kml';
        }

        if (!isset($kmlFile) || !file_exists($kmlFile)) {
            $this->_helper->viewRenderer->setNoRender(true);
            return $this->getResponse()
                ->setHttpResponseCode(404)
                ->appendBody("Location: $market is not found.");
        }

        /**
         * innerBoundaryIs cleanup
         * -- KML specifications state that you can only have one inner ring per innerBoundaryIs tag.
         * -- Go through the innerBoundayIs tags, find the LinearRing tags, if there's more than 1, harvest the data inside, 
         * -- Create new InnerBoundaryIs tags with one LinearRing, append them to the original innerboundaryIS tag's parent.
         */
        $xmlObj = new DOMDocument();
        $xmlObj->formatOutput = true;
        $xmlObj->load($kmlFile);
        $boundaries = $xmlObj->getElementsByTagName("innerBoundaryIs");
        $length = $boundaries->length;

        for($count = 0; $count < $length; $count++ ) { 
            $linearRing = $boundaries->item($count)->getElementsByTagName("LinearRing");
            $linearRingCount = $linearRing->length;
            if($linearRingCount > 1) { 
                for($innerCount = 1; $innerCount < $linearRingCount; $innerCount++) { 
                    $parent = $boundaries->item($count)->parentNode;
                    $newBoundary = $xmlObj->createElement("innerBoundaryIs");
                    $newBoundary->appendChild($linearRing->item(1)->cloneNode(true));
                    $boundaries->item($count)->removeChild($linearRing->item(1));
                    $parent->appendChild($newBoundary);
                }
            }
        }

        /**
         * Schema Cleanup. 
         * -- Google Maps' KML rendering API doesnt' support Schema Override tags, so we simply find the schema override, replace it with "Placemark" 
         * -- Then output the KML
         */
        $schemaTags = $xmlObj->getElementsByTagName("Schema");
        $needsCleanup = $schemaTags->length;

        if($needsCleanup > 0) {
            $target = $schemaTags->item(0)->getAttribute("name");
            $xmlObj->getElementsByTagName("Document")->item(0)->removeChild($schemaTags->item(0));
            $output = $xmlObj->saveXML();
            $output = str_replace($target, "Placemark", $output);
        }
        else {
            $output = $xmlObj->saveXML();
        }

        // output overrider 
        $output = str_replace("default+nicon=http://maps.google.com/mapfiles/kml/pal4/icon57.png+hicon=http://maps.google.com/mapfiles/kml/pal4/icon49.png", "stylemap", $output);           
        $output = str_replace("default+icon=http://maps.google.com/mapfiles/kml/pal4/icon57.png", "style1", $output);                
        $output = str_replace("default+icon=http://maps.google.com/mapfiles/kml/pal4/icon49.png", "style2", $output);

        header('Content-type: application/xml');
        echo $output;
        die;
    }
}