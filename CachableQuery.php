<?php
/**
 * Doctrine_Template_CachableQuery
 *
 * @package     Doctrine
 * @author      Stéphane PY <py.stephane1@gmail.com>
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.2
 * @version     $Revision$
 */
class Doctrine_Template_CachableQuery extends Doctrine_Template
{
    protected $_options = array("remove"   => array());
                                
   /**
    * __construct
    *
    * @param string $array 
    * @return void
    */
   public function __construct(array $options = array())
   {
     $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
     
   }
    
  public function setTableDefinition()
  {   
    $this->addListener(new Doctrine_Template_Listener_CachableQuery($this->_options));
  }
}