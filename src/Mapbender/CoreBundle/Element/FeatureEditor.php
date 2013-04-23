<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

/**
 * 
 */
class FeatureEditor extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Feature Editor";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Feature Editor";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('Editor');
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.button.js',
                'mapbender.element.featureEditor.js'),
            'css' => array('mapbender.element.featureEditor.css'));
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "FeatureEditor",
            'label' => true);
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        //return 'Mapbender\CoreBundle\Element\Type\AboutDialogAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbFeatureEditor';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = $this->getConfiguration();
        
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:featureeditor_dialog.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }
      
    public function httpAction($action)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

	$configuration = $this->getConfiguration();
	$db = $configuration['database'];
        $table = $configuration['table'];
        $sort_field = $configuration['sort_field']['field'];
        $order = $configuration['sort_field']['order'];
        $uniqueId = $configuration['unique_id'];
        $geom_field = $configuration['geom_field'];
        $srs = $configuration['srs'];

	$connection = $this->container->get('doctrine.dbal.' . $db. '_connection');
		
	if('select' === $action)
        {
            $request = $this->container->get('request');
            $data = json_decode($request->getContent(),true);
            $xyfields = "ST_X(ST_Transform(".$geom_field.", ".$data['srs'].")) As x,
                     ST_Y(ST_Transform(".$geom_field.", ".$data['srs'].")) As y";
            
            $where = " WHERE ".$geom_field." && ST_MakeEnvelope(".
                        $data['left'].",". $data['bottom'].",". $data['right'].",".$data['top'].", ".$srs.")";
            
            $sql = "SELECT *,". $xyfields ." FROM " . $table . $where ." ORDER BY ".$sort_field ." ". $order;
            
//            print_r($sql);
//            die();
            $stmt = $connection->fetchAll($sql);
            $response->setContent(json_encode($stmt));
            return $response;
        }
        
        if('update' === $action) 
        {
            $request = $this->container->get('request');
            $data = json_decode($request->getContent(),true); 
            
            $where = $uniqueId ."=". $data[$uniqueId];
            
            $set = null;
            foreach ($data as $key => $value) {
                if ($key != $uniqueId){
                    if ($value != ''){
                        $set .= $key .' = \''. $value.'\' ,';
                    }
                }            
            }
            $set=substr($set, 0, -1);

            $sql = "UPDATE " . $table . " SET ". $set ." WHERE " . $where;
            $count = $connection->executeUpdate($sql);
            $response->setContent(json_encode($count));
            return $response;
        }
        
        if('position' === $action) 
        {
            $request = $this->container->get('request');
            $data = json_decode($request->getContent(),true);          
            $where = $uniqueId ."=". $data[$uniqueId];           
            $lat = $data['y'];
            $lon = $data['x'];
            
            $set = $geom_field.' = ST_SetSRID(ST_MakePoint('.$lon.','.$lat.'), '.$srs.')';
            $sql = "UPDATE " . $table . " SET ". $set ." WHERE " . $where;
            
            $count = $connection->executeUpdate($sql);
            $response->setContent(json_encode($count));
            return $response;
        }
        
        if('delete' === $action) 
        {
            $request = $this->container->get('request');
            $data = json_decode($request->getContent(),true); 
            $where = $uniqueId ."=". $data[$uniqueId];
            
            $sql = "DELETE FROM " . $table . " WHERE " . $where;

            print_r($sql);
            die();
        }
    }
}

